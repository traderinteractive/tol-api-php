<?php

namespace TraderInteractive\Api;

final class RefreshTokenAdapter implements Adapter
{
    private $request;

    public function start(Request $request)
    {
        $this->request = $request;
    }

    public function end($handle)
    {
        if (substr_count($this->request->getUrl(), 'token') === 1
                && substr_count($this->request->getBody(), 'grant_type=client_credentials') === 1) {
            $response = new Response(
                200,
                ['Content-Type' => ['application/json']],
                ['access_token' => 'badToken', 'refresh_token' => 'boo', 'expires_in' => 1]
            );
            return $response;
        }

        if (substr_count($this->request->getUrl(), 'token') === 1
                && substr_count($this->request->getBody(), 'refresh_token=boo') === 1) {
            $response = new Response(
                200,
                ['Content-Type' => ['application/json']],
                ['access_token' => 'goodToken', 'expires_in' => 1]
            );
            return $response;
        }

        $headers = $this->request->getHeaders();
        if ($headers['Authorization'] === 'Bearer goodToken') {
            return new Response(200, ['Content-Type' => ['application/json']], []);
        }

        return new Response(401, ['Content-Type' => ['application/json']], ['error' => 'invalid_grant']);
    }
}
