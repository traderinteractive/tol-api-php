<?php

namespace TraderInteractive\Api;

/**
 * Defines unit tests for the MongoCache class
 *
 * @coversDefaultClass \TraderInteractive\Api\MongoCache
 * @covers ::<private>
 * @uses \TraderInteractive\Api\MongoCache::__construct
 * @uses \TraderInteractive\Api\Request
 * @uses \TraderInteractive\Api\Response
 */
final class MongoCacheTest extends \PHPUnit_Framework_TestCase
{
    const MONGO_DB = 'testing';
    const MONGO_COLLECTION = 'cache';

    private $collection;
    private $mongoUrl;

    public function setUp()
    {
        $this->mongoUrl = getenv('TESTING_MONGO_URL') ?: 'mongodb://localhost:27017';
        $mongo = new \MongoDB\Client(
            $this->mongoUrl,
            [],
            ['typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array']]
        );
        $this->collection = $mongo->selectCollection(self::MONGO_DB, self::MONGO_COLLECTION);
        $this->collection->drop();
    }

    /**
     * @test
     * @covers ::__construct
     * @covers ::set
     */
    public function set()
    {
        $expires = 'Sun, 30 Jun 2013 13:53:50 GMT';
        $expected = [
            '_id' => 'a url|',
            'httpCode' => 200,
            'body' => ['doesnt' => 'matter'],
            'headers' => ['Expires' => [$expires], 'Another' => ['Header']],
        ];

        $cache = new MongoCache($this->mongoUrl, self::MONGO_DB, self::MONGO_COLLECTION);

        $request = new Request('a url', 'not under test');
        $response = new Response(200, $expected['headers'], $expected['body']);

        $cache->set($request, $response);

        $this->assertSame(1, $this->collection->count());
        $actual = $this->collection->findOne();
        $this->assertSame(strtotime($expires), $actual['expires']->toDateTime()->getTimestamp());
        unset($actual['expires']);
        $this->assertSame($expected, $actual);
    }

    /**
     * @test
     * @covers ::set
     */
    public function setWithBody()
    {
        $expires = 'Sun, 30 Jun 2013 13:53:50 GMT';
        $expected = [
            '_id' => 'a url| with a body',
            'httpCode' => 200,
            'body' => ['doesnt' => 'matter'],
            'headers' => ['Expires' => [$expires], 'Another' => ['Header']],
        ];

        $cache = new MongoCache($this->mongoUrl, self::MONGO_DB, self::MONGO_COLLECTION);

        $request = new Request('a url', 'not under test', ' with a body');
        $response = new Response(200, $expected['headers'], $expected['body']);

        $cache->set($request, $response);

        $this->assertSame(1, $this->collection->count());
        $actual = $this->collection->findOne();
        $this->assertSame(strtotime($expires), $actual['expires']->toDateTime()->getTimestamp());
        unset($actual['expires']);
        $this->assertSame($expected, $actual);
    }

    /**
     * Verifies response is not cached if no Expires header is present
     *
     * @test
     * @covers ::set
     * @uses \TraderInteractive\Api\MongoCache::get
     */
    public function setNoExpires()
    {
        $cache = new MongoCache($this->mongoUrl, self::MONGO_DB, self::MONGO_COLLECTION);
        $request = new Request('a url', 'not under test');
        $response = new Response(200, ['doesnt' => ['matter']]);
        $cache->set($request, $response);
        $this->assertSame(0, $this->collection->count());
        $this->assertNull($cache->get($request));
    }

    /**
     * @test
     * @covers ::get
     */
    public function get()
    {
        $document = [
            '_id' => 'a url|',
            'httpCode' => 200,
            'body' => ['doesnt' => 'matter'],
            'headers' => ['key' => ['value']],
        ];
        $this->collection->insertOne($document);

        $cache = new MongoCache($this->mongoUrl, self::MONGO_DB, self::MONGO_COLLECTION);

        $actual = $cache->get(new Request('a url', 'not under test'));

        $expected = new Response(200, $document['headers'], $document['body']);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     * @covers ::get
     * @uses \TraderInteractive\Api\MongoCache::set
     */
    public function getNotFound()
    {
        $expected = [
            '_id' => 'a url|',
            'httpCode' => 200,
            'body' => ['doesnt' => 'matter'],
            'headers' => ['Expires' => ['Sun, 30 Jun 2013 13:53:50 GMT'], 'Another' => ['Header']],
        ];

        $cache = new MongoCache($this->mongoUrl, self::MONGO_DB, self::MONGO_COLLECTION);

        $request = new Request('a url', 'not under test');
        $response = new Response(200, $expected['headers'], $expected['body']);

        $cache->set($request, $response);

        $this->collection->deleteOne(['_id' => $expected['_id']]);

        $this->assertNull($cache->get($request));
    }

    /**
     * Verifies the expires TTL index
     *
     * @test
     * @covers ::ensureIndexes
     * @uses \TraderInteractive\Api\MongoCache::set
     * @uses \TraderInteractive\Api\MongoCache::get
     */
    public function getExpired()
    {
        $expected = [
            '_id' => 'a url',
            'httpCode' => 200,
            'body' => ['doesnt' => 'matter'],
            'headers' => ['Expires' => ['Sun, 30 Jun 2011 13:53:50 GMT'], 'Another' => ['Header']],
        ];

        $cache = new MongoCache($this->mongoUrl, self::MONGO_DB, self::MONGO_COLLECTION);
        $cache->ensureIndexes();

        $request = new Request('a url', 'not under test');
        $response = new Response(200, $expected['headers'], $expected['body']);

        $cache->set($request, $response);

        $endTime = time() + 121;
        while (time() <= $endTime) {
            if ($this->collection->count() === 0) {
                break;
            }

            sleep(1);
        }

        $this->assertNull($cache->get($request));
    }
}
