<?php

namespace TraderInteractive\Api;

use DominionEnterprises\Util\Arrays;

final class ArrayCache implements Cache
{
    public $cache = [];

    public function set(Request $request, Response $response, $expires = null)
    {
        $this->cache[$request->getUrl()] = $response;
    }

    public function get(Request $request)
    {
        return Arrays::get($this->cache, $request->getUrl());
    }
}
