<?php

namespace TraderInteractive\Api;

final class ErrorResponseAdapter implements Adapter
{
    public function start(Request $request)
    {
        return uniqid();
    }

    public function end($handle)
    {
        return new Response(400, ['Content-Type' => ['application/json']], ['error_description' => 'an error']);
    }
}
