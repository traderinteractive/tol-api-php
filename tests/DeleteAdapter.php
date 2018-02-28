<?php

namespace TraderInteractive\Api;

final class DeleteAdapter implements Adapter
{
    public $request;

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

        if ($this->request->getMethod() === 'DELETE'
                && $this->request->getUrl() === 'baseUrl/v1/resource+name/the+id'
                && $this->request->getHeaders() === [
                    'Content-Type' => 'application/json',
                    'Accept-Encoding' => 'gzip',
                    'Authorization' => 'Bearer a token',
                ]
        ) {
            $body = $this->request->getBody();

            if ($body === null || $body === '{"the key":"the value"}') {
                return new Response(204, ['Content-Type' => ['application/json']]);
            }
        }

        throw new \Exception('Unexpected request');
    }
}
