<?php

namespace TraderInteractive\Api;

use GuzzleHttp\Psr7;
use Psr\Http\Message\ResponseInterface;
use SubjectivePHP\Psr\SimpleCache\Serializer\SerializerInterface;

final class ResponseSerializer implements SerializerInterface
{
    /**
     * @var array
     */
    const REQUIRED_CACHE_KEYS = [
        'statusCode',
        'headers',
        'body',
    ];

    /**
     * Unserializes cached data into the original psr response.
     *
     * @param mixed $data The data to unserialize.
     *
     * @return Psr7\Response
     *
     * @throws SerializerException Thrown if the given value cannot be unserialized.
     */
    public function unserialize($data)
    {
        $this->validateCachedData($data);
        $statusCode = $data['statusCode'];
        $headers = $data['headers'];
        $body = $data['body'];
        if ($body !== '') {
            $body = Psr7\Utils::streamFor($body);
        }

        return new Psr7\Response($statusCode, $headers, $body);
    }

    /**
     * Serializes the given psr response for storage in caching.
     *
     * @param Psr7\Response $response The http response message to serialize for caching.
     *
     * @return mixed The result of serializing the given $data.
     *
     * @throws SerializerException Thrown if the given value cannot be serialized for caching.
     */
    public function serialize($response)
    {
        if (!($response instanceof ResponseInterface)) {
            $type = is_object($response) ? get_class($response) : gettype($response);
            throw new SerializerException("Cannot serialize value of type '{$type}'");
        }

        return [
            'statusCode' => $response->getStatusCode(),
            'headers' => $response->getHeaders(),
            'body' => $response->getBody()->getContents(),
        ];
    }

    private function validateCachedData($data)
    {
        if (!is_array($data)) {
            throw new SerializerException('Serialized data is not an array');
        }

        foreach (self::REQUIRED_CACHE_KEYS as $key) {
            if (!array_key_exists($key, $data)) {
                throw new SerializerException("Data is missing '{$key}' value");
            }
        }
    }
}
