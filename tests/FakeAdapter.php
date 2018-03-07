<?php

namespace TraderInteractive\Api;

use TraderInteractive\Util\Arrays;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class FakeAdapter implements AdapterInterface
{
    /**
     * @var RequestInterface[]
     */
    private $requests = [];

    /**
     * @var callable
     */
    private $handler;

    public function __construct(callable $handler)
    {
        $this->handler = $handler;
    }

    public function start(RequestInterface $request) : string
    {
        $handle = uniqid();
        $this->requests[] = ['request' => $request, 'handle' => $handle];
        return $handle;
    }

    public function end(string $handle) : ResponseInterface
    {
        $request = Arrays::where($this->requests, ['handle' => $handle])[0]['request'];

        $response = ($this->handler)($request);

        if ($response !== null) {
            return $response;
        }

        throw new \Exception("Unhandled request for '{$request->getUri()}");
    }

    public function getLastRequest() : RequestInterface
    {
        return $this->requests[count($this->requests) - 1]['request'];
    }
}
