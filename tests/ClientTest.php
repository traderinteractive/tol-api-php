<?php

namespace TraderInteractive\Api;

use DominionEnterprises\Util\Arrays;
use DominionEnterprises\Util\Http;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Client class
 *
 * @coversDefaultClass \TraderInteractive\Api\Client
 * @covers ::<private>
 */
final class ClientTest extends TestCase
{
    /**
     * @test
     * @covers ::getTokens
     */
    public function getTokensNoCall()
    {
        $auth = Authentication::createClientCredentials('not under test', 'not under test');
        $client = new Client(new FakeAdapter(), $auth, 'a url');
        $this->assertSame([null, null], $client->getTokens());
    }

    /**
     * @test
     * @covers ::getTokens
     */
    public function getTokensWithCall()
    {
        $auth = Authentication::createClientCredentials('not under test', 'not under test');
        $client = new Client(new AccessTokenAdapter(), $auth, 'a url');
        $this->assertSame(200, $client->end($client->startIndex('a resource', []))->getHttpCode());
        $this->assertSame([1, null], $client->getTokens());
    }

    /**
     * @test
     * @group unit
     * @covers ::end
     * @expectedException Exception
     * @expectedExceptionMessage Invalid Credentials
     */
    public function exceptionIsThrownOnBadCredentials()
    {
        $adapter = new AccessTokenInvalidClientAdapter();
        $authentication = Authentication::createClientCredentials('not under test', 'not under test');
        $client = new Client($adapter, $authentication, 'a url');
        $client->end($client->startIndex('a resource', []))->getHttpCode();
    }

    /**
     * @test
     * @group unit
     * @covers ::end
     */
    public function invalidTokenIsRefreshed()
    {
        $adapter = new InvalidAccessTokenAdapter();
        $authentication = Authentication::createClientCredentials('not under test', 'not under test');
        $client = new Client($adapter, $authentication, 'a url', Client::CACHE_MODE_NONE, null, 'foo');
        $this->assertSame(200, $client->end($client->startIndex('a resource', []))->getHttpCode());
    }

    /**
     * @test
     * @group unit
     * @covers ::setDefaultHeaders
     */
    public function defaultHeadersArePassed()
    {
        $adapter = $this->getMockBuilder('\TraderInteractive\Api\Adapter')->setMethods(['start', 'end'])->getMock();
        $adapter->expects($this->once())->method('start')->with(
            $this->callback(
                function ($request) {
                    $this->assertEquals('foo', $request->getHeaders()['testHeader']);
                    return true;
                }
            )
        );
        $adapter->expects($this->once())->method('end')->will(
            $this->returnValue(new Response(200, ['Content-Type' => ['application/json']], []))
        );
        $authentication = Authentication::createClientCredentials('not under test', 'not under test');
        $client = new Client($adapter, $authentication, 'a url', Client::CACHE_MODE_NONE, null, 'foo');
        $client->setDefaultHeaders(['testHeader' => 'foo']);
        $this->assertSame(200, $client->end($client->startIndex('a resource', []))->getHttpCode());
    }

    /**
     * @test
     * @group unit
     * @covers ::end
     */
    public function tokenIsRefreshedWith401()
    {
        $adapter = new AccessTokenAdapter();
        $authentication = Authentication::createClientCredentials('not under test', 'not under test');
        $client = new Client($adapter, $authentication, 'a url');
        $this->assertSame(200, $client->end($client->startIndex('a resource', []))->getHttpCode());
    }

    /**
     * @test
     * @group unit
     * @covers ::end
     */
    public function tokenIsRefreshedUsingRefreshTokenWith401()
    {
        $adapter = new RefreshTokenAdapter();
        $authentication = Authentication::createClientCredentials('not under test', 'not under test');
        $client = new Client($adapter, $authentication, 'a url');
        $this->assertSame(200, $client->end($client->startIndex('a resource', []))->getHttpCode());
    }

    /**
     * @test
     * @group unit
     * @covers ::end
     */
    public function tokenIsNotRefreshedOnOtherFault()
    {
        $adapter = new ApigeeOtherFaultAdapter();
        $authentication = Authentication::createClientCredentials('not under test', 'not under test');
        $client = new Client($adapter, $authentication, 'a url');
        $this->assertSame(401, $client->end($client->startIndex('a resource', []))->getHttpCode());
    }

    /**
     * @test
     * @group unit
     * @covers ::end
     */
    public function tokenIsRefreshedWith401OnApigee()
    {
        $adapter = new ApigeeRefreshTokenAdapter();
        $authentication = Authentication::createClientCredentials('not under test', 'not under test');
        $client = new Client($adapter, $authentication, 'a url');
        $this->assertSame(200, $client->end($client->startIndex('a resource', []))->getHttpCode());
    }

    /**
     * @test
     * @group unit
     * @covers ::end
     */
    public function tokenIsRefreshedWith401OnApigeeWithOtherMessage()
    {
        $adapter = new ApigeeRefreshToken2Adapter();
        $authentication = Authentication::createClientCredentials('not under test', 'not under test');
        $client = new Client($adapter, $authentication, 'a url');
        $this->assertSame(200, $client->end($client->startIndex('a resource', []))->getHttpCode());
    }

    /**
     * @test
     * @group unit
     * @covers ::__construct
     * @expectedException \Exception
     */
    public function throwsWithHttpCodeNot200()
    {
        $adapter = new ErrorResponseAdapter();
        $authentication = Authentication::createClientCredentials('not under test', 'not under test');
        $client = new Client($adapter, $authentication, 'a url');
        $client->get('notUnderTest', 'notUnderTest');
    }

    /**
     * @test
     * @covers ::index
     * @covers ::startIndex
     */
    public function index()
    {
        $authentication = Authentication::createClientCredentials('not under test', 'not under test');
        $client = new Client(new IndexAdapter(), $authentication, 'baseUrl/v1');

        $response = $client->index('resource name', ['the name' => 'the value']);

        $this->assertSame(200, $response->getHttpCode());
        $this->assertSame(['key' => 'value'], $response->getResponse());
        $this->assertSame(['Content-Type' => ['application/json']], $response->getResponseHeaders());
    }

    /**
     * @test
     * @covers ::get
     * @covers ::startGet
     */
    public function get()
    {
        $authentication = Authentication::createClientCredentials('not under test', 'not under test');
        $client = new Client(new GetAdapter(), $authentication, 'baseUrl/v1');

        $response = $client->get('resource name', 'the id');

        $this->assertSame(200, $response->getHttpCode());
        $this->assertSame(['key' => 'value'], $response->getResponse());
        $this->assertSame(['Content-Type' => ['application/json']], $response->getResponseHeaders());
    }

    /**
     * @test
     * @covers ::get
     * @covers ::startGet
     */
    public function getWithParameters()
    {
        $authentication = Authentication::createClientCredentials('not under test', 'not under test');
        $client = new Client(new GetWithParametersAdapter(), $authentication, 'baseUrl/v1');

        $response = $client->get('resource name', 'the id', ['foo' => 'bar']);

        $this->assertSame(200, $response->getHttpCode());
        $this->assertSame(['key' => 'value'], $response->getResponse());
        $this->assertSame(['Content-Type' => ['application/json']], $response->getResponseHeaders());
    }

    /**
     * @test
     * @covers ::put
     * @covers ::startPut
     */
    public function put()
    {
        $authentication = Authentication::createClientCredentials('not under test', 'not under test');
        $client = new Client(new PutAdapter(), $authentication, 'baseUrl/v1');

        $response = $client->put('resource name', 'the id', ['the key' => 'the value']);

        $this->assertSame(200, $response->getHttpCode());
        $this->assertSame(['key' => 'value'], $response->getResponse());
        $this->assertSame(['Content-Type' => ['application/json']], $response->getResponseHeaders());
    }

    /**
     * @test
     * @covers ::post
     * @covers ::startPost
     */
    public function post()
    {
        $authentication = Authentication::createClientCredentials('not under test', 'not under test');
        $client = new Client(new PostAdapter(), $authentication, 'baseUrl/v1');

        $response = $client->post('resource name', ['the key' => 'the value']);

        $this->assertSame(200, $response->getHttpCode());
        $this->assertSame(['key' => 'value'], $response->getResponse());
        $this->assertSame(['Content-Type' => ['application/json']], $response->getResponseHeaders());
    }

    /**
     * @test
     * @covers ::delete
     * @covers ::startDelete
     */
    public function delete()
    {
        $authentication = Authentication::createClientCredentials('not under test', 'not under test');
        $client = new Client(new DeleteAdapter(), $authentication, 'baseUrl/v1');

        $response = $client->delete('resource name', 'the id');

        $this->assertSame(204, $response->getHttpCode());
        $this->assertSame([], $response->getResponse());
        $this->assertSame(['Content-Type' => ['application/json']], $response->getResponseHeaders());
    }

    /**
     * Verify behavior of startDelete when no id is given.
     *
     * @test
     * @covers ::startDelete
     *
     * @return void
     */
    public function deleteWithoutId()
    {
        $adapter = new DeleteAdapter();
        $authentication = Authentication::createClientCredentials('not under test', 'not under test');
        $client = new Client($adapter, $authentication, 'baseUrl/v1');
        $client->startDelete('resource', null, ['foo' => 'bar']);
        $this->assertSame('baseUrl/v1/resource', $adapter->request->getUrl());
        $this->assertSame('DELETE', $adapter->request->getMethod());
        $this->assertSame(json_encode(['foo' => 'bar']), $adapter->request->getBody());
    }

    /**
     * Verfiy delete creates the request body properly
     *
     * @test
     * @covers ::delete
     * @covers ::startDelete
     */
    public function deleteWithBody()
    {
        $authentication = Authentication::createClientCredentials('not under test', 'not under test');
        $client = new Client(new DeleteAdapter(), $authentication, 'baseUrl/v1');

        $response = $client->delete('resource name', 'the id', ['the key' => 'the value']);

        $this->assertSame(204, $response->getHttpCode());
        $this->assertSame([], $response->getResponse());
        $this->assertSame(['Content-Type' => ['application/json']], $response->getResponseHeaders());
    }

    /**
     * @test
     * @group unit
     * @expectedException \InvalidArgumentException
     * @covers ::get
     * @covers ::startGet
     */
    public function getWithInvalidResource()
    {
        $adapter = new FakeAdapter();
        $authentication = Authentication::createClientCredentials('not under test', 'not under test');
        $client = new Client($adapter, $authentication, 'a url');
        $client->get(null, '3');
    }

    /**
     * @test
     * @group unit
     * @covers ::index
     */
    public function indexWithMultiParameters()
    {
        $adapter = new CheckUrlAdapter();
        $authentication = Authentication::createClientCredentials('not under test', 'not under test');
        $client = new Client($adapter, $authentication, 'url');
        $results = $client->index('resource', ['abc' => ['1$2(3', '4)5*6']]);
        $response = $results->getResponse();
        $this->assertSame('url/resource?abc=1%242%283&abc=4%295%2A6', $response['url']);
    }

    /**
     * @test
     * @group unit
     * @covers ::post
     * @covers ::startPost
     * @expectedException \InvalidArgumentException
     */
    public function postWithInvalidResource()
    {
        $adapter = new FakeAdapter();
        $authentication = Authentication::createClientCredentials('not under test', 'not under test');
        $client = new Client($adapter, $authentication, 'a url');
        $client->post(null, []);
    }

    /**
     * @test
     * @group unit
     * @covers ::put
     * @covers ::startPut
     * @expectedException \InvalidArgumentException
     */
    public function putWithInvalidResource()
    {
        $adapter = new FakeAdapter();
        $authentication = Authentication::createClientCredentials('not under test', 'not under test');
        $client = new Client($adapter, $authentication, 'a url');
        $client->put(null, 'an id', []);
    }

    /**
     * @test
     * @group unit
     * @covers ::put
     * @covers ::startPut
     * @expectedException \InvalidArgumentException
     */
    public function putWithInvalidId()
    {
        $adapter = new FakeAdapter();
        $authentication = Authentication::createClientCredentials('not under test', 'not under test');
        $client = new Client($adapter, $authentication, 'a url');
        $client->put('not under test', ' ', []);
    }

    /**
     * @test
     * @group unit
     * @covers ::delete
     * @covers ::startDelete
     * @expectedException \InvalidArgumentException
     */
    public function deleteWithInvalidResource()
    {
        $adapter = new FakeAdapter();
        $authentication = Authentication::createClientCredentials('not under test', 'not under test');
        $client = new Client($adapter, $authentication, 'a url');
        $client->delete(null, 'an id');
    }

    /**
     * @test
     * @group unit
     * @covers ::delete
     * @covers ::startDelete
     * @expectedException \InvalidArgumentException
     */
    public function deleteWithInvalidId()
    {
        $adapter = new FakeAdapter();
        $authentication = Authentication::createClientCredentials('not under test', 'not under test');
        $client = new Client($adapter, $authentication, 'a url');
        $client->delete('not under test', ' ');
    }

    /**
     * @test
     * @group unit
     * @covers ::get
     * @covers ::startGet
     * @expectedException \InvalidArgumentException
     */
    public function getWithInvalidId()
    {
        $adapter = new FakeAdapter();
        $authentication = Authentication::createClientCredentials('not under test', 'not under test');
        $client = new Client($adapter, $authentication, 'a url');
        $client->get('not under test', " \n ");
    }

    /**
     * @test
     * @group unit
     * @dataProvider constructorBadData
     * @expectedException \InvalidArgumentException
     */
    public function constructWithInvalidParameters($adapter, $authentication, $apiBaseUrl, $cacheMode, $cache)
    {
        $client = new Client($adapter, $authentication, $apiBaseUrl, $cacheMode, $cache);
    }

    /**
     * Data provider for bad constructor data
     *
     * @return array
     */
    public function constructorBadData()
    {
        $adapter = new FakeAdapter();
        $authentication = Authentication::createClientCredentials('not under test', 'not under test');
        $cache = new ArrayCache();

        return [
            // host checks
            '$baseUrl is null' => [$adapter, $authentication, null, Client::CACHE_MODE_ALL, $cache],
            '$baseUrl is empty string' => [$adapter, $authentication, '', Client::CACHE_MODE_ALL, $cache],
            '$baseUrl is whitespace' => [$adapter, $authentication, " \n ", Client::CACHE_MODE_ALL, $cache],
            '$baseUrl is not a string' => [$adapter, $authentication, 123, Client::CACHE_MODE_ALL, $cache],
            // cacheMode checks
            '$cacheMode is not valid constant' => [$adapter, $authentication, 'baseUrl', 42, $cache],
            // cache checks
            '$cache is null with mode ALL' => [$adapter, $authentication, 'baseUrl', Client::CACHE_MODE_ALL, null],
        ];
    }

    /**
     * @test
     * @group unit
     * @covers ::index
     * @covers ::startIndex
     * @expectedException \InvalidArgumentException
     */
    public function indexWithInvalidResource()
    {
        $adapter = new FakeAdapter();
        $authentication = Authentication::createClientCredentials('not under test', 'not under test');
        $client = new Client($adapter, $authentication, 'a url');
        $client->index('');
    }

    /**
     * @test
     * @covers ::__construct
     * @covers ::end
     * @covers ::startGet
     */
    public function getFromCache()
    {
        $cache = new ArrayCache();
        $request = new Request('baseUrl/a+url/id', 'not under test');
        $expected = new Response(200, ['key' => ['value']], ['doesnt' => 'matter']);
        $cache->set($request, $expected);
        $authentication = Authentication::createClientCredentials('not under test', 'not under test');
        $client = new Client(new TokenAdapter(), $authentication, 'baseUrl', Client::CACHE_MODE_GET, $cache);
        $actual = $client->end($client->startGet('a url', 'id'));
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     * @covers ::__construct
     * @covers ::end
     * @covers ::startGet
     */
    public function getDisabledCache()
    {
        $cache = new ArrayCache();
        $request = new Request('baseUrl/a+url/id', 'not under test');
        $unexpected = new Response(200, ['key' => ['value']], ['doesnt' => 'matter']);
        $expected = new Response(200, ['Content-Type' => ['application/json']], []);
        $cache->set($request, $unexpected);
        $authentication = Authentication::createClientCredentials('not under test', 'not under test');
        $adapter = $this->getMockBuilder('\TraderInteractive\Api\Adapter')->setMethods(['start', 'end'])->getMock();
        $adapter->expects($this->once())->method('start');
        $adapter->expects($this->once())->method('end')->will(
            $this->returnValue(new Response(200, ['Content-Type' => ['application/json']], []))
        );
        $client = new Client($adapter, $authentication, 'baseUrl', Client::CACHE_MODE_REFRESH, $cache, 'foo');
        $actual = $client->end($client->startGet('a url', 'id'));
        $this->assertEquals($expected, $actual);
        $this->assertEquals($expected, $cache->get($request));
        $client = new Client(new TokenAdapter(), $authentication, 'baseUrl', Client::CACHE_MODE_GET, $cache);
        $actual = $client->end($client->startGet('a url', 'id'));
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     * @covers ::__construct
     * @covers ::end
     * @covers ::startGet
     */
    public function getTokenNotInCache()
    {
        $authentication = Authentication::createClientCredentials('not under test', 'not under test');
        $client = new Client(
            new GetAdapter(),
            $authentication,
            'baseUrl/v1',
            Client::CACHE_MODE_TOKEN,
            new ArrayCache()
        );

        $response = $client->end($client->startGet('resource name', 'the id'));

        $this->assertSame(200, $response->getHttpCode());
        $this->assertSame(['key' => 'value'], $response->getResponse());
        $this->assertSame(['Content-Type' => ['application/json']], $response->getResponseHeaders());
    }

    /**
     * @test
     * @covers ::end
     */
    public function setCache()
    {
        $cache = new ArrayCache();
        $authentication = Authentication::createClientCredentials('not under test', 'not under test');
        $client = new Client(new CacheAdapter(), $authentication, 'baseUrl', Client::CACHE_MODE_GET, $cache);
        $expected = $client->end($client->startGet('a url', 'id'));
        $actual = $cache->cache['baseUrl/a+url/id'];
        $this->assertEquals($expected, $actual);
    }

    /**
     * Verify client uses in memory token only if originially pulled from cache
     *
     * @test
     */
    public function validTokenInMemory()
    {
        $cache = new ArrayCache();
        $authentication = Authentication::createClientCredentials('not under test', 'not under test');
        $request = $authentication->getTokenRequest('baseUrl', 'token', null);
        $cache->set(
            $request,
            new Response(
                200,
                ['Content-Type' => ['application/json']],
                ['access_token' => 'an access token', 'expires_in' => 1]
            )
        );
        $client = new Client(new NoTokenAdapter(), $authentication, 'baseUrl', Client::CACHE_MODE_TOKEN, $cache);
        // no token requests should be made
        $this->assertSame(['a body'], $client->index('foos')->getResponse());
        // empty the cache
        $cache->cache = [];
        // no token requests should be made with second  request
        $this->assertSame(['a body'], $client->index('foos')->getResponse());
    }
}
