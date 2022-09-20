<?php

namespace TraderInteractive\Api;

use GuzzleHttp\Psr7;
use PHPUnit\Framework\TestCase;

/**
 * Defines unit tests for the Response class
 *
 * @coversDefaultClass \TraderInteractive\Api\Response
 * @covers ::__construct
 * @covers ::<private>
 */
final class ResponseTest extends TestCase
{
    /**
     * @test
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
     * @covers ::__construct
     */
    public function constructWithInvalidHttpCode()
    {
        $this->expectException(\InvalidArgumentException::class);
        new Response(99, [], []);
    }

    /**
     * @test
     * @covers ::fromPsr7Response
     *
     * @return void
     */
    public function fromPsr7Response()
    {
        $response = Response::fromPsr7Response(
            new Psr7\Response(200, ['Content-Type' => 'application/json'], json_encode(['foo' => 'bar']))
        );

         $this->assertSame(200, $response->getHttpCode());
         $this->assertSame(['Content-Type' => ['application/json']], $response->getResponseHeaders());
         $this->assertSame(['foo' => 'bar'], $response->getResponse());
    }

    /**
     * @test
     * @covers ::fromPsr7Response
     *
     * @return void
     */
    public function fromPsr7ResponseEmptyBody()
    {
        $response = Response::fromPsr7Response(
            new Psr7\Response(200, ['Content-Type' => 'application/json'], null)
        );

         $this->assertSame(200, $response->getHttpCode());
         $this->assertSame(['Content-Type' => ['application/json']], $response->getResponseHeaders());
         $this->assertSame([], $response->getResponse());
    }

    /**
     * @test
     * @covers ::fromPsr7Response
     *
     * @return void
     */
    public function fromPsr7ResponseWithInvalidJson()
    {
        $this->expectException(\UnexpectedValueException::class);
        Response::fromPsr7Response(
            new Psr7\Response(200, ['Content-Type' => 'application/json'], '{"foo":"bar"')
        );
    }
}
