<?php

namespace DominionEnterprises\Api;
use DominionEnterprises\Util;
use Guzzle\Http\Client as GuzzleClient;
use Guzzle\Http\Exception\BadResponseException;
use Guzzle\Http\Exception\MultiTransferException;

/**
 * Concrete implentation of Adapter interface
 */
final class GuzzleAdapter implements Adapter
{
    /**
     * @var array array of \Guzzle\Http\Message\RequestInterface with keys matching what was given from start()
     */
    private $_requests = [];

    /**
     * @var array array of \Guzzle\Http\Message\Response with keys matching what was given from start()
     */
    private $_responses = [];

    /**
     * @var array array of \Exception with keys matching what was given from start()
     */
    private $_exceptions = [];

    /**
     * @var \Guzzle\Http\Client
     */
    private $_client;

    public function __construct()
    {
        $this->_client = new GuzzleClient();
    }

    /**
     * @see Adapter::start()
     */
    public function start(Request $request)
    {
        $this->_requests[] = $this->_client->createRequest(
            $request->getMethod(),
            $request->getUrl(),
            $request->getHeaders(),
            $request->getBody(),
            //stop guzzle from following redirects
            //only for 400/500 error codes, actual exceptions can still happen
            ['allow_redirects' => false, 'exceptions' => false]
        );

        end($this->_requests);
        return key($this->_requests);
    }

    /**
     * @see Adapter::end()
     *
     * @throws \InvalidArgumentException
     */
    public function end($endHandle)
    {
        Util::throwIfNotType(['int' => [$endHandle]]);

        if (!empty($this->_requests)) {
            $multiException = null;
            try {
                $this->_client->send($this->_requests);
            } catch (MultiTransferException $e) {
                $multiException = $e;
            }

            foreach ($this->_requests as $handle => $request) {
                $response = $request->getResponse();
                if ($response === null) {
                    $this->_exceptions[$handle] = $multiException->getExceptionForFailedRequest($request);
                } else {
                    try {
                        $response->json();
                        $this->_responses[$handle] = $response;
                    } catch (\Exception $e) {
                        $this->_exceptions[$handle] = BadResponseException::factory($request, $response);
                    }
                }
            }

            $this->_requests = [];
        }

        if (array_key_exists($endHandle, $this->_exceptions)) {
            $exception = $this->_exceptions[$endHandle];
            unset($this->_exceptions[$endHandle]);
            throw $exception;
        }

        if (array_key_exists($endHandle, $this->_responses)) {
            $response = $this->_responses[$endHandle];
            unset($this->_responses[$endHandle]);
            return new Response($response->getStatusCode(), $response->getHeaders()->toArray(), $response->json());
        }

        throw new \InvalidArgumentException('$endHandle not found');
    }
}
