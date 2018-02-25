<?php

namespace TraderInteractive\Api;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Request class
 *
 * @coversDefaultClass \TraderInteractive\Api\Request
 */
final class RequestTest extends TestCase
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
    public function constructWithInvalidParameters($url, $method, $body, $headers)
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
            [" \n ", 'method', null, []], // white space
            ['', 'method', null, []], // empty string
            // method checks
            ['url', " \n ", null, []], // white space
            ['url', '', null, []], // empty string
            // body checks
            ['url', 'method', " \n ", []], // white space
            ['url', 'method', '', []], // empty string
        ];
    }
}
