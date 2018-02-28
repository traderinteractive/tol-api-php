<?php

namespace TraderInteractive\Api;

final class NoTokenAdapter implements Adapter
{
    private $request;
    public function start(Request $request)
    {
        $this->request = $request;
        return uniqid();
    }

    public function end($handle)
    {
        if (substr_count($this->request->getUrl(), 'foos')) {
            return new Response(200, ['Content-Type' => ['application/json']], ['a body']);
        }

        throw new \Exception('Unexpected request');
    }
}
