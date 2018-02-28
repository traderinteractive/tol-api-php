<?php

namespace TraderInteractive\Api;

final class AccessTokenInvalidClientAdapter implements Adapter
{
    private $request;

    public function start(Request $request)
    {
        $this->request = $request;
        return uniqid();
    }

    public function end($handle)
    {
        if (substr_count($this->request->getUrl(), 'token') == 1) {
            return new Response(200, ['Content-Type' => ['application/json']], ['error' => 'invalid_client']);
        }
    }
}
