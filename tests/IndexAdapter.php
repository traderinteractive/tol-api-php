<?php

namespace TraderInteractive\Api;

final class IndexAdapter implements Adapter
{
    private $request;

    public function start(Request $request)
    {
        $this->request = $request;
        return uniqid();
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
                && urldecode($this->request->getUrl()) === 'baseUrl/v1/resource name?the name=the value') {
            return new Response(200, ['Content-Type' => ['application/json']], ['key' => 'value']);
        }

        throw new \Exception('Unexpected request');
    }
}
