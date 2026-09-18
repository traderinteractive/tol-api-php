<?php

namespace TraderInteractive\Api;

use ArrayObject;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface as GuzzleClientInterface;
use GuzzleHttp\Pool;
use GuzzleHttp\Promise;
use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Throwable;
use TraderInteractive\Util;

/**
 * Concrete implentation of Adapter interface
 */
final class GuzzleAdapter implements AdapterInterface
{
    /**
     * @var int
     */
    const DEFAULT_CONCURRENCY_LIMIT = PHP_INT_MAX;

    /**
     * Collection of RequestInterface instances with keys matching what was given from start().
     *
     * @var array
     */
    private $requests = [];

    /**
     * Collection of Api\Response with keys matching what was given from start().
     *
     * @var array
     */
    private $responses = [];

    /**
     * Collection of \Exception with keys matching what was given from start().
     *
     * @var ArrayObject
     */
    private $exceptions;

    /**
     * @var int
     */
    private $concurrencyLimit;

    /**
     * @var GuzzleClientInterface
     */
    private $client;

    public function __construct(
        GuzzleClientInterface $client = null,
        int $concurrencyLimit = self::DEFAULT_CONCURRENCY_LIMIT
    ) {
        $this->exceptions = new ArrayObject();
        $this->client = $client ?? new GuzzleClient(
            [
                'allow_redirects' => false, //stop guzzle from following redirects
                'http_errors' => false, //only for 400/500 error codes, actual exceptions can still happen
            ]
        );
        $this->concurrencyLimit = $concurrencyLimit;
        if ($this->concurrencyLimit <= 0) {
            throw new InvalidArgumentException('$concurrencyLimit must be a positive integer');
        }
    }

    /**
     * @see AdapterInterface::start()
     */
    public function start(RequestInterface $request) : string
    {
        $handle = uniqid();
        $this->requests[$handle] = $request;
        return $handle;
    }

    /**
     * @see Adapter::end()
     *
     * @throws \InvalidArgumentException
     */
    public function end(string $endHandle) : ResponseInterface
    {
        $results = $this->fulfillPromises($this->requests, $this->exceptions);
        foreach ($results as $handle => $response) {
            try {
                $contents = (string)$response->getBody();
                if (trim($contents) !== '') {
                    json_decode($contents, true);
                    Util::ensure(
                        JSON_ERROR_NONE,
                        json_last_error(),
                        '\UnexpectedValueException',
                        [json_last_error_msg()]
                    );
                }

                $this->responses[$handle] = $response;
            } catch (\Exception $e) {
                $this->exceptions[$handle] = $e;
            }
        }

        $this->requests = [];

        if ($this->exceptions->offsetExists($endHandle)) {
            $exception = $this->exceptions[$endHandle];
            unset($this->exceptions[$endHandle]);
            throw $exception;
        }

        if (array_key_exists($endHandle, $this->responses)) {
            $response = $this->responses[$endHandle];
            unset($this->responses[$endHandle]);
            return $response;
        }

        throw new \InvalidArgumentException('$endHandle not found');
    }

    /**
     * @return ResponseInterface[]
     */
    private function fulfillPromises(array $requests, ArrayObject $exceptions): array
    {
        if (empty($requests)) {
            return [];
        }

        $responses = new ArrayObject();
        $pool = new Pool(
            $this->client,
            $requests,
            [
                'concurrency' => $this->concurrencyLimit,
                Promise\Promise::FULFILLED => function (ResponseInterface $response, $index) use ($responses) {
                    $responses[$index] = $response;
                },
                Promise\Promise::REJECTED => function ($reason, $index) use ($exceptions) {
                    $exceptions[$index] = $reason instanceof Throwable
                        ? $reason
                        : new RuntimeException('Request rejected with non throwable reason');
                },
            ]
        );
        $promise = $pool->promise();
        $promise->wait();

        return $responses->getArrayCopy();
    }
}
