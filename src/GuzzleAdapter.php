<?php

namespace TraderInteractive\Api;

use ArrayObject;
use DominionEnterprises\Util;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface as GuzzleClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise;
use Psr\Http\Message\ResponseInterface;

/**
 * Concrete implentation of Adapter interface
 */
final class GuzzleAdapter implements AdapterInterface
{
    /**
     * Collection of Promise\PromiseInterface instances with keys matching what was given from start().
     *
     * @var array
     */
    private $promises = [];

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
     * @var GuzzleClientInterface
     */
    private $client;

    public function __construct(GuzzleClientInterface $client = null)
    {
        $this->exceptions = new ArrayObject();
        $this->client = $client ?? new GuzzleClient(
            [
                'allow_redirects' => false, //stop guzzle from following redirects
                'http_errors' => false, //only for 400/500 error codes, actual exceptions can still happen
            ]
        );
    }

    /**
     * @see AdapterInterface::start()
     */
    public function start(Request $request) : string
    {
        $handle = uniqid();
        $this->promises[$handle] = $this->client->requestAsync(
            $request->getMethod(),
            $request->getUrl(),
            [
                'headers' => $request->getHeaders(),
                'body' => $request->getBody(),
            ]
        );

        return $handle;
    }

    /**
     * @see Adapter::end()
     *
     * @throws \InvalidArgumentException
     */
    public function end(string $endHandle) : ResponseInterface
    {
        $results = $this->fulfillPromises($this->promises, $this->exceptions);
        foreach ($results as $handle => $response) {
            try {
                $body = []; //default to empty body
                $contents = (string)$response->getBody();
                if (trim($contents) !== '') {
                    $body = json_decode($contents, true);
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

        $this->promises = [];

        if (array_key_exists($endHandle, $this->exceptions)) {
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
     * Helper method to execute all guzzle promises.
     *
     * @param array $promises
     * @param array $exceptions
     *
     * @return array Array of fulfilled PSR7 responses.
     */
    private function fulfillPromises(array $promises, ArrayObject $exceptions) : array
    {
        if (empty($promises)) {
            return [];
        }

        $results = new ArrayObject();
        Promise\each(
            $this->promises,
            function (ResponseInterface $response, $index) use ($results) {
                $results[$index] = $response;
            },
            function (RequestException $e, $index) use ($exceptions) {
                $exceptions[$index] = $e;
            }
        )->wait();

        return $results->getArrayCopy();
    }
}
