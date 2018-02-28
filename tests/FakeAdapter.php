<?php

namespace TraderInteractive\Api;

use DominionEnterprises\Util\Arrays;

final class FakeAdapter implements Adapter
{
    /**
     * @var Request
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

    public function start(Request $request)
    {
        $handle = uniqid();
        $this->requests[] = ['request' => $request, 'handle' => $handle];
        return $handle;
    }

    public function end($handle)
    {
        $request = Arrays::where($this->requests, ['handle' => $handle])[0]['request'];

        $response = ($this->handler)($request);

        if ($response !== null) {
            return $response;
        }

        throw new \Exception("Unhandled request for '{$request->getUrl()}");
    }

    public function getLastRequest() : Request
    {
        return $this->requests[count($this->requests) - 1]['request'];
    }
}
