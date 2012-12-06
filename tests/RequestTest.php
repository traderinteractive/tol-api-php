<?php

namespace DominionEnterprises\Api;

/**
 * Unit tests for the Request class
 *
 * @coversDefaultClass \DominionEnterprises\Api\Request
 */
final class RequestTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @group unit
     * @covers ::__construct
     * @covers ::getUrl
     * @covers ::getBody
     * @covers ::getMethod
     * @covers ::getHeaders
     */
    public function construct()
    {
        $url = 'a url';
        $method = 'method';
        $body = 'some data';
        $headers = array('key' => 'value');
        $request = new Request($url, $method, $body, $headers);
        $this->assertSame($url, $request->getUrl());
        $this->assertSame($method, $request->getMethod());
        $this->assertSame($body, $request->getBody());
        $this->assertSame($headers, $request->getHeaders());
    }

    /**
     * @test
     * @group unit
     * @covers ::__construct
     * @dataProvider badData
     * @expectedException \InvalidArgumentException
     */
    public function construct_withInvalidParameters($url, $method, $body, $headers)
    {
        $request = new Request($url, $method, $body, $headers);
    }

    /**
     * Provides data for __construct test
     *
     * @return array
     */
    public function badData()
    {
        return array(
            // url checks
            array(null, 'method', null, array()), // null
            array(" \n ", 'method', null, array()), // white space
            array('', 'method', null, array()), // empty string
            array(1, 'method', null, array()), // not a string
            // method checks
            array('url', null, null, array()), // null
            array('url', " \n ", null, array()), // white space
            array('url', '', null, array()), // empty string
            array('url', 1, null, array()), // not a string
            // body checks
            array('url', 'method', " \n ", array()), // white space
            array('url', 'method', '', array()), // empty string
            array('url', 'method', 1, array()), // not a string
        );
    }
}
