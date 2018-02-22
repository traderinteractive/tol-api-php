<?php

namespace TraderInteractive\Api;
use TraderInteractive\Util\Http;

/**
 * Defines unit tests for the GuzzleAdapter class
 *
 * @coversDefaultClass \TraderInteractive\Api\GuzzleAdapter
 * @covers ::<private>
 */
final class GuzzleAdapterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @group unit
     * @covers ::__construct
     * @covers ::start
     * @covers ::end
     * @expectedException \Exception
     */
    public function requestThrowsOnUnsupporetedMethod()
    {
        $adapter = new GuzzleAdapter();
        $request = new Request('a resource', 'SILLY', null, []);
        $adapter->end($adapter->start($request));
    }

    /**
     * @test
     * @covers ::__construct
     * @covers ::start
     * @covers ::end
     */
    public function nonJsonResponseInOneUrl()
    {
        $adapter = new GuzzleAdapter();

        $handleOne = $adapter->start(new Request('http://www.google.com', 'GET'));
        $handleTwo = $adapter->start(
            new Request('https://raw.githubusercontent.com/dominionenterprises/tol-api-php/master/composer.json', 'GET')
        );

        try {
            $adapter->end($handleOne);
            $this->fail();
        } catch (\UnexpectedValueException $e) {
        } catch (\Exception $e) {
            $this->fail();
        }

        $responseTwo = $adapter->end($handleTwo);
        $this->assertSame(200, $responseTwo->getHttpCode());
    }

    /**
     * @test
     * @covers ::__construct
     * @covers ::start
     * @covers ::end
     */
    public function badProtocolInOneUrl()
    {
        $adapter = new GuzzleAdapter();

        $handleOne = $adapter->start(new Request('silly://localhost', 'GET'));
        $handleTwo = $adapter->start(
            new Request('https://raw.githubusercontent.com/dominionenterprises/tol-api-php/master/composer.json', 'GET')
        );

        try {
            $adapter->end($handleOne);
            $this->fail();
        } catch (\Exception $e) {
        }

        $responseTwo = $adapter->end($handleTwo);
        $this->assertSame(200, $responseTwo->getHttpCode());
    }

    /**
     * @test
     * @covers ::__construct
     * @covers ::end
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage $endHandle not found
     */
    public function badHandle()
    {
        (new GuzzleAdapter())->end(0);
    }

    /**
     * @test
     * @covers ::end
     * @uses \TraderInteractive\Api\GuzzleAdapter::start
     */
    public function getHeaders()
    {
        $adapter = new GuzzleAdapter();
        $response = $adapter->end(
            $adapter->start(new Request('https://raw.githubusercontent.com/dominionenterprises/tol-api-php/master/composer.json', 'GET'))
        );

        foreach ($response->getResponseHeaders() as $header) {
            $this->assertInternalType('array', $header);
        }
    }
}
