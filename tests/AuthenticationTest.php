<?php

namespace TraderInteractive\Api;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Collection class
 *
 * @coversDefaultClass \TraderInteractive\Api\Authentication
 * @covers ::<private>
 */
final class AuthenticationTest extends TestCase
{
    /**
     * @test
     * @covers ::createClientCredentials
     */
    public function createClientCredentials()
    {
        $auth = Authentication::createClientCredentials('not under test', 'not under test');
        $this->assertInstanceOf('\TraderInteractive\Api\Authentication', $auth);
    }

    /**
     * @test
     * @covers ::createClientCredentials
     * @covers ::getTokenRequest
     */
    public function getTokenRequestClientCredentials()
    {
        $auth = Authentication::createClientCredentials('id', 'secret');
        $request = $auth->getTokenRequest('baseUrl', null);
        $this->assertSame('baseUrl/token', (string)$request->getUri());
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame(
            'client_id=id&client_secret=secret&grant_type=client_credentials',
            (string)$request->getBody()
        );
        $this->assertSame(['Content-Type' => ['application/x-www-form-urlencoded']], $request->getHeaders());
    }

    /**
     * @test
     * @covers ::createClientCredentials
     * @covers ::getTokenRequest
     */
    public function getTokenRequestClientCredentialsWithRefreshToken()
    {
        $auth = Authentication::createClientCredentials('id', 'secret');
        $request = $auth->getTokenRequest('baseUrl', 'theRefreshToken');
        $this->assertSame('baseUrl/token', (string)$request->getUri());
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame(
            'client_id=id&client_secret=secret&refresh_token=theRefreshToken&grant_type=refresh_token',
            (string)$request->getBody()
        );
        $this->assertSame(['Content-Type' => ['application/x-www-form-urlencoded']], $request->getHeaders());
    }

    /**
     * @test
     * @covers ::createOwnerCredentials
     */
    public function createOwnerCredentials()
    {
        $auth = Authentication::createOwnerCredentials(
            'not under test',
            'not under test',
            'not under test',
            'not under test'
        );
        $this->assertInstanceOf('\TraderInteractive\Api\Authentication', $auth);
    }

    /**
     * @test
     * @covers ::createOwnerCredentials
     * @covers ::getTokenRequest
     */
    public function getTokenRequestOwnerCredentials()
    {
        $auth = Authentication::createOwnerCredentials('id', 'secret', 'username', 'password');
        $request = $auth->getTokenRequest('baseUrl', null);
        $this->assertSame('baseUrl/token', (string)$request->getUri());
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame(
            'client_id=id&client_secret=secret&username=username&password=password&grant_type=password',
            (string)$request->getBody()
        );
        $this->assertSame(['Content-Type' => ['application/x-www-form-urlencoded']], $request->getHeaders());
    }

    /**
     * @test
     * @covers ::createOwnerCredentials
     * @covers ::getTokenRequest
     */
    public function getTokenRequestOwnerCredientialsWithRefreshToken()
    {
        $auth = Authentication::createOwnerCredentials('id', 'secret', 'notUnderTest', 'notUnderTest');
        $request = $auth->getTokenRequest('baseUrl', 'theRefreshToken');
        $this->assertSame('baseUrl/token', (string)$request->getUri());
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame(
            'client_id=id&client_secret=secret&refresh_token=theRefreshToken&grant_type=refresh_token',
            (string)$request->getBody()
        );
        $this->assertSame(['Content-Type' => ['application/x-www-form-urlencoded']], $request->getHeaders());
    }

    /**
     * @test
     * @covers ::parseTokenResponse
     */
    public function parseTokenResponseNoRefreshToken()
    {
        $response = new Response(
            200,
            ['Content-Type' => ['application/json']],
            json_encode(['access_token' => 'theAccessToken', 'expires_in' => 1])
        );

        list($actualToken, $actualRefreshToken, $actualExpires) = Authentication::parseTokenResponse($response);

        $this->assertSame('theAccessToken', $actualToken);
        $this->assertNull($actualRefreshToken);
        $this->assertSame(2, $actualExpires);
    }

    /**
     * @test
     * @covers ::parseTokenResponse
     */
    public function parseTokenResponseWithRefreshToken()
    {
        $response = new Response(
            200,
            ['Content-Type' => ['application/json']],
            json_encode(['access_token' => 'theAccessToken', 'expires_in' => 1, 'refresh_token' => 'theRefreshToken'])
        );

        list($actualToken, $actualRefreshToken, $actualExpires) = Authentication::parseTokenResponse($response);

        $this->assertSame('theAccessToken', $actualToken);
        $this->assertSame('theRefreshToken', $actualRefreshToken);
        $this->assertSame(2, $actualExpires);
    }

    /**
     * @test
     * @covers ::createClientCredentials
     * @covers ::getTokenRequest
     */
    public function getTokenRequestClientCredentialsCustomTokenResource()
    {
        $auth = Authentication::createClientCredentials('id', 'secret', 'token', 'custom');
        $request = $auth->getTokenRequest('baseUrl', null);
        $this->assertSame('baseUrl/custom', (string)$request->getUri());
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame(
            'client_id=id&client_secret=secret&grant_type=client_credentials',
            (string)$request->getBody()
        );
        $this->assertSame(['Content-Type' => ['application/x-www-form-urlencoded']], $request->getHeaders());
    }

    /**
     * @test
     * @covers ::createApiGatewayClientCredentials
     */
    public function createApiGatewayClientCredentials()
    {
        $auth = Authentication::createApiGatewayClientCredentials('not under test', 'not under test', 'http://auth');
        $this->assertInstanceOf('\TraderInteractive\Api\Authentication', $auth);
    }

    /**
     * @test
     * @covers ::createApiGatewayClientCredentials
     * @covers ::getTokenRequest
     */
    public function getTokenRequestApiGatewayClientCredentials()
    {
        $auth = Authentication::createApiGatewayClientCredentials('id', 'secret', 'authUrl');
        $request = $auth->getTokenRequest('baseUrl');
        $this->assertSame('authUrl/oauth2/token', (string)$request->getUri());
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame(
            'client_id=id&client_secret=secret&grant_type=client_credentials',
            (string)$request->getBody()
        );
        $this->assertSame(['Content-Type' => ['application/x-www-form-urlencoded']], $request->getHeaders());
    }
}

function time()
{
    return 1;
}
