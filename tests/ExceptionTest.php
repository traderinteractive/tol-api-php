<?php

namespace TraderInteractive\Api;

use GuzzleHttp\Psr7;
use PHPUnit\Framework\TestCase;

/**
 * Defines unit tests for the Exception class
 *
 * @coversDefaultClass \TraderInteractive\Api\Exception
 * @covers ::__construct
 */
final class ExceptionTest extends TestCase
{
    /**
     * @test
     * @covers ::getResponse
     */
    public function getResponseReturnsResponse()
    {
        $httpCode = 200;
        $body = ['doesnt' => 'matter'];
        $headers = ['Content-Type' => ['text/json']];
        $response = new Response($httpCode, $headers, $body);
        $exception = new Exception('the error message', $response);
        $this->assertSame($response, $exception->getResponse());
    }
}
