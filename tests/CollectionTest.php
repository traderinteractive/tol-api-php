<?php

namespace TraderInteractive\Api;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Collection class
 *
 * @coversDefaultClass \TraderInteractive\Api\Collection
 */
final class CollectionTest extends TestCase
{
    const DEFAULT_RESULT_SET = [
        ['id' => '0', 'key' => 0],
        ['id' => '1', 'key' => 1],
        ['id' => '2', 'key' => 2],
        ['id' => '3', 'key' => 3],
        ['id' => '4', 'key' => 4],
    ];

    /**
     * @test
     * @covers ::__construct
     * @covers ::rewind
     * @covers ::valid
     * @covers ::key
     * @covers ::current
     * @covers ::next
     * @covers ::count
     */
    public function directUsage()
    {
        $collection = new Collection($this->getClient(), 'basic', ['limit' => 3]);
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
        $collection = new Collection($this->getClient(), 'basic', ['limit' => 3]);
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
        $collection = new Collection($this->getClient(), 'basic', ['limit' => 3]);
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
        $collection = new Collection($this->getClient(), 'basic', ['limit' => 3]);
        $collection->next();
        $collection->next();
        $this->assertSame(['id' => '1', 'key' => 1], $collection->current());
    }

    /**
     * Verifies count() lazy loads the next result
     *
     * @test
     * @covers ::count
     *
     * THIS FUNCTION CANNOT BE NAMED COUNT OR PHPUNIT EXPLODES
     */
    public function countOfResult()
    {
        $collection = new Collection($this->getClient(), 'basic', ['limit' => 3]);
        $this->assertSame(5, $collection->count());
    }

    /**
     * Verifies key() lazy loads the next result
     *
     * @test
     * @covers ::key
     */
    public function key()
    {
        $collection = new Collection($this->getClient(), 'basic', ['limit' => 3]);
        $this->assertSame(0, $collection->key());
    }

    /**
     * Verifies current() lazy loads the next result
     *
     * @test
     * @covers ::current
     */
    public function current()
    {
        $collection = new Collection($this->getClient(), 'basic', ['limit' => 3]);
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
        $collection = new Collection($this->getClient([]), 'empty');
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
        $collection = new Collection($this->getClient([]), 'empty');
        $collection->key();
    }

    /**
     * @test
     */
    public function multiIteration()
    {
        $collection = new Collection($this->getClient(), 'basic', ['limit' => 3]);

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
        $collection = new Collection($this->getClient([]), 'empty');
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
        $collection = new Collection($this->getClient([['id' => '0', 'key' => 0]]), 'single');
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
        $collection = new Collection($this->getClient(), 'basic', ['limit' => 3]);
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
        $collection = new Collection($this->getClient(), 'basic', ['limit' => 3]);
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
        $client = $this->getClient(
            [
                ['id' => 1, 'name' => 'Sam', 'score' => 99],
                ['id' => 2, 'name' => 'Bob', 'score' => 83],
                ['id' => 3, 'name' => 'Jon', 'score' => 75],
                ['id' => 4, 'name' => 'Ted', 'score' => 64],
            ]
        );
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
        $client = $this->getClient(
            [
                ['id' => 1, 'name' => 'Sam', 'score' => 99],
                ['id' => 2, 'name' => 'Bob'],
                ['id' => 3, 'name' => 'Jon', 'score' => 75],
                ['id' => 4, 'score' => 64],
            ]
        );
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

    private function getClient(array $items = self::DEFAULT_RESULT_SET) : ClientInterface
    {
        $callback = function (string $resource, array $filters) use ($items) {
            $offset = (int)($filters['offset'] ?? 0);
            $limit = (int)($filters['limit'] ?? 2);

            $result = json_encode(
                [
                    'pagination' => [
                        'offset' => $offset,
                        'total' => count($items),
                        'limit' => min($limit, count($items)),
                    ],
                    'result' => array_slice($items, $offset, $limit),
                ]
            );

            return new Response(200, ['Content-Type' => ['application/json']], $result);
        };

        $mock = $this->getMockBuilder(ClientInterface::class)->getMock();
        $mock->method('index')->will($this->returnCallback($callback));

        return $mock;
    }
}
