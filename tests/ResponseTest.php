<?php

namespace TraderInteractive\Api;

/**
 * Defines unit tests for the Response class
 *
 * @coversDefaultClass \TraderInteractive\Api\Response
 */
final class ResponseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @group unit
     * @covers ::__construct
     * @covers ::getHttpCode
     */
    public function getHttpCode()
    {
        $httpCode = 200;
        $body = ['doesnt' => 'matter'];
        $headers = ['Content-Type' => ['text/json']];
        $response = new Response($httpCode, $headers, $body);
        $this->assertSame($httpCode, $response->getHttpCode());
    }

    /**
     * @test
     * @group unit
     * @covers ::__construct
     * @covers ::getResponse
     */
    public function getResponse()
    {
        $httpCode = 200;
        $body = ['doesnt' => 'matter'];
        $headers = ['Content-Type' => ['text/json']];
        $response = new Response($httpCode, $headers, $body);
        $this->assertSame($body, $response->getResponse());
    }

    /**
     * @test
     * @group unit
     * @covers ::__construct
     * @covers ::getResponseHeaders
     */
    public function getResponseHeaders()
    {
        $httpCode = 200;
        $body = ['doesnt' => 'matter'];
        $headers = ['Content-Type' => ['text/json']];
        $response = new Response($httpCode, $headers, $body);
        $this->assertSame($headers, $response->getResponseHeaders());
    }

    /**
     * @test
     * @group unit
     * @covers ::__construct
     * @dataProvider constructorBadData
     * @expectedException \InvalidArgumentException
     */
    public function construct_withInvalidParameters($httpCode, $rawResponse, $rawHeaders)
    {
        $response = new Response($httpCode, $rawResponse, $rawHeaders);
    }

    /**
     * data provider
     *
     * @return array
     */
    public function constructorBadData()
    {
        $body = ['doesnt' => 'matter'];
        return [
            // http code checks
            ['NaN', ['doesnt' => ['matter']], $body],  // not a number
            [99, ['doesnt' => ['matter']], $body],  // less than 100
            [601, ['doesnt' => ['matter']], $body],  // greater than 600
            [200, ['doesnt' => 'NOT AN ARRAY'], $body],  // header value not an array
        ];
    }
}
