<?php

namespace TraderInteractive\Api;

use ArrayObject;
use Chadicus\Psr\SimpleCache\InMemoryCache;
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
        $client = new Client($this->getAdapter(), $this->getAuthentication(), 'a url');
        $this->assertSame([null, null], $client->getTokens());
    }

    /**
     * @test
     * @covers ::getTokens
     */
    public function getTokensWithCall()
    {
        $client = new Client(new AccessTokenAdapter(), $this->getAuthentication(), 'a url');
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
        $client = new Client($adapter, $this->getAuthentication(), 'a url');
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
        $client = new Client($adapter, $this->getAuthentication(), 'a url', Client::CACHE_MODE_NONE, null, 'foo');
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
        $adapter->expects($this->once())->method('start')->will(
            $this->returnCallback(
                function ($request) {
                    $this->assertEquals('foo', $request->getHeaders()['testHeader']);
                    return uniqid();
                }
            )
        );
        $adapter->expects($this->once())->method('end')->will(
            $this->returnValue(new Response(200, ['Content-Type' => ['application/json']], []))
        );
        $client = new Client($adapter, $this->getAuthentication(), 'a url', Client::CACHE_MODE_NONE, null, 'foo');
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
        $client = new Client($adapter, $this->getAuthentication(), 'a url');
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
        $client = new Client($adapter, $this->getAuthentication(), 'a url');
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
        $client = new Client($adapter, $this->getAuthentication(), 'a url');
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
        $client = new Client($adapter, $this->getAuthentication(), 'a url');
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
        $client = new Client($adapter, $this->getAuthentication(), 'a url');
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
        $client = new Client($adapter, $this->getAuthentication(), 'a url');
        $client->get('notUnderTest', 'notUnderTest');
    }

    /**
     * @test
     * @covers ::index
     * @covers ::startIndex
     */
    public function index()
    {
        $client = new Client(new IndexAdapter(), $this->getAuthentication(), 'baseUrl/v1');

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
        $client = new Client(new GetAdapter(), $this->getAuthentication(), 'baseUrl/v1');

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
        $client = new Client(new GetWithParametersAdapter(), $this->getAuthentication(), 'baseUrl/v1');

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
        $client = new Client(new PutAdapter(), $this->getAuthentication(), 'baseUrl/v1');

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
        $client = new Client(new PostAdapter(), $this->getAuthentication(), 'baseUrl/v1');

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
        $client = new Client(new DeleteAdapter(), $this->getAuthentication(), 'baseUrl/v1');

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
        $client = new Client($adapter, $this->getAuthentication(), 'baseUrl/v1');
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
        $client = new Client(new DeleteAdapter(), $this->getAuthentication(), 'baseUrl/v1');

        $response = $client->delete('resource name', 'the id', ['the key' => 'the value']);

        $this->assertSame(204, $response->getHttpCode());
        $this->assertSame([], $response->getResponse());
        $this->assertSame(['Content-Type' => ['application/json']], $response->getResponseHeaders());
    }

    /**
     * @test
     * @group unit
     * @covers ::index
     */
    public function indexWithMultiParameters()
    {
        $adapter = new CheckUrlAdapter();
        $client = new Client($adapter, $this->getAuthentication(), 'url');
        $results = $client->index('resource', ['abc' => ['1$2(3', '4)5*6']]);
        $response = $results->getResponse();
        $this->assertSame('url/resource?abc=1%242%283&abc=4%295%2A6', $response['url']);
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
        $adapter = $this->getAdapter();
        $authentication = $this->getAuthentication();
        $cache = new InMemoryCache();

        return [
            '$cacheMode is not valid constant' => [$adapter, $authentication, 'baseUrl', 42, $cache],
        ];
    }

    /**
     * @test
     * @covers ::__construct
     * @covers ::end
     * @covers ::startGet
     */
    public function getFromCache()
    {
        $cache = new InMemoryCache();
        $request = new Request('baseUrl/a+url/id', 'not under test');
        $expected = new Response(200, ['key' => ['value']], ['doesnt' => 'matter']);
        $cache->set($this->getCacheKey($request), $expected);
        $client = new Client(new TokenAdapter(), $this->getAuthentication(), 'baseUrl', Client::CACHE_MODE_GET, $cache);
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
        $cache = new InMemoryCache();
        $request = new Request('baseUrl/a+url/id', 'not under test');
        $unexpected = new Response(200, ['key' => ['value']], ['doesnt' => 'matter']);
        $expected = new Response(200, ['Content-Type' => ['application/json']], []);
        $cache->set($this->getCacheKey($request), $unexpected);
        $adapter = $this->getMockBuilder('\TraderInteractive\Api\Adapter')->setMethods(['start', 'end'])->getMock();
        $adapter->expects($this->once())->method('start')->willReturn(uniqid());
        $adapter->expects($this->once())->method('end')->will(
            $this->returnValue(new Response(200, ['Content-Type' => ['application/json']], []))
        );
        $client = new Client(
            $adapter,
            $this->getAuthentication(),
            'baseUrl',
            Client::CACHE_MODE_REFRESH,
            $cache,
            'foo'
        );
        $actual = $client->end($client->startGet('a url', 'id'));
        $this->assertEquals($expected, $actual);
        $this->assertEquals($expected, $cache->get($this->getCacheKey($request)));
        $client = new Client(new TokenAdapter(), $this->getAuthentication(), 'baseUrl', Client::CACHE_MODE_GET, $cache);
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
        $client = new Client(
            new GetAdapter(),
            $this->getAuthentication(),
            'baseUrl/v1',
            Client::CACHE_MODE_TOKEN,
            new InMemoryCache()
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
        $cache = new InMemoryCache();
        $client = new Client(new CacheAdapter(), $this->getAuthentication(), 'baseUrl', Client::CACHE_MODE_GET, $cache);
        $expected = $client->end($client->startGet('a url', 'id'));
        $actual = $cache->get('baseUrl_FSLASH_a+url_FSLASH_id|');
        $this->assertEquals($expected, $actual);
    }

    /**
     * Verify client uses in memory token only if originially pulled from cache
     *
     * @test
     */
    public function validTokenInMemory()
    {
        $cache = new InMemoryCache();
        $authentication = $this->getAuthentication();
        $request = $authentication->getTokenRequest('baseUrl', null);
        $cache->set(
            $this->getCacheKey($request),
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
        $cache->clear();
        // no token requests should be made with second  request
        $this->assertSame(['a body'], $client->index('foos')->getResponse());
    }

    private function getAdapter() : Adapter
    {
        $container = new ArrayObject();
        $startCallback = function (Request $request) use ($container) {
            $container['request'] = $request;
        };

        $endCallback = function (string $handle) use ($container) {
            if (substr_count($this->request->getUrl(), 'token') == 1) {
                $container['token'] = md5(microtime(true));
                return new Response(
                    200,
                    ['Content-Type' => ['application/json']],
                    ['access_token' => $container['token'], 'expires_in' => 1]
                );
            }
        };

        $handle = uniqid();
        $mock = $this->getMockBuilder(Adapter::class)->getMock();
        $mock->method('start')->willReturn($handle);
        $mock->method('end')->willReturn(
            new Response(
                200,
                ['Content-Type' => ['application/json']],
                ['access_token' => 'foo', 'expires_in' => 1]
            )
        );

        return $mock;
    }

    private function getAuthentication() : Authentication
    {
        return Authentication::createClientCredentials('not under test', 'not under test');
    }

    private function getCacheKey(Request $request) : string
    {
        return CacheHelper::getCacheKey($request);
    }
}
