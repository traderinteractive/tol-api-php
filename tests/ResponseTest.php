<?php

namespace DominionEnterprises\Api;

/**
 * Defines unit tests for the Response class
 *
 * @coversDefaultClass \DominionEnterprises\Api\Response
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
        $body = array('doesnt' => 'matter');
        $headers = array('Content-Type' => array('text/json'));
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
        $body = array('doesnt' => 'matter');
        $headers = array('Content-Type' => array('text/json'));
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
        $body = array('doesnt' => 'matter');
        $headers = array('Content-Type' => array('text/json'));
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
        $body = array('doesnt' => 'matter');
        return array(
            // http code checks
            array('NaN', array('doesnt' => array('matter')), $body),  // not a number
            array(99, array('doesnt' => array('matter')), $body),  // less than 100
            array(601, array('doesnt' => array('matter')), $body),  // greater than 600
            array(200, array('doesnt' => 'NOT AN ARRAY'), $body),  // header value not an array
        );
    }
}
