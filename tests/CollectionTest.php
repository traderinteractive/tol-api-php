<?php

namespace TraderInteractive\Api;

/**
 * Unit tests for the Collection class
 *
 * @coversDefaultClass \TraderInteractive\Api\Collection
 *
 * @uses \TraderInteractive\Api\Collection::__construct
 * @uses \TraderInteractive\Api\Collection::rewind
 * @uses \TraderInteractive\Api\Client::__construct
 * @uses \TraderInteractive\Api\Client::<private>
 * @uses \TraderInteractive\Api\Authentication::__construct
 * @uses \TraderInteractive\Api\Authentication::createClientCredentials
 * @uses \TraderInteractive\Api\Authentication::parseTokenResponse
 * @uses \TraderInteractive\Api\Authentication::getTokenRequest
 * @uses \TraderInteractive\Api\Request
 * @uses \TraderInteractive\Api\Response
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
     * @uses \TraderInteractive\Api\Client::startIndex
     * @uses \TraderInteractive\Api\Client::index
     * @uses \TraderInteractive\Api\Client::end
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
     * @uses \TraderInteractive\Api\Collection::next
     * @uses \TraderInteractive\Api\Client::startIndex
     * @uses \TraderInteractive\Api\Client::index
     * @uses \TraderInteractive\Api\Client::end
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
     * @uses \TraderInteractive\Api\Collection::next
     * @uses \TraderInteractive\Api\Client::startIndex
     * @uses \TraderInteractive\Api\Client::index
     * @uses \TraderInteractive\Api\Client::end
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
     * @uses \TraderInteractive\Api\Collection::next
     * @uses \TraderInteractive\Api\Client::startIndex
     * @uses \TraderInteractive\Api\Client::index
     * @uses \TraderInteractive\Api\Client::end
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

    /**
     * Verifies basic behavior of select().
     *
     * @test
     * @covers ::select
     *
     * @return void
     */
    public function select()
    {
        $authentication = Authentication::createClientCredentials('not under test', 'not under test');
        $client = new Client(new CollectionAdapter(), $authentication, 'not under test');
        $collection = new Collection($client, 'basic', ['limit' => 3]);
        $this->assertSame(
            [
                ['key' => 0],
                ['key' => 1],
                ['key' => 2],
                ['key' => 3],
                ['key' => 4],
            ],
            iterator_to_array($collection->select(['key']))
        );
    }

    /**
     * Verifies behavior of select() with multiple keys.
     *
     * @test
     * @covers ::select
     *
     * @return void
     */
    public function selectMultipleKeys()
    {
        $adapter = new CollectionAdapter();
        $adapter->results = [
            ['id' => 1, 'name' => 'Sam', 'score' => 99],
            ['id' => 2, 'name' => 'Bob', 'score' => 83],
            ['id' => 3, 'name' => 'Jon', 'score' => 75],
            ['id' => 4, 'name' => 'Ted', 'score' => 64],
        ];
        $authentication = Authentication::createClientCredentials('not under test', 'not under test');
        $client = new Client($adapter, $authentication, 'not under test');
        $collection = new Collection($client, 'basic', ['limit' => 3]);
        $this->assertSame(
            [
                ['id' => 1, 'score' => 99],
                ['id' => 2, 'score' => 83],
                ['id' => 3, 'score' => 75],
                ['id' => 4, 'score' => 64],
            ],
            iterator_to_array($collection->select(['id', 'score']))
        );
    }

    /**
     * Verifies behavior of select() when results have missing keys.
     *
     * @test
     * @covers ::select
     *
     * @return void
     */
    public function selectMissingKeys()
    {
        $adapter = new CollectionAdapter();
        $adapter->results = [
            ['id' => 1, 'name' => 'Sam', 'score' => 99],
            ['id' => 2, 'name' => 'Bob'],
            ['id' => 3, 'name' => 'Jon', 'score' => 75],
            ['id' => 4, 'score' => 64],
        ];
        $authentication = Authentication::createClientCredentials('not under test', 'not under test');
        $client = new Client($adapter, $authentication, 'not under test');
        $collection = new Collection($client, 'basic', ['limit' => 3]);
        $this->assertSame(
            [
                ['name' => 'Sam', 'score' => 99],
                ['name' => 'Bob', 'score' => null],
                ['name' => 'Jon', 'score' => 75],
                ['name' => null, 'score' => 64],
            ],
            iterator_to_array($collection->select(['name', 'score']))
        );
    }
}
