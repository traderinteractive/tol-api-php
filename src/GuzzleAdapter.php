<?php

namespace DominionEnterprises\Api;

use ArrayObject;
use DominionEnterprises\Util;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise;
use Psr\Http\Message\ResponseInterface;

/**
 * Concrete implentation of Adapter interface
 */
final class GuzzleAdapter implements Adapter
{
    /**
     * Collection of Promise\PromiseInterface instances with keys matching what was given from start().
     *
     * @var array
     */
    private $_promises = [];

    /**
     * Collection of Api\Response with keys matching what was given from start().
     *
     * @var array
     */
    private $_responses = [];

    /**
     * Collection of \Exception with keys matching what was given from start().
     *
     * @var array
     */
    private $_exceptions = [];

    /**
     * @var \Guzzle\Http\Client
     */
    private $_client;

    public function __construct()
    {
        $this->_client = new GuzzleClient(
            [
                'allow_redirects' => false, //stop guzzle from following redirects
                'http_errors' => false, //only for 400/500 error codes, actual exceptions can still happen
            ]
        );
    }

    /**
     * @see Adapter::start()
     */
    public function start(Request $request)
    {
        $this->_promises[] = $this->_client->requestAsync(
            $request->getMethod(),
            $request->getUrl(),
            [
                'headers' => $request->getHeaders(),
                'body' => $request->getBody(),
            ]
        );

        end($this->_promises);
        return key($this->_promises);
    }

    /**
     * @see Adapter::end()
     *
     * @throws \InvalidArgumentException
     */
    public function end($endHandle)
    {
        Util::throwIfNotType(['int' => [$endHandle]]);

        $results = $this->fulfillPromises($this->_promises, $this->_exceptions);
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

                $this->_responses[$handle] = new Response($response->getStatusCode(), $response->getHeaders(), $body);
            } catch (\Exception $e) {
                $this->_exceptions[$handle] = $e;
            }
        }

        $this->_promises = [];

        if (array_key_exists($endHandle, $this->_exceptions)) {
            $exception = $this->_exceptions[$endHandle];
            unset($this->_exceptions[$endHandle]);
            throw $exception;
        }

        if (array_key_exists($endHandle, $this->_responses)) {
            $response = $this->_responses[$endHandle];
            unset($this->_responses[$endHandle]);
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
    private function fulfillPromises(array $promises, array &$exceptions)
    {
        if (empty($promises)) {
            return [];
        }

        $results = [];
        Promise\each(
            $this->_promises,
            function (ResponseInterface $response, $index) use (&$results) {
                $results[$index] = $response;
            },
            function (RequestException $e, $index) use (&$exceptions) {
                $exceptions[$index] = $e;
            }
        )->wait();

        return $results;
    }
}
