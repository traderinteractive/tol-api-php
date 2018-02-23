<?php

namespace TraderInteractive\Api;

final class GetWithParametersAdapter implements Adapter
{
    private $request;

    public function start(Request $request)
    {
        $this->request = $request;
    }

    public function end($handle)
    {
        if (substr($this->request->getUrl(), -5) === 'token') {
            return new Response(
                200,
                ['Content-Type' => ['application/json']],
                ['access_token' => 'a token', 'expires_in' => 1]
            );
        }

        if ($this->request->getMethod() === 'GET'
                && $this->request->getUrl() === 'baseUrl/v1/resource+name/the+id?foo=bar') {
            return new Response(200, ['Content-Type' => ['application/json']], ['key' => 'value']);
        }

        throw new \Exception('Unexpected request');
    }
}
