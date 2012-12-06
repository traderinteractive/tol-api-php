<?php

namespace DominionEnterprises\Api;

/**
 * Defines unit tests for the PredisCache class
 *
 * @coversDefaultClass \DominionEnterprises\Api\PredisCache
 * @covers ::<private>
 * @uses \DominionEnterprises\Api\Request
 * @uses \DominionEnterprises\Api\Response
 * @uses \DominionEnterprises\Api\PredisCache::__construct
 */
final class PredisCacheTest extends \PHPUnit_Framework_TestCase
{
    private $_client;

    public function setUp()
    {
        $redisUrl = getenv('TESTING_REDIS_URL') ?: null;
        $this->_client = new \Predis\Client($redisUrl);
        $this->_client->flushall();
    }

    /**
     * @test
     * @covers ::__construct
     * @covers ::set
     */
    public function setBasicUsage()
    {
        $expires = 'Sun, 30 Jun 2043 13:53:50 GMT';
        $expected = array(
            'httpCode' => 200,
            'headers' => array('Expires' => array($expires), 'Another' => array('Header')),
            'body' => array('doesnt' => 'matter'),
        );

        $cache = new PredisCache($this->_client);

        $request = new Request('a url', 'not under test');
        $response = new Response(200, $expected['headers'], $expected['body']);

        $cache->set($request, $response);

        $actual = json_decode($this->_client->get('a url:'), true);
        $this->assertSame($expected, $actual);
    }

    /**
     * Verifies response is not cached if no Expires header is present
     *
     * @test
     * @covers ::set
     * @uses \DominionEnterprises\Api\PredisCache::get
     */
    public function setNoExpires()
    {
        $cache = new PredisCache($this->_client);
        $request = new Request('a url', 'not under test');
        $response = new Response(200, array('doesnt' => array('matter')));
        $cache->set($request, $response);
        $this->assertNull($cache->get($request));
    }

    /**
     * @test
     * @covers ::get
     */
    public function getBasicUsage()
    {
        $document = array(
            '_id' => 'a url',
            'httpCode' => 200,
            'body' => array('doesnt' => 'matter'),
            'headers' => array('key' => array('value')),
        );
        $this->_client->set(
            'a url:',
            json_encode(
                [
                    'httpCode' => 200,
                    'headers' => array('key' => array('value')),
                    'body' => array('doesnt' => 'matter'),
                ]
            )
        );

        $cache = new PredisCache($this->_client);

        $actual = $cache->get(new Request('a url', 'not under test'));

        $expected = new Response(200, $document['headers'], $document['body']);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     * @covers ::get
     * @uses \DominionEnterprises\Api\PredisCache::set
     */
    public function getNotFound()
    {
        $cache = new PredisCache($this->_client);

        $request = new Request('a url', 'not under test');
        $response = new Response(
            200,
            array('Expires' => array('Sun, 30 Jun 2043 13:53:50 GMT'), 'Another' => array('Header')),
            array('doesnt' => 'matter')
        );

        $cache->set($request, $response);

        $this->_client->del('a url:');

        $this->assertNull($cache->get($request));
    }

    /**
     * Verifies the expires TTL index
     *
     * @test
     * @covers ::get
     * @uses \DominionEnterprises\Api\PredisCache::set
     */
    public function getExpired()
    {
        $cache = new PredisCache($this->_client);

        $request = new Request('a url', 'not under test');
        $response = new Response(
            200,
            array('Expires' => array('Sun, 30 Jun 2011 13:53:50 GMT'), 'Another' => array('Header')),
            array('doesnt' => 'matter')
        );

        $cache->set($request, $response);

        $this->assertNull($cache->get($request));
    }
}
