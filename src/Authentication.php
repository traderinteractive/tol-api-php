<?php
namespace TraderInteractive\Api;

use TraderInteractive\Util;
use TraderInteractive\Util\Arrays;
use TraderInteractive\Util\Http;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

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
    private $getTokenRequestFunc;

    /**
     * Private constructor to safeguard undeclared functions
     *
     * @param callable $getTokenRequestFunc Function to create a Request object for obtaining a new token from the API
     */
    private function __construct(callable $getTokenRequestFunc)
    {
        $this->getTokenRequestFunc = $getTokenRequestFunc;
    }

    /**
     * Creates a new instance of Authentication for Client Credentials grant type
     *
     * @param string $clientId The oauth client id
     * @param string $clientSecret The oauth client secret
     * @param string $refreshResource The refresh token resource of the API
     *     Only needed since apigee doesnt use the token resource that is in the oauth2 spec
     *
     * @return Authentication
     */
    public static function createClientCredentials(
        string $clientId,
        string $clientSecret,
        string $refreshResource = 'token',
        string $tokenResource = 'token'
    ) : Authentication {
        $getTokenRequestFunc = function (
            string $baseUrl,
            string $refreshToken = null
        ) use (
            $clientId,
            $clientSecret,
            $refreshResource,
            $tokenResource
        ) {
            if ($refreshToken !== null) {
                return self::getRefreshTokenRequest(
                    $baseUrl,
                    $clientId,
                    $clientSecret,
                    $refreshResource,
                    $refreshToken
                );
            }

            $data = ['client_id' => $clientId, 'client_secret' => $clientSecret, 'grant_type' => 'client_credentials'];
            return new Request(
                'POST',
                "{$baseUrl}/{$tokenResource}",
                ['Content-Type' => 'application/x-www-form-urlencoded'],
                Http::buildQueryString($data)
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
     * @return Authentication
     */
    public static function createOwnerCredentials(
        string $clientId,
        string $clientSecret,
        string $username,
        string $password,
        string $refreshResource = 'token',
        string $tokenResource = 'token'
    ) : Authentication {
        $getTokenRequestFunc = function (
            string $baseUrl,
            string $refreshToken = null
        ) use (
            $clientId,
            $clientSecret,
            $username,
            $password,
            $refreshResource,
            $tokenResource
        ) {
            if ($refreshToken !== null) {
                return self::getRefreshTokenRequest(
                    $baseUrl,
                    $clientId,
                    $clientSecret,
                    $refreshResource,
                    $refreshToken
                );
            }

            $data = [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'username' => $username,
                'password' => $password,
                'grant_type' => 'password',
            ];
            return new Request(
                'POST',
                "{$baseUrl}/{$tokenResource}",
                ['Content-Type' => 'application/x-www-form-urlencoded'],
                Http::buildQueryString($data)
            );
        };

        return new self($getTokenRequestFunc);
    }

    /**
     * Extracts an access token from the given API response
     *
     * @param ResponseInterface $response The API response containing the access token
     *
     * @return array Array containing the access token, refresh token and expires timestamp
     */
    public static function parseTokenResponse(ResponseInterface $response)
    {
        $parsedJson = json_decode((string)$response->getBody(), true);
        Util::ensureNot('invalid_client', Arrays::get($parsedJson, 'error'), 'Invalid Credentials');
        Util::ensure(
            200,
            $response->getStatusCode(),
            Arrays::get($parsedJson, 'error_description', 'Unknown API error')
        );
        return [
            $parsedJson['access_token'],
            Arrays::get($parsedJson, 'refresh_token'),
            time() + (int)$parsedJson['expires_in'],
        ];
    }

    /**
     * Creates a Request object for obtaining a new token from the API
     *
     * @param string      $baseUrl      The base url of the API
     * @param string|null $refreshToken The refresh token of the API
     *
     * @return RequestInterface
     */
    public function getTokenRequest(string $baseUrl, string $refreshToken = null) : RequestInterface
    {
        return call_user_func($this->getTokenRequestFunc, $baseUrl, $refreshToken);
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
     * @return RequestInterface The built token refresh request
     */
    private static function getRefreshTokenRequest(
        string $baseUrl,
        string $clientId,
        string $clientSecret,
        string $refreshResource,
        string $refreshToken
    ) : RequestInterface {
        //NOTE client_id and client_secret are needed for Apigee but are not in the oauth2 spec
        $data = [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ];

        //NOTE the oauth2 spec says the refresh resource should be the same as the token resource, which is impossible
        //in Apigee and why the $refreshResource variable exists
        return new Request(
            'POST',
            "{$baseUrl}/{$refreshResource}",
            ['Content-Type' => 'application/x-www-form-urlencoded'],
            Http::buildQueryString($data)
        );
    }
}
