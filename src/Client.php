<?php

namespace TraderInteractive\Api;

use Chadicus\Psr\SimpleCache\NullCache;
use DominionEnterprises\Util;
use DominionEnterprises\Util\Arrays;
use DominionEnterprises\Util\Http;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;

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
     * @var array
     */
    const CACHE_MODES = [
        self::CACHE_MODE_NONE,
        self::CACHE_MODE_GET,
        self::CACHE_MODE_TOKEN,
        self::CACHE_MODE_ALL,
        self::CACHE_MODE_REFRESH,
    ];

    /**
     * Base url of the API server
     *
     * @var string
     */
    private $baseUrl;

    /**
     * HTTP Adapter for sending request to the api
     *
     * @var AdapterInterface
     */
    private $adapter;

    /**
     * Oauth authentication implementation
     *
     * @var Authentication
     */
    private $authentication;

    /**
     * API access token
     *
     * @var string
     */
    private $accessToken;

    /**
     * API refresh token
     *
     * @var string
     */
    private $refreshToken;

    /**
     * Storage for cached API responses
     *
     * @var CacheInterface
     */
    private $cache;

    /**
     * Strategy for caching
     *
     * @var int
     */
    private $cacheMode;

    /**
     * Handles set in start()
     *
     * @var array like [opaqueKey => [cached response (Response), adapter handle (opaque), Request]]
     */
    private $handles = [];

    /**
     * Array of headers that are passed on every request unless they are overridden
     *
     * @var array
     */
    private $defaultHeaders = [];

    /**
     * Create a new instance of Client
     *
     * @param AdapterInterface $adapter
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
        AdapterInterface $adapter,
        Authentication $authentication,
        string $baseUrl,
        int $cacheMode = self::CACHE_MODE_NONE,
        CacheInterface $cache = null,
        string $accessToken = null,
        string $refreshToken = null
    ) {
        Util::ensure(
            true,
            in_array($cacheMode, self::CACHE_MODES, true),
            '\InvalidArgumentException',
            ['$cacheMode must be a valid cache mode constant']
        );

        $this->adapter = $adapter;
        $this->baseUrl = $baseUrl;
        $this->authentication = $authentication;
        $this->cache = $cache ?? new NullCache();
        $this->cacheMode = $cacheMode;
        $this->accessToken = $accessToken;
        $this->refreshToken = $refreshToken;
    }

    /**
     * Get access token and refresh token
     *
     * @return array two string values, access token and refresh token
     */
    public function getTokens() : array
    {
        return [$this->accessToken, $this->refreshToken];
    }

    /**
     * Search the API resource using the specified $filters
     *
     * @param string $resource
     * @param array $filters
     *
     * @return string opaque handle to be given to endIndex()
     */
    public function startIndex(string $resource, array $filters = []) : string
    {
        $url = "{$this->baseUrl}/" . urlencode($resource) . '?' . Http::buildQueryString($filters);
        return $this->start($url, 'GET');
    }

    /**
     * @see startIndex()
     */
    public function index(string $resource, array $filters = []) : ResponseInterface
    {
        return $this->end($this->startIndex($resource, $filters));
    }

    /**
     * Get the details of an API resource based on $id
     *
     * @param string $resource
     * @param string $id
     * @param array  $parameters
     *
     * @return string opaque handle to be given to endGet()
     */
    public function startGet(string $resource, string $id, array $parameters = []) : string
    {
        $url = "{$this->baseUrl}/" . urlencode($resource) . '/' . urlencode($id);
        if (!empty($parameters)) {
            $url .= '?' . Http::buildQueryString($parameters);
        }

        return $this->start($url, 'GET');
    }

    /**
     * @see startGet()
     */
    public function get(string $resource, string $id, array $parameters = []) : ResponseInterface
    {
        return $this->end($this->startGet($resource, $id, $parameters));
    }

    /**
     * Create a new instance of an API resource using the provided $data
     *
     * @param string $resource
     * @param array $data
     *
     * @return string opaque handle to be given to endPost()
     */
    public function startPost(string $resource, array $data) : string
    {
        $url = "{$this->baseUrl}/" . urlencode($resource);
        return $this->start($url, 'POST', json_encode($data), ['Content-Type' => 'application/json']);
    }

    /**
     * @see startPost()
     */
    public function post(string $resource, array $data) : ResponseInterface
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
     * @return string opaque handle to be given to endPut()
     */
    public function startPut(string $resource, string $id, array $data) : string
    {
        $url = "{$this->baseUrl}/" . urlencode($resource) . '/' . urlencode($id);
        return $this->start($url, 'PUT', json_encode($data), ['Content-Type' => 'application/json']);
    }

    /**
     * @see startPut()
     */
    public function put(string $resource, string $id, array $data) : ResponseInterface
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
     * @return string opaque handle to be given to endDelete()
     */
    public function startDelete(string $resource, string $id = null, array $data = null) : string
    {
        $url = "{$this->baseUrl}/" . urlencode($resource);
        if ($id !== null) {
            $url .= '/' . urlencode($id);
        }

        $json = $data !== null ? json_encode($data) : null;
        return $this->start($url, 'DELETE', $json, ['Content-Type' => 'application/json']);
    }

    /**
     * @see startDelete()
     */
    public function delete(string $resource, string $id = null, array $data = null) : ResponseInterface
    {
        return $this->end($this->startDelete($resource, $id, $data));
    }

    /**
     * Get response of start*() method
     *
     * @param string $handle opaque handle from start*()
     *
     * @return ResponseInterface
     */
    public function end(string $handle) : ResponseInterface
    {
        Util::ensure(
            true,
            array_key_exists($handle, $this->handles),
            '\InvalidArgumentException',
            ['$handle not found']
        );

        list($cachedResponse, $adapterHandle, $request) = $this->handles[$handle];
        unset($this->handles[$handle]);

        if ($cachedResponse !== null) {
            return $cachedResponse;
        }

        $response = $this->adapter->end($adapterHandle);

        if (self::isExpiredToken($response)) {
            $this->refreshAccessToken();
            $headers = $request->getHeaders();
            $headers['Authorization'] = "Bearer {$this->accessToken}";
            $request = new Request($request->getUrl(), $request->getMethod(), $request->getBody(), $headers);
            $response = $this->adapter->end($this->adapter->start($request));
        }

        if (($this->cacheMode === self::CACHE_MODE_REFRESH
                || $this->cacheMode & self::CACHE_MODE_GET)
                && $request->getMethod() === 'GET') {
            $this->cache->set($this->getCacheKey($request), $response);
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
    public function setDefaultHeaders(array $defaultHeaders)
    {
        $this->defaultHeaders = $defaultHeaders;
    }

    private static function isExpiredToken(ResponseInterface $response) : bool
    {
        if ($response->getStatusCode() !== 401) {
            return false;
        }

        $parsedJson = json_decode((string)$response->getBody(), true);
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
    private function refreshAccessToken()
    {
        $request = $this->authentication->getTokenRequest($this->baseUrl, $this->refreshToken);
        $response = $this->adapter->end($this->adapter->start($request));

        list($this->accessToken, $this->refreshToken, $expires) = Authentication::parseTokenResponse($response);

        if ($this->cache === self::CACHE_MODE_REFRESH || $this->cacheMode & self::CACHE_MODE_TOKEN) {
            $this->cache->set($this->getCacheKey($request), $response, $expires);
            return;
        }
    }

    /**
     * Helper method to set this clients access token from cache
     *
     * @return void
     */
    private function setTokenFromCache()
    {
        if (($this->cacheMode & self::CACHE_MODE_TOKEN) === 0) {
            return;
        }

        $cachedResponse = $this->cache->get(
            $this->getCacheKey($this->authentication->getTokenRequest($this->baseUrl, $this->refreshToken))
        );
        if ($cachedResponse === null) {
            return;
        }

        list($this->accessToken, $this->refreshToken, ) = Authentication::parseTokenResponse($cachedResponse);
    }

    /**
     * Calls adapter->start() using caches
     *
     * @param string $url
     * @param string $method
     * @param string|null $body
     * @param array $headers Authorization key will be overwritten with the bearer token, and Accept-Encoding wil be
     *                       overwritten with gzip.
     *
     * @return string opaque handle to be given to end()
     */
    private function start(string $url, string $method, string $body = null, array $headers = [])
    {
        $headers += $this->defaultHeaders;
        $headers['Accept-Encoding'] = 'gzip';
        if ($this->accessToken === null) {
            $this->setTokenFromCache();
        }

        if ($this->accessToken === null) {
            $this->refreshAccessToken();
        }

        $headers['Authorization'] = "Bearer {$this->accessToken}";

        $request = new Request($url, $method, $body, $headers);

        if ($request->getMethod() === 'GET' && $this->cacheMode & self::CACHE_MODE_GET) {
            $cached = $this->cache->get($this->getCacheKey($request));
            if ($cached !== null) {
                //The response is cache. Generate a key for the handles array
                $key = uniqid();
                $this->handles[$key] = [$cached, null, $request];
                return $key;
            }
        }

        $key = $this->adapter->start($request);
        $this->handles[$key] = [null, $key, $request];
        return $key;
    }

    private function getCacheKey(Request $request) : string
    {
        return CacheHelper::getCacheKey($request);
    }
}
