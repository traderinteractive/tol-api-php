<?php

namespace TraderInteractive\Api;

/**
 * Unit tests for the Request class
 *
 * @coversDefaultClass \TraderInteractive\Api\Request
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
        $headers = ['key' => 'value'];
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
        return [
            // url checks
            [null, 'method', null, []], // null
            [" \n ", 'method', null, []], // white space
            ['', 'method', null, []], // empty string
            [1, 'method', null, []], // not a string
            // method checks
            ['url', null, null, []], // null
            ['url', " \n ", null, []], // white space
            ['url', '', null, []], // empty string
            ['url', 1, null, []], // not a string
            // body checks
            ['url', 'method', " \n ", []], // white space
            ['url', 'method', '', []], // empty string
            ['url', 'method', 1, []], // not a string
        ];
    }
}
