<?php

namespace TraderInteractive\Api;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * interface for api requests
 */
interface AdapterInterface
{
    /**
     * Start a request
     *
     * @param RequestInterface $request
     *
     * @return string opaque handle to give to end()
     */
    public function start(RequestInterface $request) : string;

    /**
     * End a request
     *
     * @param mixed $handle opaque handle from start()
     *
     * @return ResponseInterface
     */
    public function end(string $handle) : ResponseInterface;
}
