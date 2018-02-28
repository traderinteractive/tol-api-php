<?php

namespace TraderInteractive\Api;

final class CheckUrlAdapter implements Adapter
{
    private $request;

    public function start(Request $request)
    {
        $this->request = $request;
        return uniqid();
    }

    public function end($handle)
    {
        return new Response(
            200,
            ['Content-Type' => ['application/json']],
            ['access_token' => 'foo', 'url' => $this->request->getUrl(), 'expires_in' => 1]
        );
    }
}
