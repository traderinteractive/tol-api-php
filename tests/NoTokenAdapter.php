<?php

namespace TraderInteractive\Api;

final class NoTokenAdapter implements Adapter
{
    private $request;
    public function start(Request $request)
    {
        $this->request = $request;
    }

    public function end($handle)
    {
        if (substr($this->request->getUrl(), -5) === 'foos?') {
            return new Response(200, ['Content-Type' => ['application/json']], ['a body']);
        }

        throw new \Exception('Unexpected request');
    }
}
