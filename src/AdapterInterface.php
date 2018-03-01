<?php

namespace TraderInteractive\Api;

use Psr\Http\Message\ResponseInterface;

/**
 * interface for api requests
 */
interface AdapterInterface
{
    /**
     * Start a request
     *
     * @param Request $request
     *
     * @return string opaque handle to give to end()
     */
    public function start(Request $request) : string;

    /**
     * End a request
     *
     * @param mixed $handle opaque handle from start()
     *
     * @return ResponseInterface
     */
    public function end(string $handle) : ResponseInterface;
}
