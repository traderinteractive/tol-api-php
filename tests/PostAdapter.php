<?php

namespace TraderInteractive\Api;

final class PostAdapter implements Adapter
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

        if ($this->request->getMethod() === 'POST'
                && $this->request->getUrl() === 'baseUrl/v1/resource+name'
                && $this->request->getBody() === '{"the key":"the value"}'
                && $this->request->getHeaders() === [
                    'Content-Type' => 'application/json',
                    'Accept-Encoding' => 'gzip',
                    'Authorization' => 'Bearer a token',
                ]
        ) {
            return new Response(200, ['Content-Type' => ['application/json']], ['key' => 'value']);
        }

        throw new \Exception('Unexpected request');
    }
}
