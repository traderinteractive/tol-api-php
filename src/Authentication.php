<?php
namespace DominionEnterprises\Api;
use DominionEnterprises\Util;
use DominionEnterprises\Util\Arrays;
use DominionEnterprises\Util\Http;

/**
 * Layer for OAuth2 Authentication
 */
final class Authentication
{
    /**
     * Function to create a Request object for obtaining a new token from the API
     *
     * @var callable
     */
    private $_getTokenRequestFunc;

    /**
     * Private constructor to safeguard undeclared functions
     *
     * @param callable $getTokenRequestFunc Function to create a Request object for obtaining a new token from the API
     */
    private function __construct(callable $getTokenRequestFunc)
    {
        $this->_getTokenRequestFunc = $getTokenRequestFunc;
    }

    /**
     * Creates a new instance of Authentication for Client Credentials grant type
     *
     * @param string $clientId The oauth client id
     * @param string $clientSecret The oauth client secret
     * @param string $refreshResource The refresh token resource of the API
     *     Only needed since apigee doesnt use the token resource that is in the oauth2 spec
     *
     * @return \DominionEnterprises\Api\Authentication
     */
    public static function createClientCredentials($clientId, $clientSecret, $refreshResource = 'token', $tokenResource = 'token')
    {
        Util::throwIfNotType(['string' => [$clientId, $clientSecret]], true);

        $getTokenRequestFunc = function($baseUrl, $refreshToken) use ($clientId, $clientSecret, $refreshResource, $tokenResource) {
            if ($refreshToken !== null) {
                return self::_getRefreshTokenRequest($baseUrl, $clientId, $clientSecret, $refreshResource, $refreshToken);
            }

            $data = ['client_id' => $clientId, 'client_secret' => $clientSecret, 'grant_type' => 'client_credentials'];
            return new Request(
                "{$baseUrl}/{$tokenResource}",
                'POST',
                Http::buildQueryString($data),
                ['Content-Type' => 'application/x-www-form-urlencoded']
            );
        };

        return new self($getTokenRequestFunc);
    }

    /**
     * Creates a new instance of Authentication for Owner Credentials grant type
     *
     * @param string $clientId The oauth client id
     * @param string $clientSecret The oauth client secret
     * @param string $username The oauth username
     * @param string $password The oauth password
     * @param string $refreshResource The refresh token resource of the API
     *     Only needed since apigee doesnt use the token resource that is in the oauth2 spec
     *
     * @return \DominionEnterprises\Api\Authentication
     */
    public static function createOwnerCredentials(
        $clientId,
        $clientSecret,
        $username,
        $password,
        $refreshResource = 'token',
        $tokenResource = 'token'
    )
    {
        Util::throwIfNotType(['string' => [$clientId, $clientSecret, $username, $password]], true);

        $getTokenRequestFunc = function($baseUrl, $refreshToken)
        use ($clientId, $clientSecret, $username, $password, $refreshResource, $tokenResource) {
            if ($refreshToken !== null) {
                return self::_getRefreshTokenRequest($baseUrl, $clientId, $clientSecret, $refreshResource, $refreshToken);
            }

            $data = [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'username' => $username,
                'password' => $password,
                'grant_type' => 'password',
            ];
            return new Request(
                "{$baseUrl}/{$tokenResource}",
                'POST',
                Http::buildQueryString($data),
                ['Content-Type' => 'application/x-www-form-urlencoded']
            );
        };

        return new self($getTokenRequestFunc);
    }

    /**
     * Extracts an access token from the given API response
     *
     * @param \DominionEnterprises\Api\Response $response The API response containing the access token
     *
     * @return array Array containing the access token, refresh token and expires timestamp
     */
    public static function parseTokenResponse(Response $response)
    {
        $parsedJson = $response->getResponse();
        Util::ensureNot('invalid_client', Arrays::get($parsedJson, 'error'), 'Invalid Credentials');
        Util::ensure(200, $response->getHttpCode(), Arrays::get($parsedJson, 'error_description', 'Unknown API error'));
        return [$parsedJson['access_token'], Arrays::get($parsedJson, 'refresh_token'), time() + (int)$parsedJson['expires_in']];
    }

    /**
     * Creates a Request object for obtaining a new token from the API
     *
     * @param string $baseUrl The base url of the API
     * @param string $refreshToken The refresh token of the API
     *
     * @return \DominionEnterprises\Api\Request
     */
    public function getTokenRequest($baseUrl, $refreshToken)
    {
        Util::throwIfNotType(['string' => [$baseUrl]], true);
        Util::throwIfNotType(['string' => [$refreshToken]], true, true);

        return call_user_func($this->_getTokenRequestFunc, $baseUrl, $refreshToken);
    }

    /**
     * Build a refresh token request
     *
     * @param string $baseUrl API base url
     * @param string $clientId The client id
     * @param string $clientSecret The client secret
     * @param string $refreshResource The refresh token resource of the API
     *     Only needed since apigee doesnt use the token resource that is in the oauth2 spec
     * @param string $refreshToken The refresh token of the API
     *
     * @return \DominionEnterprises\Api\Request The built token refresh request
     */
    private static function _getRefreshTokenRequest($baseUrl, $clientId, $clientSecret, $refreshResource, $refreshToken)
    {
        //NOTE client_id and client_secret are needed for Apigee but are not in the oauth2 spec
        $data = ['client_id' => $clientId, 'client_secret' => $clientSecret, 'refresh_token' => $refreshToken, 'grant_type' => 'refresh_token'];

        //NOTE the oauth2 spec says the refresh resource should be the same as the token resource, which is impossible in Apigee and why the
        //$refreshResource variable exists
        return new Request(
            "{$baseUrl}/{$refreshResource}",
            'POST',
            Http::buildQueryString($data),
            ['Content-Type' => 'application/x-www-form-urlencoded']
        );
    }
}
