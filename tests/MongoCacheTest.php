<?php

namespace DominionEnterprises\Api;

/**
 * Defines unit tests for the MongoCache class
 *
 * @coversDefaultClass \DominionEnterprises\Api\MongoCache
 * @covers ::<private>
 * @uses \DominionEnterprises\Api\MongoCache::__construct
 * @uses \DominionEnterprises\Api\Request
 * @uses \DominionEnterprises\Api\Response
 */
final class MongoCacheTest extends \PHPUnit_Framework_TestCase
{
    const MONGO_DB = 'testing';
    const MONGO_COLLECTION = 'cache';

    private $_collection;
    private $_mongoUrl;

    public function setUp()
    {
        $this->_mongoUrl = getenv('TESTING_MONGO_URL') ?: 'mongodb://localhost:27017';
        $mongo = new \MongoClient($this->_mongoUrl);
        $this->_collection = $mongo->selectDb(self::MONGO_DB)->selectCollection(self::MONGO_COLLECTION);
        $this->_collection->drop();
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

        $cache = new MongoCache($this->_mongoUrl, self::MONGO_DB, self::MONGO_COLLECTION);

        $request = new Request('a url', 'not under test');
        $response = new Response(200, $expected['headers'], $expected['body']);

        $cache->set($request, $response);

        $this->assertSame(1, $this->_collection->count());
        $actual = $this->_collection->findOne();
        $this->assertSame(strtotime($expires), $actual['expires']->sec);
        unset($actual['expires']);
        $this->assertSame($expected, $actual);
    }

    /**
     * @test
     * @covers ::set
     */
    public function set_withBody()
    {
        $expires = 'Sun, 30 Jun 2013 13:53:50 GMT';
        $expected = [
            '_id' => 'a url| with a body',
            'httpCode' => 200,
            'body' => ['doesnt' => 'matter'],
            'headers' => ['Expires' => [$expires], 'Another' => ['Header']],
        ];

        $cache = new MongoCache($this->_mongoUrl, self::MONGO_DB, self::MONGO_COLLECTION);

        $request = new Request('a url', 'not under test', ' with a body');
        $response = new Response(200, $expected['headers'], $expected['body']);

        $cache->set($request, $response);

        $this->assertSame(1, $this->_collection->count());
        $actual = $this->_collection->findOne();
        $this->assertSame(strtotime($expires), $actual['expires']->sec);
        unset($actual['expires']);
        $this->assertSame($expected, $actual);
    }

    /**
     * Verifies response is not cached if no Expires header is present
     *
     * @test
     * @covers ::set
     * @uses \DominionEnterprises\Api\MongoCache::get
     */
    public function set_noExpires()
    {
        $cache = new MongoCache($this->_mongoUrl, self::MONGO_DB, self::MONGO_COLLECTION);
        $request = new Request('a url', 'not under test');
        $response = new Response(200, ['doesnt' => ['matter']]);
        $cache->set($request, $response);
        $this->assertSame(0, $this->_collection->count());
        $this->assertNull($cache->get($request));
    }

    /**
     * @test
     * @covers ::get
     */
    public function get()
    {
        $document = [ '_id' => 'a url|', 'httpCode' => 200, 'body' => ['doesnt' => 'matter'], 'headers' => ['key' => ['value']]];
        $this->_collection->insert($document);

        $cache = new MongoCache($this->_mongoUrl, self::MONGO_DB, self::MONGO_COLLECTION);

        $actual = $cache->get(new Request('a url', 'not under test'));

        $expected = new Response(200, $document['headers'], $document['body']);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     * @covers ::get
     * @uses \DominionEnterprises\Api\MongoCache::set
     */
    public function get_notFound()
    {
        $expected = [
            '_id' => 'a url|',
            'httpCode' => 200,
            'body' => ['doesnt' => 'matter'],
            'headers' => ['Expires' => ['Sun, 30 Jun 2013 13:53:50 GMT'], 'Another' => ['Header']],
        ];

        $cache = new MongoCache($this->_mongoUrl, self::MONGO_DB, self::MONGO_COLLECTION);

        $request = new Request('a url', 'not under test');
        $response = new Response(200, $expected['headers'], $expected['body']);

        $cache->set($request, $response);

        $this->_collection->remove(['_id' => $expected['_id']]);

        $this->assertNull($cache->get($request));
    }

    /**
     * Verifies the expires TTL index
     *
     * @test
     * @covers ::ensureIndexes
     * @uses \DominionEnterprises\Api\MongoCache::set
     * @uses \DominionEnterprises\Api\MongoCache::get
     */
    public function get_expired()
    {
        $expected = [
            '_id' => 'a url',
            'httpCode' => 200,
            'body' => ['doesnt' => 'matter'],
            'headers' => ['Expires' => ['Sun, 30 Jun 2011 13:53:50 GMT'], 'Another' => ['Header']],
        ];

        $cache = new MongoCache($this->_mongoUrl, self::MONGO_DB, self::MONGO_COLLECTION);
        $cache->ensureIndexes();

        $request = new Request('a url', 'not under test');
        $response = new Response(200, $expected['headers'], $expected['body']);

        $cache->set($request, $response);

        $endTime = time() + 121;
        while (time() <= $endTime) {
            if ($this->_collection->count() === 0) {
                break;
            }

            sleep(1);
        }

        $this->assertNull($cache->get($request));
    }
}
