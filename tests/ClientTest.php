<?php

namespace DominionEnterprises\Api;
use DominionEnterprises\Util\Arrays;
use DominionEnterprises\Util\Http;

/**
 * Unit tests for the Client class
 *
 * @coversDefaultClass \DominionEnterprises\Api\Client
 * @covers ::<private>
 * @uses \DominionEnterprises\Api\Client::__construct
 * @uses \DominionEnterprises\Api\Authentication::<private>
 * @uses \DominionEnterprises\Api\Authentication::__construct
 * @uses \DominionEnterprises\Api\Authentication::createClientCredentials
 * @uses \DominionEnterprises\Api\Authentication::parseTokenResponse
 * @uses \DominionEnterprises\Api\Authentication::getTokenRequest
 * @uses \DominionEnterprises\Api\Request
 * @uses \DominionEnterprises\Api\Response
 */
final class ClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @covers ::getTokens
     */
    public function getTokens_noCall()
    {
        $auth = Authentication::createClientCredentials('not under test', 'not under test');
        $client = new Client(new FakeAdapter(), $auth, 'a url');
        $this->assertSame([null, null], $client->getTokens());
    }

    /**
     * @test
     * @covers ::getTokens
     * @uses \DominionEnterprises\Api\Client::startIndex
     * @uses \DominionEnterprises\Api\Client::end
     */
    public function getTokens_withCall()
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
     * @uses \DominionEnterprises\Api\Client::startIndex
     * @uses \DominionEnterprises\Api\Client::end
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
     * @uses \DominionEnterprises\Api\Client::startIndex
     * @uses \DominionEnterprises\Api\Client::end
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
     * @uses \DominionEnterprises\Api\Client::startIndex
     * @uses \DominionEnterprises\Api\Client::end
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
     * @uses \DominionEnterprises\Api\Client::startIndex
     * @uses \DominionEnterprises\Api\Client::end
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
     * @uses \DominionEnterprises\Api\Client::startIndex
     * @uses \DominionEnterprises\Api\Client::end
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
     * @uses \DominionEnterprises\Api\Client::startIndex
     * @uses \DominionEnterprises\Api\Client::end
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
     * @uses \DominionEnterprises\Api\Client::get
     * @uses \DominionEnterprises\Api\Client::startGet
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
     * @uses \DominionEnterprises\Api\Client::end
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
     * @uses \DominionEnterprises\Api\Client::end
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
     * @covers ::put
     * @covers ::startPut
     * @uses \DominionEnterprises\Api\Client::end
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
     * @uses \DominionEnterprises\Api\Client::end
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
     * @uses \DominionEnterprises\Api\Client::end
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
     * Verfiy delete creates the request body properly
     *
     * @test
     * @covers ::delete
     * @covers ::startDelete
     * @uses \DominionEnterprises\Api\Client::end
     */
    public function delete_withBody()
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
    public function get_withInvalidResource()
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
     * @uses \DominionEnterprises\Api\Client::startIndex
     * @uses \DominionEnterprises\Api\Client::end
     */
    public function index_withMultiParameters()
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
    public function post_withInvalidResource()
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
    public function put_withInvalidResource()
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
    public function put_withInvalidId()
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
    public function delete_withInvalidResource()
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
    public function delete_withInvalidId()
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
    public function get_withInvalidId()
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
    public function construct_withInvalidParameters($adapter, $authentication, $apiBaseUrl, $cacheMode, $cache)
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
    public function index_withInvalidResource()
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
    public function get_fromCache()
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
    public function get_tokenNotInCache()
    {
        $authentication = Authentication::createClientCredentials('not under test', 'not under test');
        $client = new Client(new GetAdapter(), $authentication, 'baseUrl/v1', Client::CACHE_MODE_TOKEN, new ArrayCache());

        $response = $client->end($client->startGet('resource name', 'the id'));

        $this->assertSame(200, $response->getHttpCode());
        $this->assertSame(['key' => 'value'], $response->getResponse());
        $this->assertSame(['Content-Type' => ['application/json']], $response->getResponseHeaders());
    }

    /**
     * @test
     * @covers ::end
     * @uses \DominionEnterprises\Api\Client::startGet
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
     * @covers ::_start
     * @uses \DominionEnterprises\Api\Client::index
     * @uses \DominionEnterprises\Api\Client::startIndex
     * @uses \DominionEnterprises\Api\Client::end
     */
    public function validTokenInMemory()
    {
        $cache = new ArrayCache();
        $authentication = Authentication::createClientCredentials('not under test', 'not under test');
        $request = $authentication->getTokenRequest('baseUrl', 'token', null);
        $cache->set(
            $request,
            new Response(200, ['Content-Type' => ['application/json']], ['access_token' => 'an access token', 'expires_in' => 1])
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

final class NoTokenAdapter implements Adapter
{
    private $_request;
    public function start(Request $request)
    {
        $this->_request = $request;
    }

    public function end($handle)
    {
        if (substr($this->_request->getUrl(), -5) === 'foos?') {
            return new Response(200, ['Content-Type' => ['application/json']], ['a body']);
        }

        throw new \Exception('Unexpected request');
    }
}

final class IndexAdapter implements Adapter
{
    private $_request;

    public function start(Request $request)
    {
        $this->_request = $request;
    }

    public function end($handle)
    {
        if (substr($this->_request->getUrl(), -5) === 'token') {
            return new Response(200, ['Content-Type' => ['application/json']], ['access_token' => 'a token', 'expires_in' => 1]);
        }

        if ($this->_request->getMethod() === 'GET' && $this->_request->getUrl() === 'baseUrl/v1/resource+name?the+name=the+value') {
            return new Response(200, ['Content-Type' => ['application/json']], ['key' => 'value']);
        }

        throw new \Exception('Unexpected request');
    }
}

final class GetAdapter implements Adapter
{
    private $_request;

    public function start(Request $request)
    {
        $this->_request = $request;
    }

    public function end($handle)
    {
        if (substr($this->_request->getUrl(), -5) === 'token') {
            return new Response(200, ['Content-Type' => ['application/json']], ['access_token' => 'a token', 'expires_in' => 1]);
        }

        if ($this->_request->getMethod() === 'GET' && $this->_request->getUrl() === 'baseUrl/v1/resource+name/the+id') {
            return new Response(200, ['Content-Type' => ['application/json']], ['key' => 'value']);
        }

        throw new \Exception('Unexpected request');
    }
}

final class PutAdapter implements Adapter
{
    private $_request;

    public function start(Request $request)
    {
        $this->_request = $request;
    }

    public function end($handle)
    {
        if (substr($this->_request->getUrl(), -5) === 'token') {
            return new Response(200, ['Content-Type' => ['application/json']], ['access_token' => 'a token', 'expires_in' => 1]);
        }

        if (
            $this->_request->getMethod() === 'PUT' &&
            $this->_request->getUrl() === 'baseUrl/v1/resource+name/the+id' &&
            $this->_request->getBody() === '{"the key":"the value"}' &&
            $this->_request->getHeaders() === [
                'Content-Type' => 'application/json',
                'Accept-Encoding' => 'gzip',
                'Authorization' => 'Bearer a token',
            ]
        ) {
            return new Response(200, ['Content-Type' => ['application/json']], ['key' => 'value']);
        }

        throw new \Exception('Unexpected request');
    }
}

final class PostAdapter implements Adapter
{
    private $_request;

    public function start(Request $request)
    {
        $this->_request = $request;
    }

    public function end($handle)
    {
        if (substr($this->_request->getUrl(), -5) === 'token') {
            return new Response(200, ['Content-Type' => ['application/json']], ['access_token' => 'a token', 'expires_in' => 1]);
        }

        if (
            $this->_request->getMethod() === 'POST' &&
            $this->_request->getUrl() === 'baseUrl/v1/resource+name' &&
            $this->_request->getBody() === '{"the key":"the value"}' &&
            $this->_request->getHeaders() === [
                'Content-Type' => 'application/json',
                'Accept-Encoding' => 'gzip',
                'Authorization' => 'Bearer a token',
            ]
        ) {
            return new Response(200, ['Content-Type' => ['application/json']], ['key' => 'value']);
        }

        throw new \Exception('Unexpected request');
    }
}

final class DeleteAdapter implements Adapter
{
    private $_request;

    public function start(Request $request)
    {
        $this->_request = $request;
    }

    public function end($handle)
    {
        if (substr($this->_request->getUrl(), -5) === 'token') {
            return new Response(200, ['Content-Type' => ['application/json']], ['access_token' => 'a token', 'expires_in' => 1]);
        }

        if (
            $this->_request->getMethod() === 'DELETE' &&
            $this->_request->getUrl() === 'baseUrl/v1/resource+name/the+id' &&
            $this->_request->getHeaders() === [
                'Content-Type' => 'application/json',
                'Accept-Encoding' => 'gzip',
                'Authorization' => 'Bearer a token',
            ]
        ) {
            $body = $this->_request->getBody();

            if ($body === null || $body === '{"the key":"the value"}') {
                return new Response(204, ['Content-Type' => ['application/json']]);
            }
        }

        throw new \Exception('Unexpected request');
    }
}

final class TokenAdapter implements Adapter
{
    public function start(Request $request)
    {
    }

    public function end($handle)
    {
        return new Response(200, ['Content-Type' => ['application/json']], ['access_token' => 'token', 'expires_in' => 1]);
    }
}

final class ExceptionAdapter implements Adapter
{
    private $_request;

    public function start(Request $request)
    {
        $this->_request = $request;
    }

    public function end($handle)
    {
        if (substr_count($this->_request->getUrl(), 'token') == 1) {
            return new Response(200, ['Content-Type' => ['application/json']], ['access_token' => 'foo', 'expires_in' => 1]);
        }

        throw new \Exception('An error');
    }
}

final class AccessTokenInvalidClientAdapter implements Adapter
{
    private $_request;

    public function start(Request $request)
    {
        $this->_request = $request;
    }

    public function end($handle)
    {
        if (substr_count($this->_request->getUrl(), 'token') == 1) {
            return new Response(200, ['Content-Type' => ['application/json']], ['error' => 'invalid_client']);
        }
    }
}

final class AccessTokenAdapter implements Adapter
{
    private $_request;
    private $_count = 0;

    public function start(Request $request)
    {
        $this->_request = $request;
    }

    public function end($handle)
    {
        if (substr_count($this->_request->getUrl(), 'token') == 1) {
            $response = new Response(200, ['Content-Type' => ['application/json']], ['access_token' => $this->_count, 'expires_in' => 1]);
            ++$this->_count;
            return $response;
        }

        $headers = $this->_request->getHeaders();
        if ($headers['Authorization'] === 'Bearer 1') {
            return new Response(200, ['Content-Type' => ['application/json']], []);
        }

        return new Response(401, ['Content-Type' => ['application/json']], ['error' => 'invalid_grant']);
    }
}

final class RefreshTokenAdapter implements Adapter
{
    private $_request;

    public function start(Request $request)
    {
        $this->_request = $request;
    }

    public function end($handle)
    {
        if (
            substr_count($this->_request->getUrl(), 'token') === 1 &&
            substr_count($this->_request->getBody(), 'grant_type=client_credentials') === 1
        ) {
            $response = new Response(
                200,
                ['Content-Type' => ['application/json']],
                ['access_token' => 'badToken', 'refresh_token' => 'boo', 'expires_in' => 1]
            );
            return $response;
        }

        if (
            substr_count($this->_request->getUrl(), 'token') === 1 &&
            substr_count($this->_request->getBody(), 'refresh_token=boo') === 1
        ) {
            $response = new Response(200, ['Content-Type' => ['application/json']], ['access_token' => 'goodToken', 'expires_in' => 1]);
            return $response;
        }

        $headers = $this->_request->getHeaders();
        if ($headers['Authorization'] === 'Bearer goodToken') {
            return new Response(200, ['Content-Type' => ['application/json']], []);
        }

        return new Response(401, ['Content-Type' => ['application/json']], ['error' => 'invalid_grant']);
    }
}

final class ApigeeOtherFaultAdapter implements Adapter
{
    private $_request;
    private $_count = 0;

    public function start(Request $request)
    {
        $this->_request = $request;
    }

    public function end($handle)
    {
        if (substr_count($this->_request->getUrl(), 'token') == 1) {
            $response = new Response(200, ['Content-Type' => ['application/json']], ['access_token' => $this->_count, 'expires_in' => 1]);
            ++$this->_count;
            return $response;
        }

        $headers = $this->_request->getHeaders();
        if ($headers['Authorization'] === 'Bearer 1') {
            return new Response(200, ['Content-Type' => ['application/json']], []);
        }

        return new Response(401, ['Content-Type' => ['application/json']], ['someotherproblem' => 'Something other than invalid access token']);
    }
}

final class ApigeeRefreshTokenAdapter implements Adapter
{
    private $_request;
    private $_count = 0;

    public function start(Request $request)
    {
        $this->_request = $request;
    }

    public function end($handle)
    {
        if (substr_count($this->_request->getUrl(), 'token') == 1) {
            $response = new Response(200, ['Content-Type' => ['application/json']], ['access_token' => $this->_count, 'expires_in' => 1]);
            ++$this->_count;
            return $response;
        }

        $headers = $this->_request->getHeaders();
        if ($headers['Authorization'] === 'Bearer 1') {
            return new Response(200, ['Content-Type' => ['application/json']], []);
        }

        return new Response(401, ['Content-Type' => ['application/json']], ['fault' => ['faultstring' => 'InvAlid accEss tOkEn']]);
    }
}

final class ApigeeRefreshToken2Adapter implements Adapter
{
    private $_request;
    private $_count = 0;

    public function start(Request $request)
    {
        $this->_request = $request;
    }

    public function end($handle)
    {
        if (substr_count($this->_request->getUrl(), 'token') == 1) {
            $response = new Response(200, ['Content-Type' => ['application/json']], ['access_token' => $this->_count, 'expires_in' => 1]);
            ++$this->_count;
            return $response;
        }

        $headers = $this->_request->getHeaders();
        if ($headers['Authorization'] === 'Bearer 1') {
            return new Response(200, ['Content-Type' => ['application/json']], []);
        }

        return new Response(401, ['Content-Type' => ['application/json']], ['fault' => ['faultstring' => 'AccEss TokEn eXpiRed']]);
    }
}

final class ErrorResponseAdapter implements Adapter
{
    public function start(Request $request)
    {
    }

    public function end($handle)
    {
        return new Response(400, ['Content-Type' => ['application/json']], ['error_description' => 'an error']);
    }
}

final class FakeAdapter implements Adapter
{
    public function start(Request $request)
    {
    }

    public function end($handle)
    {
        return new Response(200, ['Content-Type' => ['application/json']], ['access_token' => 'foo', 'expires_in' => 1]);
    }
}

final class CheckUrlAdapter implements Adapter
{
    private $_request;

    public function start(Request $request)
    {
        $this->_request = $request;
    }

    public function end($handle)
    {
        return new Response(
            200,
            ['Content-Type' => ['application/json']],
            ['access_token' => 'foo', 'url' => $this->_request->getUrl(), 'expires_in' => 1]
        );
    }
}

final class CacheAdapter implements Adapter
{
    private $_request;

    public function start(Request $request)
    {
        $this->_request = $request;
    }

    public function end($handle)
    {
        if (substr_count($this->_request->getUrl(), 'token') == 1) {
            return new Response(200, ['Content-Type' => ['application/json']], ['access_token' => 'token', 'expires_in' => 1]);
        }

        if (substr_count($this->_request->getUrl(), 'a+url') == 1) {
            return new Response(200, ['header' => ['value']], ['doesnt' => 'matter']);
        }

        throw new \Exception();
    }
}
final class ArrayCache implements Cache
{
    public $cache = [];

    public function set(Request $request, Response $response, $expires = null)
    {
        $this->cache[$request->getUrl()] = $response;
    }

    public function get(Request $request)
    {
        return Arrays::get($this->cache, $request->getUrl());
    }
}
