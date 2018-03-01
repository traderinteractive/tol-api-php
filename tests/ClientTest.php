<?php

namespace TraderInteractive\Api;

use ArrayObject;
use Chadicus\Psr\SimpleCache\InMemoryCache;
use DominionEnterprises\Util\Arrays;
use DominionEnterprises\Util\Http;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

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
        $adapter = new FakeAdapter(
            function (RequestInterface $request) {
                return new Response(
                    200,
                    ['Content-Type' => ['application/json']],
                    ['access_token' => 'foo', 'expires_in' => 1]
                );
            }
        );
        $client = new Client($adapter, $this->getAuthentication(), 'a url');
        $this->assertSame([null, null], $client->getTokens());
    }

    /**
     * @test
     * @covers ::getTokens
     */
    public function getTokensWithCall()
    {
        $tokenCount = 0;
        $adapter = new FakeAdapter(
            function (RequestInterface $request) use (&$tokenCount) {
                if (substr_count($request->getUri(), 'token') == 1) {
                    $response = new Response(
                        200,
                        ['Content-Type' => ['application/json']],
                        json_encode(['access_token' => $tokenCount, 'expires_in' => 1])
                    );
                    ++$tokenCount;
                    return $response;
                }

                $headers = $request->getHeaders();
                if ($headers['Authorization'] === ['Bearer 1']) {
                    return new Response(200, ['Content-Type' => ['application/json']]);
                }

                return new Response(401, ['Content-Type' => ['application/json']], json_encode(['error' => 'invalid_grant']));
            }
        );
        $client = new Client($adapter, $this->getAuthentication(), 'a url');
        $this->assertSame(200, $client->end($client->startIndex('a resource', []))->getStatusCode());
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
        $tokenCount = 0;
        $adapter = new FakeAdapter(
            function (RequestInterface $request) use (&$tokenCount) {
                if (substr_count($request->getUri(), 'token') == 1) {
                    return new Response(200, ['Content-Type' => ['application/json']], json_encode(['error' => 'invalid_client']));
                }
            }
        );

        $client = new Client($adapter, $this->getAuthentication(), 'a url');
        $client->end($client->startIndex('a resource', []))->getStatusCode();
    }

    /**
     * @test
     * @group unit
     * @covers ::end
     */
    public function invalidTokenIsRefreshed()
    {
        $tokenCount = 0;
        $adapter = new FakeAdapter(
            function (RequestInterface $request) use (&$tokenCount) {
                if (substr_count($request->getUri(), 'token') == 1) {
                    $response = new Response(
                        200,
                        ['Content-Type' => ['application/json']],
                        json_encode(['access_token' => $tokenCount, 'expires_in' => 1])
                    );
                    ++$tokenCount;
                    return $response;
                }

                $headers = $request->getHeaders();
                if ($headers['Authorization'] === ['Bearer foo']) {
                    return new Response(
                        401,
                        ['Content-Type' => ['application/json']],
                        json_encode(['error' => ['code' => 'invalid_token']])
                    );
                }

                $headers = $request->getHeaders();
                if ($headers['Authorization'] === ['Bearer 0']) {
                    return new Response(200, ['Content-Type' => ['application/json']]);
                }

                return new Response(401, ['Content-Type' => ['application/json']], json_encode(['error' => 'invalid_grant']));
            }
        );

        $client = new Client($adapter, $this->getAuthentication(), 'a url', Client::CACHE_MODE_NONE, null, 'foo');
        $this->assertSame(200, $client->end($client->startIndex('a resource', []))->getStatusCode());
    }

    /**
     * @test
     * @group unit
     * @covers ::setDefaultHeaders
     */
    public function defaultHeadersArePassed()
    {
        $test = $this;
        $adapter = new FakeAdapter(
            function (RequestInterface $request) use ($test) {
                $test->assertEquals(['foo'], $request->getHeaders()['testHeader']);
                return new Response(200, ['Content-Type' => ['application/json']]);
            }
        );
        $client = new Client($adapter, $this->getAuthentication(), 'a url', Client::CACHE_MODE_NONE, null, 'foo');
        $client->setDefaultHeaders(['testHeader' => 'foo']);
        $this->assertSame(200, $client->end($client->startIndex('a resource', []))->getStatusCode());
    }

    /**
     * @test
     * @group unit
     * @covers ::end
     */
    public function tokenIsRefreshedWith401()
    {
        $tokenCount = 0;
        $adapter = new FakeAdapter(
            function (RequestInterface $request) use (&$tokenCount) {
                if (substr_count($request->getUri(), 'token') == 1) {
                    $response = new Response(
                        200,
                        ['Content-Type' => ['application/json']],
                        json_encode(['access_token' => $tokenCount, 'expires_in' => 1])
                    );
                    ++$tokenCount;
                    return $response;
                }

                $headers = $request->getHeaders();
                if ($headers['Authorization'] === ['Bearer 1']) {
                    return new Response(200, ['Content-Type' => ['application/json']]);
                }

                return new Response(401, ['Content-Type' => ['application/json']], json_encode(['error' => 'invalid_grant']));
            }
        );

        $client = new Client($adapter, $this->getAuthentication(), 'a url');
        $this->assertSame(200, $client->end($client->startIndex('a resource', []))->getStatusCode());
    }

    /**
     * @test
     * @group unit
     * @covers ::end
     */
    public function tokenIsRefreshedUsingRefreshTokenWith401()
    {
        $adapter = new FakeAdapter(
            function (RequestInterface $request) {
                if (substr_count($request->getUri(), 'token') === 1
                        && substr_count($request->getBody(), 'grant_type=client_credentials') === 1) {
                    return new Response(
                        200,
                        ['Content-Type' => ['application/json']],
                        json_encode(['access_token' => 'badToken', 'refresh_token' => 'boo', 'expires_in' => 1])
                    );
                }

                if (substr_count($request->getUri(), 'token') === 1
                        && substr_count($request->getBody(), 'refresh_token=boo') === 1) {
                    return new Response(
                        200,
                        ['Content-Type' => ['application/json']],
                        json_encode(['access_token' => 'goodToken', 'expires_in' => 1])
                    );
                }

                $headers = $request->getHeaders();
                if ($headers['Authorization'] === ['Bearer goodToken']) {
                    return new Response(200, ['Content-Type' => ['application/json']]);
                }

                return new Response(401, ['Content-Type' => ['application/json']], json_encode(['error' => 'invalid_grant']));
            }
        );

        $client = new Client($adapter, $this->getAuthentication(), 'a url');
        $this->assertSame(200, $client->end($client->startIndex('a resource', []))->getStatusCode());
    }

    /**
     * @test
     * @group unit
     * @covers ::end
     */
    public function tokenIsNotRefreshedOnOtherFault()
    {
        $tokenCount = 0;
        $adapter = new FakeAdapter(
            function (RequestInterface $request) use (&$tokenCount) {
                if (substr_count($request->getUri(), 'token') == 1) {
                    $response = new Response(
                        200,
                        ['Content-Type' => ['application/json']],
                        json_encode(['access_token' => $tokenCount, 'expires_in' => 1])
                    );
                    ++$tokenCount;
                    return $response;
                }

                return new Response(
                    401,
                    ['Content-Type' => ['application/json']],
                    json_encode(['someotherproblem' => 'Something other than invalid access token'])
                );
            }
        );
        $client = new Client($adapter, $this->getAuthentication(), 'a url');
        $this->assertSame(401, $client->end($client->startIndex('a resource', []))->getStatusCode());
    }

    /**
     * @test
     * @group unit
     * @covers ::end
     */
    public function tokenIsRefreshedWith401OnApigee()
    {
        $tokenCount = 0;
        $adapter = new FakeAdapter(
            function (RequestInterface $request) use (&$tokenCount) {
                if (substr_count($request->getUri(), 'token') == 1) {
                    $response = new Response(
                        200,
                        ['Content-Type' => ['application/json']],
                        json_encode(['access_token' => $tokenCount, 'expires_in' => 1])
                    );
                    ++$tokenCount;
                    return $response;
                }

                $headers = $request->getHeaders();
                if ($headers['Authorization'] === ['Bearer 1']) {
                    return new Response(200, ['Content-Type' => ['application/json']]);
                }
                return new Response(
                    401,
                    ['Content-Type' => ['application/json']],
                    json_encode(['fault' => ['faultstring' => 'AccEss TokEn eXpiRed']])
                );
            }
        );
        $client = new Client($adapter, $this->getAuthentication(), 'a url');
        $this->assertSame(200, $client->end($client->startIndex('a resource', []))->getStatusCode());
    }

    /**
     * @test
     * @group unit
     * @covers ::end
     */
    public function tokenIsRefreshedWith401OnApigeeWithOtherMessage()
    {
        $tokenCount = 0;
        $adapter = new FakeAdapter(
            function (RequestInterface $request) use (&$tokenCount) {
                if (substr_count($request->getUri(), 'token') == 1) {
                    $response = new Response(
                        200,
                        ['Content-Type' => ['application/json']],
                        json_encode(['access_token' => $tokenCount, 'expires_in' => 1])
                    );
                    ++$tokenCount;
                    return $response;
                }

                $headers = $request->getHeaders();
                if ($headers['Authorization'] === ['Bearer 1']) {
                    return new Response(200, ['Content-Type' => ['application/json']]);
                }

                return new Response(
                    401,
                    ['Content-Type' => ['application/json']],
                    json_encode(['fault' => ['faultstring' => 'AccEss TokEn eXpiRed']])
                );
            }
        );
        $client = new Client($adapter, $this->getAuthentication(), 'a url');
        $this->assertSame(200, $client->end($client->startIndex('a resource', []))->getStatusCode());
    }

    /**
     * @test
     * @group unit
     * @covers ::__construct
     * @expectedException \Exception
     */
    public function throwsWithHttpCodeNot200()
    {
        $adapter = new FakeAdapter(
            function (RequestInterface $request) {
                return new Response(400, ['Content-Type' => ['application/json']], json_encode(['error_description' => 'an error']));
            }
        );
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
        $adapter = new FakeAdapter(
            function (RequestInterface $request) {
                if (substr($request->getUri(), -5) === 'token') {
                    return new Response(
                        200,
                        ['Content-Type' => ['application/json']],
                        json_encode(['access_token' => 'a token', 'expires_in' => 1])
                    );
                }

                if ($request->getMethod() === 'GET'
                        && urldecode($request->getUri()) === 'baseUrl/v1/resource name?the name=the value') {
                    return new Response(200, ['Content-Type' => ['application/json']], json_encode(['key' => 'value']));
                }
            }
        );

        $client = new Client($adapter, $this->getAuthentication(), 'baseUrl/v1');

        $response = $client->index('resource name', ['the name' => 'the value']);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['key' => 'value'], json_decode($response->getBody(), true));
        $this->assertSame(['Content-Type' => ['application/json']], $response->getHeaders());
    }

    /**
     * @test
     * @covers ::get
     * @covers ::startGet
     */
    public function get()
    {
        $adapter = new FakeAdapter(
            function (RequestInterface $request) {
                if (substr($request->getUri(), -5) === 'token') {
                    return new Response(
                        200,
                        ['Content-Type' => ['application/json']],
                        json_encode(['access_token' => 'a token', 'expires_in' => 1])
                    );
                }

                if ($request->getMethod() === 'GET' && (string)$request->getUri() === 'baseUrl/v1/resource+name/the+id') {
                    return new Response(200, ['Content-Type' => ['application/json']], json_encode(['key' => 'value']));
                }
            }
        );
        $client = new Client($adapter, $this->getAuthentication(), 'baseUrl/v1');

        $response = $client->get('resource name', 'the id');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['key' => 'value'], json_decode($response->getBody(), true));
        $this->assertSame(['Content-Type' => ['application/json']], $response->getHeaders());
    }

    /**
     * @test
     * @covers ::get
     * @covers ::startGet
     */
    public function getWithParameters()
    {
        $adapter = new FakeAdapter(
            function (RequestInterface $request) {
                if (substr($request->getUri(), -5) === 'token') {
                    return new Response(
                        200,
                        ['Content-Type' => ['application/json']],
                        json_encode(['access_token' => 'a token', 'expires_in' => 1])
                    );
                }

                if ($request->getMethod() === 'GET'
                        && (string)$request->getUri() === 'baseUrl/v1/resource+name/the+id?foo=bar') {
                    return new Response(200, ['Content-Type' => ['application/json']], json_encode(['key' => 'value']));
                }
            }
        );
        $client = new Client($adapter, $this->getAuthentication(), 'baseUrl/v1');

        $response = $client->get('resource name', 'the id', ['foo' => 'bar']);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['key' => 'value'], json_decode($response->getBody(), true));
        $this->assertSame(['Content-Type' => ['application/json']], $response->getHeaders());
    }

    /**
     * @test
     * @covers ::put
     * @covers ::startPut
     */
    public function put()
    {
        $adapter = new FakeAdapter(
            function (RequestInterface $request) {
                if (substr($request->getUri(), -5) === 'token') {
                    return new Response(
                        200,
                        ['Content-Type' => ['application/json']],
                        json_encode(['access_token' => 'a token', 'expires_in' => 1])
                    );
                }

                if ($request->getMethod() === 'PUT'
                        && (string)$request->getUri() === 'baseUrl/v1/resource+name/the+id'
                        && (string)$request->getBody() === '{"the key":"the value"}'
                        && $request->getHeaders() === [
                            'Content-Type' => ['application/json'],
                            'Accept-Encoding' => ['gzip'],
                            'Authorization' => ['Bearer a token'],
                        ]
                ) {
                    return new Response(200, ['Content-Type' => ['application/json']], json_encode(['key' => 'value']));
                }
            }
        );

        $client = new Client($adapter, $this->getAuthentication(), 'baseUrl/v1');

        $response = $client->put('resource name', 'the id', ['the key' => 'the value']);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['key' => 'value'], json_decode($response->getBody(), true));
        $this->assertSame(['Content-Type' => ['application/json']], $response->getHeaders());
    }

    /**
     * @test
     * @covers ::post
     * @covers ::startPost
     */
    public function post()
    {
        $adapter = new FakeAdapter(
            function (RequestInterface $request) {
                if (substr($request->getUri(), -5) === 'token') {
                    return new Response(
                        200,
                        ['Content-Type' => ['application/json']],
                        json_encode(['access_token' => 'a token', 'expires_in' => 1])
                    );
                }

                if ($request->getMethod() === 'POST'
                        && (string)$request->getUri() === 'baseUrl/v1/resource+name'
                        && (string)$request->getBody() === '{"the key":"the value"}'
                        && $request->getHeaders() === [
                            'Content-Type' => ['application/json'],
                            'Accept-Encoding' => ['gzip'],
                            'Authorization' => ['Bearer a token'],
                        ]
                ) {
                    return new Response(200, ['Content-Type' => ['application/json']], json_encode(['key' => 'value']));
                }
            }
        );
        $client = new Client($adapter, $this->getAuthentication(), 'baseUrl/v1');

        $response = $client->post('resource name', ['the key' => 'the value']);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['key' => 'value'], json_decode($response->getBody(), true));
        $this->assertSame(['Content-Type' => ['application/json']], $response->getHeaders());
    }

    /**
     * @test
     * @covers ::delete
     * @covers ::startDelete
     */
    public function delete()
    {
        $adapter = new FakeAdapter(
            function (RequestInterface $request) {
                if (substr($request->getUri(), -5) === 'token') {
                    return new Response(
                        200,
                        ['Content-Type' => ['application/json']],
                        json_encode(['access_token' => 'a token', 'expires_in' => 1])
                    );
                }

                if ($request->getMethod() === 'DELETE'
                        && (string)$request->getUri() === 'baseUrl/v1/resource+name/the+id'
                        && $request->getHeaders() === [
                            'Content-Type' => ['application/json'],
                            'Accept-Encoding' => ['gzip'],
                            'Authorization' => ['Bearer a token'],
                        ]
                ) {
                    $body = (string)$request->getBody();

                    if ($body === '' || $body === '{"the key":"the value"}') {
                        return new Response(204, ['Content-Type' => ['application/json']], json_encode([]));
                    }
                }
            }
        );
        $client = new Client($adapter, $this->getAuthentication(), 'baseUrl/v1');

        $response = $client->delete('resource name', 'the id');

        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame([], json_decode($response->getBody(), true));
        $this->assertSame(['Content-Type' => ['application/json']], $response->getHeaders());
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
        $adapter = new FakeAdapter(
            function (RequestInterface $request) {
                if (substr($request->getUri(), -5) === 'token') {
                    return new Response(
                        200,
                        ['Content-Type' => ['application/json']],
                        json_encode(['access_token' => 'a token', 'expires_in' => 1])
                    );
                }

                if ($request->getMethod() === 'DELETE'
                        && $request->getUri() === 'baseUrl/v1/resource+name/the+id'
                        && $request->getHeaders() === [
                            'Content-Type' => 'application/json',
                            'Accept-Encoding' => 'gzip',
                            'Authorization' => 'Bearer a token',
                        ]
                ) {
                    $body = $request->getBody();

                    if ($body === null || $body === '{"the key":"the value"}') {
                        return new Response(204, ['Content-Type' => ['application/json']]);
                    }
                }
            }
        );
        $client = new Client($adapter, $this->getAuthentication(), 'baseUrl/v1');
        $client->startDelete('resource', null, ['foo' => 'bar']);
        $this->assertSame('baseUrl/v1/resource', (string)$adapter->getLastRequest()->getUri());
        $this->assertSame('DELETE', $adapter->getLastRequest()->getMethod());
        $this->assertSame(json_encode(['foo' => 'bar']), (string)$adapter->getLastRequest()->getBody());
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
        $adapter = new FakeAdapter(
            function (RequestInterface $request) {
                if (substr($request->getUri(), -5) === 'token') {
                    return new Response(
                        200,
                        ['Content-Type' => ['application/json']],
                        json_encode(['access_token' => 'a token', 'expires_in' => 1])
                    );
                }

                if ($request->getMethod() === 'DELETE'
                        && (string)$request->getUri() === 'baseUrl/v1/resource+name/the+id'
                        && $request->getHeaders() === [
                            'Content-Type' => ['application/json'],
                            'Accept-Encoding' => ['gzip'],
                            'Authorization' => ['Bearer a token'],
                        ]
                ) {
                    $body = (string)$request->getBody();

                    if ($body === '' || $body === '{"the key":"the value"}') {
                        return new Response(204, ['Content-Type' => ['application/json']], json_encode([]));
                    }
                }
            }
        );
        $client = new Client($adapter, $this->getAuthentication(), 'baseUrl/v1');

        $response = $client->delete('resource name', 'the id', ['the key' => 'the value']);

        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame([], json_decode($response->getBody(), true));
        $this->assertSame(['Content-Type' => ['application/json']], $response->getHeaders());
    }

    /**
     * @test
     * @group unit
     * @covers ::index
     */
    public function indexWithMultiParameters()
    {
        $adapter = new FakeAdapter(
            function (RequestInterface $request) {
                return new Response(
                    200,
                    ['Content-Type' => ['application/json']],
                    json_encode(['access_token' => 'foo', 'url' => (string)$request->getUri(), 'expires_in' => 1])
                );
            }
        );
        $client = new Client($adapter, $this->getAuthentication(), 'url');
        $results = $client->index('resource', ['abc' => ['1$2(3', '4)5*6']]);
        $response = json_decode($results->getBody(), true);
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
        $adapter = new FakeAdapter(
            function (RequestInterface $request) {
                return new Response(
                    200,
                    ['Content-Type' => ['application/json']],
                    json_encode(['access_token' => 'foo', 'expires_in' => 1])
                );
            }
        );
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
        $adapter = new FakeAdapter(
            function (RequestInterface $request) {
                return new Response(
                    200,
                    ['Content-Type' => ['application/json']],
                    json_encode(['access_token' => 'token', 'expires_in' => 1])
                );
            }
        );
        $cache = new InMemoryCache();
        $request = new Request('GET', 'baseUrl/a+url/id', []);
        $expected = new Response(200, ['key' => ['value']], json_encode(['doesnt' => 'matter']));
        $cache->set($this->getCacheKey($request), $expected);
        $client = new Client($adapter, $this->getAuthentication(), 'baseUrl', Client::CACHE_MODE_GET, $cache);
        $actual = $client->get('a url', 'id');
        $this->assertSame($expected->getStatusCode(), $actual->getStatusCode());
        $this->assertSame((string)$expected->getBody(), (string)$actual->getBody());
        $this->assertSame($expected->getHeaders(), $actual->getHeaders());
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
        $request = new Request('GET', 'baseUrl/a+url/id', []);
        $unexpected = new Response(200, ['key' => ['value']], json_encode(['doesnt' => 'matter']));
        $expected = new Response(200, ['Content-Type' => ['application/json']], json_encode([]));
        $cache->set($this->getCacheKey($request), $unexpected);
        $adapter = new FakeAdapter(
            function (RequestInterface $request) {
                return new Response(200, ['Content-Type' => ['application/json']], json_encode([]));
            }
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
        $this->assertSame($expected->getStatusCode(), $actual->getStatusCode());
        $this->assertSame($expected->getHeaders(), $actual->getHeaders());
        $this->assertSame((string)$expected->getBody(), (string)$actual->getBody());

        $actual = $cache->get($this->getCacheKey($request));
        $this->assertSame($expected->getStatusCode(), $actual->getStatusCode());
        $this->assertSame($expected->getHeaders(), $actual->getHeaders());
        $this->assertSame((string)$expected->getBody(), (string)$actual->getBody());

        $adapter = new FakeAdapter(
            function (RequestInterface $request) {
                return new Response(
                    200,
                    ['Content-Type' => ['application/json']],
                    json_encode(['access_token' => 'token', 'expires_in' => 1])
                );
            }
        );
        $client = new Client($adapter, $this->getAuthentication(), 'baseUrl', Client::CACHE_MODE_GET, $cache);
        $actual = $client->end($client->startGet('a url', 'id'));
        $this->assertSame($expected->getStatusCode(), $actual->getStatusCode());
        $this->assertSame($expected->getHeaders(), $actual->getHeaders());
        $this->assertSame((string)$expected->getBody(), (string)$actual->getBody());
    }

    /**
     * @test
     * @covers ::__construct
     * @covers ::end
     * @covers ::startGet
     */
    public function getTokenNotInCache()
    {
        $adapter = new FakeAdapter(
            function (RequestInterface $request) {
                if (substr($request->getUri(), -5) === 'token') {
                    return new Response(
                        200,
                        ['Content-Type' => ['application/json']],
                        json_encode(['access_token' => 'a token', 'expires_in' => 1])
                    );
                }

                if ($request->getMethod() === 'GET' && (string)$request->getUri() === 'baseUrl/v1/resource+name/the+id') {
                    return new Response(200, ['Content-Type' => ['application/json']], json_encode(['key' => 'value']));
                }
            }
        );
        $client = new Client(
            $adapter,
            $this->getAuthentication(),
            'baseUrl/v1',
            Client::CACHE_MODE_TOKEN,
            new InMemoryCache()
        );

        $response = $client->end($client->startGet('resource name', 'the id'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['key' => 'value'], json_decode($response->getBody(), true));
        $this->assertSame(['Content-Type' => ['application/json']], $response->getHeaders());
    }

    /**
     * @test
     * @covers ::end
     */
    public function setCache()
    {
        $adapter = new FakeAdapter(
            function (RequestInterface $request) {
                if (substr_count($request->getUri(), 'token') == 1) {
                    return new Response(
                        200,
                        ['Content-Type' => ['application/json']],
                        json_encode(['access_token' => 'token', 'expires_in' => 1])
                    );
                }

                if (substr_count($request->getUri(), 'a+url') == 1) {
                    return new Response(200, ['header' => ['value']], json_encode(['doesnt' => 'matter']));
                }
            }
        );
        $cache = new InMemoryCache();
        $client = new Client($adapter, $this->getAuthentication(), 'baseUrl', Client::CACHE_MODE_GET, $cache);
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
                json_encode(['access_token' => 'an access token', 'expires_in' => 1])
            )
        );
        $adapter = new FakeAdapter(
            function (RequestInterface $request) {
                if (substr_count($request->getUri(), 'foos')) {
                    return new Response(200, ['Content-Type' => ['application/json']], json_encode(['a body']));
                }
            }
        );
        $client = new Client($adapter, $authentication, 'baseUrl', Client::CACHE_MODE_TOKEN, $cache);
        // no token requests should be made
        $this->assertSame(['a body'], json_decode($client->index('foos')->getBody(), true));
        // empty the cache
        $cache->clear();
        // no token requests should be made with second  request
        $this->assertSame(['a body'], json_decode($client->index('foos')->getBody(), true));
    }

    private function getAuthentication() : Authentication
    {
        return Authentication::createClientCredentials('not under test', 'not under test');
    }

    private function getCacheKey(RequestInterface $request) : string
    {
        return CacheHelper::getCacheKey($request);
    }
}
