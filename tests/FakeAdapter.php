<?php

namespace TraderInteractive\Api;

final class FakeAdapter implements Adapter
{
    public function start(Request $request)
    {
    }

    public function end($handle)
    {
        return new Response(
            200,
            ['Content-Type' => ['application/json']],
            ['access_token' => 'foo', 'expires_in' => 1]
        );
    }
}
