<?php

namespace TraderInteractive\Api;

/**
 * Unit tests for the Collection class
 *
 * @coversDefaultClass \TraderInteractive\Api\Authentication
 * @covers ::<private>
 * @uses \TraderInteractive\Api\Request
 * @uses \TraderInteractive\Api\Response
 */
final class AuthenticationTest extends \PHPUnit_Framework_TestCase
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
    public function getTokenRequest_clientCredentials()
    {
        $auth = Authentication::createClientCredentials('id', 'secret');
        $request = $auth->getTokenRequest('baseUrl', null);
        $this->assertSame('baseUrl/token', $request->getUrl());
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('client_id=id&client_secret=secret&grant_type=client_credentials', $request->getBody());
        $this->assertSame(['Content-Type' => 'application/x-www-form-urlencoded'], $request->getHeaders());
    }

    /**
     * @test
     * @covers ::createClientCredentials
     * @covers ::getTokenRequest
     */
    public function getTokenRequest_clientCredentialsWithRefreshToken()
    {
        $auth = Authentication::createClientCredentials('id', 'secret');
        $request = $auth->getTokenRequest('baseUrl', 'theRefreshToken');
        $this->assertSame('baseUrl/token', $request->getUrl());
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('client_id=id&client_secret=secret&refresh_token=theRefreshToken&grant_type=refresh_token', $request->getBody());
        $this->assertSame(['Content-Type' => 'application/x-www-form-urlencoded'], $request->getHeaders());
    }

    /**
     * @test
     * @covers ::createOwnerCredentials
     */
    public function createOwnerCredentials()
    {
        $auth = Authentication::createOwnerCredentials('not under test', 'not under test', 'not under test', 'not under test');
        $this->assertInstanceOf('\TraderInteractive\Api\Authentication', $auth);
    }

    /**
     * @test
     * @covers ::createOwnerCredentials
     * @covers ::getTokenRequest
     */
    public function getTokenRequest_ownerCredentials()
    {
        $auth = Authentication::createOwnerCredentials('id', 'secret', 'username', 'password');
        $request = $auth->getTokenRequest('baseUrl', null);
        $this->assertSame('baseUrl/token', $request->getUrl());
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('client_id=id&client_secret=secret&username=username&password=password&grant_type=password', $request->getBody());
        $this->assertSame(['Content-Type' => 'application/x-www-form-urlencoded'], $request->getHeaders());
    }

    /**
     * @test
     * @covers ::createOwnerCredentials
     * @covers ::getTokenRequest
     */
    public function getTokenRequest_ownerCredientialsWithRefreshToken()
    {
        $auth = Authentication::createOwnerCredentials('id', 'secret', 'notUnderTest', 'notUnderTest');
        $request = $auth->getTokenRequest('baseUrl', 'theRefreshToken');
        $this->assertSame('baseUrl/token', $request->getUrl());
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('client_id=id&client_secret=secret&refresh_token=theRefreshToken&grant_type=refresh_token', $request->getBody());
        $this->assertSame(['Content-Type' => 'application/x-www-form-urlencoded'], $request->getHeaders());
    }

    /**
     * @test
     * @covers ::parseTokenResponse
     */
    public function parseTokenResponse_noRefreshToken()
    {
        $response = new Response(200, ['Content-Type' => ['application/json']], ['access_token' => 'theAccessToken', 'expires_in' => 1]);

        list($actualToken, $actualRefreshToken, $actualExpires) = Authentication::parseTokenResponse($response);

        $this->assertSame('theAccessToken', $actualToken);
        $this->assertNull($actualRefreshToken);
        $this->assertSame(2, $actualExpires);
    }

    /**
     * @test
     * @covers ::parseTokenResponse
     */
    public function parseTokenResponse_withRefreshToken()
    {
        $response = new Response(
            200,
            ['Content-Type' => ['application/json']],
            ['access_token' => 'theAccessToken', 'expires_in' => 1, 'refresh_token' => 'theRefreshToken']
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
    public function getTokenRequest_clientCredentialsCustomTokenResource()
    {
        $auth = Authentication::createClientCredentials('id', 'secret', 'token', 'custom');
        $request = $auth->getTokenRequest('baseUrl', null);
        $this->assertSame('baseUrl/custom', $request->getUrl());
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('client_id=id&client_secret=secret&grant_type=client_credentials', $request->getBody());
        $this->assertSame(['Content-Type' => 'application/x-www-form-urlencoded'], $request->getHeaders());
    }
}

function time()
{
    return 1;
}
