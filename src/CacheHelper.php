<?php

namespace TraderInteractive\Api;

use Psr\Http\Message\RequestInterface;

abstract class CacheHelper
{
    /**
     * Returns a valid PSR-11 key.
     *
     * @param RequestInterface $request The request from which the key will be generated.
     *
     * @return string
     */
    public static function getCacheKey(RequestInterface $request) : string
    {
        $key = "{$request->getMethod()}|{$request->getUri()}|{$request->getBody()}";
        $map = [
            '{' => '_LBRACE_',
            '}'=> '_RBRACE_',
            '('=> '_LPAREN_',
            ')'=> '_RPAREN_',
            '/'=> '_FSLASH_',
            '\\'=> '_BSLASH_',
            '@'=> '_AT_',
            ':'=> '_COLON_',
        ];

        return str_replace(array_keys($map), $map, $key);
    }
}
