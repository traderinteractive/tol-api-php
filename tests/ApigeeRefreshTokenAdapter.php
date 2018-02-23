<?php

namespace TraderInteractive\Api;

final class ApigeeRefreshTokenAdapter implements Adapter
{
    private $request;
    private $count = 0;

    public function start(Request $request)
    {
        $this->request = $request;
    }

    public function end($handle)
    {
        if (substr_count($this->request->getUrl(), 'token') == 1) {
            $response = new Response(
                200,
                ['Content-Type' => ['application/json']],
                ['access_token' => $this->count, 'expires_in' => 1]
            );
            ++$this->count;
            return $response;
        }

        $headers = $this->request->getHeaders();
        if ($headers['Authorization'] === 'Bearer 1') {
            return new Response(200, ['Content-Type' => ['application/json']], []);
        }

        return new Response(
            401,
            ['Content-Type' => ['application/json']],
            ['fault' => ['faultstring' => 'InvAlid accEss tOkEn']]
        );
    }
}
