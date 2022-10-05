<?php

namespace TraderInteractive\Api;

use Fig\Http\Message\StatusCodeInterface as StatusCodes;
use GuzzleHttp\Psr7;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

/**
 * @coversDefaultClass \TraderInteractive\Api\ResponseSerializer
 * @covers ::<private>
 */
final class ResponseSerializerTest extends TestCase
{
    /**
     * @param ResponseInterface $response     The PSR response to serialize.
     * @param array             $expectedData The expected serialized data.
     * @test
     * @covers ::serialize
     *
     * @dataProvider provideValidSerializationData
     */
    public function serializeRepsonse(ResponseInterface $response, array $expectedData)
    {
        $serializer = new ResponseSerializer();
        $actualData = $serializer->serialize($response);
        $this->assertSame($expectedData, $actualData);
    }

    /**
     * @param ResponseInterface $expectedResponse The expected PSR response after unserializing.
     * @param array             $serailizedData   The data to unserialize.
     *
     * @test
     * @covers ::unserialize
     * @dataProvider provideValidSerializationData
     */
    public function unserializeResponse(ResponseInterface $expectedResponse, array $serailizedData)
    {
        $serializer = new ResponseSerializer();
        $actualResponse = $serializer->unserialize($serailizedData);
        $this->assertSame($expectedResponse->getStatusCode(), $actualResponse->getStatusCode());
        $this->assertSame($expectedResponse->getHeaders(), $actualResponse->getHeaders());
        $this->assertSame($expectedResponse->getBody()->getContents(), $actualResponse->getBody()->getContents());
    }

    /**
     * @return array
     */
    public function provideValidSerializationData() : array
    {
        return [
            [
                'response' => new Psr7\Response(
                    StatusCodes::STATUS_OK,
                    ['Content-Type' => ['application/json']],
                    Psr7\Utils::streamFor('{"success": true}')
                ),
                'data' => [
                    'statusCode' => StatusCodes::STATUS_OK,
                    'headers' => ['Content-Type' => ['application/json']],
                    'body' => '{"success": true}',
                ],
            ],
            [
                'response' => new Psr7\Response(
                    StatusCodes::STATUS_NO_CONTENT,
                    ['X-PHPUnit' => ['testing']]
                ),
                'data' => [
                    'statusCode' => StatusCodes::STATUS_NO_CONTENT,
                    'headers' => ['X-PHPUnit' => ['testing']],
                    'body' => '',
                ],
            ],
        ];
    }

    /**
     * @test
     * @covers ::serialize
     */
    public function serializeAcceptsOnlyResponses()
    {
        $this->expectException(\TraderInteractive\Api\SerializerException::class);
        $this->expectExceptionMessage("Cannot serialize value of type 'array'");
        $serializer = new ResponseSerializer();
        $serializer->serialize(['foo']);
    }

    /**
     * @param mixed  $serializedData           The data to be unserialized
     * @param string $expectedExceptionMessage The expected message for the SerializerException
     *
     * @test
     * @covers ::unserialize
     * @dataProvider provideInvalidSerializedData
     */
    public function unserializeEnforcesKeyRequirements($serializedData, string $expectedExceptionMessage)
    {
        $this->expectException(SerializerException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);
        $serializer = new ResponseSerializer();
        $serializer->unserialize($serializedData);
    }

    /**
     * @return array
     */
    public function provideInvalidSerializedData() : array
    {
        return [
            'missing statusCode' => [
                'data' => [
                    'headers' => ['X-PHPUnit' => ['testing']],
                    'body' => '',
                ],
                'message' => "Data is missing 'statusCode' value",
            ],
            'missing headers' => [
                'data' => [
                    'statusCode' => StatusCodes::STATUS_NO_CONTENT,
                    'body' => '',
                ],
                'message' => "Data is missing 'headers' value",
            ],
            'missing body' => [
                'data' => [
                    'statusCode' => StatusCodes::STATUS_NO_CONTENT,
                    'headers' => ['X-PHPUnit' => ['testing']],
                ],
                'message' => "Data is missing 'body' value",
            ],
            'data is not an array' => [
                'data' => '{"foo": "bar"}',
                'message' => 'Serialized data is not an array',
            ],
        ];
    }
}
