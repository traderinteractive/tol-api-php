<?php

namespace DominionEnterprises\Api;
use DominionEnterprises\Util;
use DominionEnterprises\Util\Arrays;
use DominionEnterprises\Util\Http;

/**
 * Client for apis
 */
final class Client implements ClientInterface
{
    /**
     * Flag to cache no requests
     *
     * @const int
     */
    const CACHE_MODE_NONE = 0;

    /**
     * Flag to cache only GET requests
     *
     * @const int
     */
    const CACHE_MODE_GET = 1;

    /**
     * Flag to cache only TOKEN requests
     *
     * @const int
     */
    const CACHE_MODE_TOKEN = 2;

    /**
     * Flag to cache ALL requests
     *
     * @const int
     */
    const CACHE_MODE_ALL = 3;

    /**
     * Flag to refresh cache on ALL requests
     *
     * @const int
     */
    const CACHE_MODE_REFRESH = 4;

    /**
     * Base url of the API server
     *
     * @var string
     */
    private $_baseUrl;

    /**
     * HTTP Adapter for sending request to the api
     *
     * @var Adapter
     */
    private $_adapter;

    /**
     * Oauth authentication implementation
     *
     * @var Authentication
     */
    private $_authentication;

    /**
     * API access token
     *
     * @var string
     */
    private $_accessToken;

    /**
     * API refresh token
     *
     * @var string
     */
    private $_refreshToken;

    /**
     * Storage for cached API responses
     *
     * @var Cache
     */
    private $_cache;

    /**
     * Strategy for caching
     *
     * @var int
     */
    private $_cacheMode;

    /**
     * Handles set in _start()
     *
     * @var array like [opaqueKey => [cached response (Response), adapter handle (opaque), Request]]
     */
    private $_handles = [];

    /**
     * Array of headers that are passed on every request unless they are overridden
     *
     * @var array
     */
    private $_defaultHeaders = [];

    /**
     * Create a new instance of Client
     *
     * @param Adapter $adapter
     * @param Authentication $authentication
     * @param string $baseUrl
     * @param int $cacheMode
     * @param Cache $cache
     * @param string $accessToken
     * @param string $refreshToken
     *
     * @throws \InvalidArgumentException Thrown if $baseUrl is not a non-empty string
     * @throws \InvalidArgumentException Thrown if $cacheMode is not one of the cache mode constants
     */
    public function __construct(
        Adapter $adapter,
        Authentication $authentication,
        $baseUrl,
        $cacheMode = self::CACHE_MODE_NONE,
        Cache $cache = null,
        $accessToken = null,
        $refreshToken = null
    )
    {
        Util::throwIfNotType(['string' => [$baseUrl]], true);
        Util::throwIfNotType(['string' => [$accessToken, $refreshToken]], true, true);
        Util::ensure(
            true,
            in_array(
                $cacheMode,
                [self::CACHE_MODE_NONE, self::CACHE_MODE_GET, self::CACHE_MODE_TOKEN, self::CACHE_MODE_ALL, self::CACHE_MODE_REFRESH],
                true
            ),
            '\InvalidArgumentException',
            ['$cacheMode must be a valid cache mode constant']
        );

        if ($cacheMode !== self::CACHE_MODE_NONE) {
            Util::ensureNot(null, $cache, '\InvalidArgumentException', ['$cache must not be null if $cacheMode is not CACHE_MODE_NONE']);
        }

        $this->_adapter = $adapter;
        $this->_baseUrl = $baseUrl;
        $this->_authentication = $authentication;
        $this->_cache = $cache;
        $this->_cacheMode = $cacheMode;
        $this->_accessToken = $accessToken;
        $this->_refreshToken = $refreshToken;
    }

    /**
     * Get access token and refresh token
     *
     * @return array two string values, access token and refresh token
     */
    public function getTokens()
    {
        return [$this->_accessToken, $this->_refreshToken];
    }

    /**
     * Search the API resource using the specified $filters
     *
     * @param string $resource
     * @param array $filters
     *
     * @return mixed opaque handle to be given to endIndex()
     */
    public function startIndex($resource, array $filters = [])
    {
        Util::throwIfNotType(['string' => [$resource]], true);
        $url = "{$this->_baseUrl}/" . urlencode($resource) . '?' . Http::buildQueryString($filters);
        return $this->_start($url, 'GET');
    }

    /**
     * @see startIndex()
     */
    public function index($resource, array $filters = [])
    {
        return $this->end($this->startIndex($resource, $filters));
    }

    /**
     * Get the details of an API resource based on $id
     *
     * @param string $resource
     * @param string $id
     *
     * @return mixed opaque handle to be given to endGet()
     */
    public function startGet($resource, $id)
    {
        Util::throwIfNotType(['string' => [$resource, $id]], true);
        $url = "{$this->_baseUrl}/" . urlencode($resource) . '/' . urlencode($id);
        return $this->_start($url, 'GET');
    }

    /**
     * @see startGet()
     */
    public function get($resource, $id)
    {
        return $this->end($this->startGet($resource, $id));
    }

    /**
     * Create a new instance of an API resource using the provided $data
     *
     * @param string $resource
     * @param array $data
     *
     * @return mixed opaque handle to be given to endPost()
     */
    public function startPost($resource, array $data)
    {
        Util::throwIfNotType(['string' => [$resource]], true);
        $url = "{$this->_baseUrl}/" . urlencode($resource);
        return $this->_start($url, 'POST', json_encode($data), ['Content-Type' => 'application/json']);
    }

    /**
     * @see startPost()
     */
    public function post($resource, array $data)
    {
        return $this->end($this->startPost($resource, $data));
    }

    /**
     * Update an existing instance of an API resource specified by $id with the provided $data
     *
     * @param string $resource
     * @param string $id
     * @param array $data
     *
     * @return mixed opaque handle to be given to endPut()
     */
    public function startPut($resource, $id, array $data)
    {
        Util::throwIfNotType(['string' => [$resource, $id]], true);
        $url = "{$this->_baseUrl}/" . urlencode($resource) . '/' . urlencode($id);
        return $this->_start($url, 'PUT', json_encode($data), ['Content-Type' => 'application/json']);
    }

    /**
     * @see startPut()
     */
    public function put($resource, $id, array $data)
    {
        return $this->end($this->startPut($resource, $id, $data));
    }

    /**
     * Delete an existing instance of an API resource specified by $id
     *
     * @param string $resource
     * @param string $id
     * @param array $data
     *
     * @return mixed opaque handle to be given to endDelete()
     */
    public function startDelete($resource, $id, array $data = null)
    {
        Util::throwIfNotType(['string' => [$resource, $id]], true);
        $url = "{$this->_baseUrl}/" . urlencode($resource) . '/' . urlencode($id);
        $json = $data !== null ? json_encode($data) : null;
        return $this->_start($url, 'DELETE', $json, ['Content-Type' => 'application/json']);
    }

    /**
     * @see startDelete()
     */
    public function delete($resource, $id, array $data = null)
    {
        return $this->end($this->startDelete($resource, $id, $data));
    }

    /**
     * Get response of start*() method
     *
     * @param mixed $handle opaque handle from start*()
     *
     * @return Response
     */
    public function end($handle)
    {
        Util::throwIfNotType(['int' => [$handle]]);
        Util::ensure(true, array_key_exists($handle, $this->_handles), '\InvalidArgumentException', ['$handle not found']);

        list($cachedResponse, $adapterHandle, $request) = $this->_handles[$handle];
        unset($this->_handles[$handle]);

        if ($cachedResponse !== null) {
            return $cachedResponse;
        }

        $response = $this->_adapter->end($adapterHandle);

        if (self::_isExpiredToken($response)) {
            $this->_refreshAccessToken();
            $headers = $request->getHeaders();
            $headers['Authorization'] = "Bearer {$this->_accessToken}";
            $request = new Request($request->getUrl(), $request->getMethod(), $request->getBody(), $headers);
            $response = $this->_adapter->end($this->_adapter->start($request));
        }

        if (($this->_cacheMode === self::CACHE_MODE_REFRESH || $this->_cacheMode & self::CACHE_MODE_GET) && $request->getMethod() === 'GET') {
            $this->_cache->set($request, $response);
        }

        return $response;
    }

    /**
     * Set the default headers
     *
     * @param array The default headers
     *
     * @return void
     */
    public function setDefaultHeaders($defaultHeaders)
    {
        $this->_defaultHeaders = $defaultHeaders;
    }

    private static function _isExpiredToken(Response $response)
    {
        if ($response->getHttpCode() !== 401) {
            return false;
        }

        $parsedJson = $response->getResponse();
        $error = Arrays::get($parsedJson, 'error');

        if (is_array($error)) {
            $error = Arrays::get($error, 'code');
        }

        //This detects expired access tokens on Apigee
        if ($error !== null) {
            return $error === 'invalid_grant' || $error === 'invalid_token';
        }

        $fault = Arrays::get($parsedJson, 'fault');
        if ($fault === null) {
            return false;
        }

        $error = strtolower(Arrays::get($fault, 'faultstring', ''));

        return $error === 'invalid access token' || $error === 'access token expired';
    }

    /**
     * Obtains a new access token from the API
     *
     * @return void
     */
    private function _refreshAccessToken()
    {
        $request = $this->_authentication->getTokenRequest($this->_baseUrl, $this->_refreshToken);
        $response = $this->_adapter->end($this->_adapter->start($request));

        list($this->_accessToken, $this->_refreshToken, $expires) = Authentication::parseTokenResponse($response);

        if ($this->_cache === self::CACHE_MODE_REFRESH || $this->_cacheMode & self::CACHE_MODE_TOKEN) {
            $this->_cache->set($request, $response, $expires);
        }
    }

    /**
     * Helper method to set this clients access token from cache
     *
     * @return void
     */
    private function _setTokenFromCache()
    {
        if (($this->_cacheMode & self::CACHE_MODE_TOKEN) === 0) {
            return;
        }

        $cachedResponse = $this->_cache->get(
            $this->_authentication->getTokenRequest($this->_baseUrl, $this->_refreshToken)
        );
        if ($cachedResponse === null) {
            return;
        }

        list($this->_accessToken, $this->_refreshToken, ) = Authentication::parseTokenResponse($cachedResponse);
    }

    /**
     * Calls adapter->start() using caches
     *
     * @param string $url
     * @param string $method
     * @param string|null $body
     * @param array $headers Authorization key will be overwritten with the bearer token, and Accept-Encoding wil be overwritten with gzip.
     *
     * @return mixed opaque handle to be given to end()
     */
    private function _start($url, $method, $body = null, array $headers = [])
    {
        $headers += $this->_defaultHeaders;
        $headers['Accept-Encoding'] = 'gzip';
        if ($this->_accessToken === null) {
            $this->_setTokenFromCache();
        }

        if ($this->_accessToken === null) {
            $this->_refreshAccessToken();
        }

        $headers['Authorization'] = "Bearer {$this->_accessToken}";

        $request = new Request($url, $method, $body, $headers);

        if ($request->getMethod() === 'GET' && $this->_cacheMode & self::CACHE_MODE_GET) {
            $cached = $this->_cache->get($request);
            if ($cached !== null) {
                $this->_handles[] = [$cached, null, $request];
                end($this->_handles);
                return key($this->_handles);
            }
        }

        $this->_handles[] = [null, $this->_adapter->start($request), $request];
        end($this->_handles);
        return key($this->_handles);
    }
}
