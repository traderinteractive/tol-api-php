<?php

namespace TraderInteractive\Api;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use UnexpectedValueException;

/**
 * Immutable representation of a response to an API request.
 */
final class Response
{
    /**
     * @var integer
     */
    private $httpCode;

    /**
     * @var array
     */
    private $headers;

    /**
     * @var array
     */
    private $body;

    /**
     * Create a new instance of Response
     *
     * @param int   $httpCode The http status of the response.
     * @param array $headers  An array where each header key has an array of values
     * @param array $body    The response from the API
     *
     * @throws InvalidArgumentException Throw if $httpCode is not an integer between 100 and 600
     */
    public function __construct(int $httpCode = 300, array $headers = [], array $body = [])
    {
        if ($httpCode < 100 || $httpCode > 600) {
            throw new InvalidArgumentException('$httpCode must be an integer >= 100 and <= 600');
        }

        $this->httpCode = $httpCode;
        $this->headers = $headers;
        $this->body = $body;
    }

    /**
     * Returns the HTTP status code of the response.
     *
     * @return integer
     */
    public function getHttpCode() : int
    {
        return $this->httpCode;
    }

    /**
     * Returns an array representing the response from the API.
     *
     * @return array
     */
    public function getResponse() : array
    {
        return $this->body;
    }

    /**
     * Returns the parsed response headers from the API.
     *
     * @return array Array where each header key has an array of values.
     */
    public function getResponseHeaders() : array
    {
        return $this->headers;
    }

    public static function fromPsr7Response(ResponseInterface $response)
    {
        return new self(
            $response->getStatusCode(),
            $response->getHeaders(),
            self::decodeBody($response->getBody())
        );
    }

    private static function decodeBody(string $json) : array
    {
        if (trim($json) == '') {
            return [];
        }

        try {
            return json_decode($json, true);
        } finally {
            self::ensureJson();
        }
    }//@codeCoverageIgnore Unreachable line

    private static function ensureJson()
    {
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new UnexpectedValueException(
                sprintf(
                    'Could not parse response body. Error: %s',
                    json_last_error_msg()
                )
            );
        }
    }
}
