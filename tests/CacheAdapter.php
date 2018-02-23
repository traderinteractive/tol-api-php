<?php

namespace TraderInteractive\Api;

final class CacheAdapter implements Adapter
{
    private $request;

    public function start(Request $request)
    {
        $this->request = $request;
    }

    public function end($handle)
    {
        if (substr_count($this->request->getUrl(), 'token') == 1) {
            return new Response(
                200,
                ['Content-Type' => ['application/json']],
                ['access_token' => 'token', 'expires_in' => 1]
            );
        }

        if (substr_count($this->request->getUrl(), 'a+url') == 1) {
            return new Response(200, ['header' => ['value']], ['doesnt' => 'matter']);
        }

        throw new \Exception();
    }
}
