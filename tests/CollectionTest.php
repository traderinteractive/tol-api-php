<?php

namespace DominionEnterprises\Api;

/**
 * Unit tests for the Collection class
 *
 * @coversDefaultClass \DominionEnterprises\Api\Collection
 *
 * @uses \DominionEnterprises\Api\Collection::__construct
 * @uses \DominionEnterprises\Api\Collection::rewind
 * @uses \DominionEnterprises\Api\Client::__construct
 * @uses \DominionEnterprises\Api\Client::<private>
 * @uses \DominionEnterprises\Api\Authentication::__construct
 * @uses \DominionEnterprises\Api\Authentication::createClientCredentials
 * @uses \DominionEnterprises\Api\Authentication::parseTokenResponse
 * @uses \DominionEnterprises\Api\Authentication::getTokenRequest
 * @uses \DominionEnterprises\Api\Request
 * @uses \DominionEnterprises\Api\Response
 */
final class CollectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @covers ::__construct
     * @covers ::rewind
     * @covers ::valid
     * @covers ::key
     * @covers ::current
     * @covers ::next
     * @covers ::count
     * @uses \DominionEnterprises\Api\Client::startIndex
     * @uses \DominionEnterprises\Api\Client::index
     * @uses \DominionEnterprises\Api\Client::end
     */
    public function directUsage()
    {
        $authentication = Authentication::createClientCredentials('not under test', 'not under test');
        $client = new Client(new CollectionAdapter(), $authentication, 'not under test');
        $collection = new Collection($client, 'basic', ['limit' => 3]);
        $collection->rewind();
        $iterations = 0;
        while ($collection->valid()) {
            $key = $collection->key();
            $this->assertSame(['id' => (string)$key, 'key' => $key], $collection->current());
            $collection->next();
            ++$iterations;
        }

        $this->assertSame($collection->count(), $iterations);
    }

    /**
     * Verifies code does not explode when rewind() consectutively
     *
     * @test
     * @group edgecase
     */
    public function consecutiveRewind()
    {
        $authentication = Authentication::createClientCredentials('not under test', 'not under test');
        $client = new Client(new CollectionAdapter(), $authentication, 'not under test');
        $collection = new Collection($client, 'basic', ['limit' => 3]);
        $collection->rewind();
        $collection->rewind();
        $iterations = 0;
        foreach ($collection as $key => $actual) {
            $this->assertSame(['id' => (string)$key, 'key' => $key], $actual);
            ++$iterations;
        }

        $this->assertSame(5, $iterations);
    }

    /**
     * Verifies code does not explode when current() consectutively
     *
     * @test
     * @group edgecase
     */
    public function consecutiveCurrent()
    {
        $authentication = Authentication::createClientCredentials('not under test', 'not under test');
        $client = new Client(new CollectionAdapter(), $authentication, 'not under test');
        $collection = new Collection($client, 'basic', ['limit' => 3]);
        $this->assertSame(['id' => '0', 'key' => 0], $collection->current());
        $this->assertSame(['id' => '0', 'key' => 0], $collection->current());
    }

    /**
     * Verifies code does not explode when next() consectutively
     *
     * @test
     * @group edgecase
     */
    public function consecutiveNext()
    {
        $authentication = Authentication::createClientCredentials('not under test', 'not under test');
        $client = new Client(new CollectionAdapter(), $authentication, 'not under test');
        $collection = new Collection($client, 'basic', ['limit' => 3]);
        $collection->next();
        $collection->next();
        $this->assertSame(['id' => '1', 'key' => 1], $collection->current());
    }

    /**
     * Verifies count() lazy loads the next result
     *
     * @test
     * @covers ::count
     * @uses \DominionEnterprises\Api\Collection::next
     * @uses \DominionEnterprises\Api\Client::startIndex
     * @uses \DominionEnterprises\Api\Client::index
     * @uses \DominionEnterprises\Api\Client::end
     *
     * THIS FUNCTION CANNOT BE NAMED COUNT OR PHPUNIT EXPLODES
     */
    public function countOfResult()
    {
        $authentication = Authentication::createClientCredentials('not under test', 'not under test');
        $client = new Client(new CollectionAdapter(), $authentication, 'not under test');
        $collection = new Collection($client, 'basic', ['limit' => 3]);
        $this->assertSame(5, $collection->count());
    }

    /**
     * Verifies key() lazy loads the next result
     *
     * @test
     * @covers ::key
     * @uses \DominionEnterprises\Api\Collection::next
     * @uses \DominionEnterprises\Api\Client::startIndex
     * @uses \DominionEnterprises\Api\Client::index
     * @uses \DominionEnterprises\Api\Client::end
     */
    public function key()
    {
        $authentication = Authentication::createClientCredentials('not under test', 'not under test');
        $client = new Client(new CollectionAdapter(), $authentication, 'not under test');
        $collection = new Collection($client, 'basic', ['limit' => 3]);
        $this->assertSame(0, $collection->key());
    }

    /**
     * Verifies current() lazy loads the next result
     *
     * @test
     * @covers ::current
     * @uses \DominionEnterprises\Api\Collection::next
     * @uses \DominionEnterprises\Api\Client::startIndex
     * @uses \DominionEnterprises\Api\Client::index
     * @uses \DominionEnterprises\Api\Client::end
     */
    public function current()
    {
        $authentication = Authentication::createClientCredentials('not under test', 'not under test');
        $client = new Client(new CollectionAdapter(), $authentication, 'not under test');
        $collection = new Collection($client, 'basic', ['limit' => 3]);
        $this->assertSame(['id' => '0', 'key' => 0], $collection->current());
    }

    /**
     * Verfies current() throws when collection is empty
     *
     * @test
     * @expectedException \OutOfBoundsException
     */
    public function currentWithEmpty()
    {
        $authentication = Authentication::createClientCredentials('not under test', 'not under test');
        $client = new Client(new CollectionAdapter(), $authentication, 'not under test');
        $collection = new Collection($client, 'empty');
        $collection->current();
    }

    /**
     * Verfies key() throws when collection is empty
     *
     * @test
     * @expectedException \OutOfBoundsException
     */
    public function keyWithEmpty()
    {
        $authentication = Authentication::createClientCredentials('not under test', 'not under test');
        $client = new Client(new CollectionAdapter(), $authentication, 'not under test');
        $collection = new Collection($client, 'empty');
        $collection->key();
    }

    /**
     * @test
     */
    public function multiIteration()
    {
        $authentication = Authentication::createClientCredentials('not under test', 'not under test');
        $client = new Client(new CollectionAdapter(), $authentication, 'not under test');
        $collection = new Collection($client, 'basic', ['limit' => 3]);

        $iterations = 0;
        foreach ($collection as $key => $actual) {
            $this->assertSame(['id' => (string)$key, 'key' => $key], $actual);
            ++$iterations;
        }

        $this->assertSame(5, $iterations);

        $iterations = 0;
        foreach ($collection as $key => $actual) {
            $this->assertSame(['id' => (string)$key, 'key' => $key], $actual);
            ++$iterations;
        }

        $this->assertSame(5, $iterations);

    }

    /**
     * Verify Collection can handle an empty response
     *
     * @test
     */
    public function emptyResult()
    {
        $authentication = Authentication::createClientCredentials('not under test', 'not under test');
        $client = new Client(new CollectionAdapter(), $authentication, 'not under test');
        $collection = new Collection($client, 'empty');
        $this->assertFalse($collection->valid());
        $this->assertSame(0, $collection->count());
    }

    /**
     * Verify Collection can handle a response with a single item
     *
     * @test
     */
    public function oneItemCollection()
    {
        $authentication = Authentication::createClientCredentials('not under test', 'not under test');
        $client = new Client(new CollectionAdapter(), $authentication, 'not under test');
        $collection = new Collection($client, 'single');
        foreach ($collection as $item) {
            $this->assertSame(['id' => '0', 'key' => 0], $item);
        }
    }

    /**
     * Verifies basic behavior of column().
     *
     * @test
     * @covers ::column
     */
    public function column()
    {
        $authentication = Authentication::createClientCredentials('not under test', 'not under test');
        $client = new Client(new CollectionAdapter(), $authentication, 'not under test');
        $collection = new Collection($client, 'basic', ['limit' => 3]);
        $this->assertSame(
            [0, 1, 2, 3, 4],
            iterator_to_array($collection->column('key'))
        );
    }
}

final class CollectionAdapter implements Adapter
{
    private $_request;

    public function start(Request $request)
    {
        $this->_request = $request;
    }

    public function end($handle)
    {
        if (substr_count($this->_request->getUrl(), '/token') == 1) {
            return new Response(200, ['Content-Type' => ['application/json']], ['access_token' => 'foo', 'expires_in' => 1]);
        }

        if (substr_count($this->_request->getUrl(), '/empty') == 1) {
            return new Response(
                200,
                ['Content-Type' => ['application/json']],
                ['pagination' => ['offset' => 0, 'total' => 0, 'limit' => 0], 'result' => []]
            );
        }

        if (substr_count($this->_request->getUrl(), '/single') == 1) {
            return new Response(
                200,
                ['Content-Type' => ['application/json']],
                ['pagination' => ['offset' => 0, 'total' => 1, 'limit' => 1], 'result' => [['id' => '0', 'key' => 0]]]
            );
        }

        if (substr_count($this->_request->getUrl(), '/basic') === 1) {
            $results = [
                ['id' => '0', 'key' => 0],
                ['id' => '1', 'key' => 1],
                ['id' => '2', 'key' => 2],
                ['id' => '3', 'key' => 3],
                ['id' => '4', 'key' => 4],
            ];

            $queryString = parse_url($this->_request->getUrl(), PHP_URL_QUERY);
            $queryParams = [];
            parse_str($queryString, $queryParams);

            $offset = (int)$queryParams['offset'];
            $limit = (int)$queryParams['limit'];

            $result = [
                'pagination' => ['offset' => $offset, 'total' => 5, 'limit' => $limit],
                'result' => array_slice($results, $offset, $limit),
            ];

            return new Response(200, ['Content-Type' => ['application/json']], $result);
        }

        throw new \Exception('Unexpected request');
    }
}
