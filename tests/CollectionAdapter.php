<?php

namespace TraderInteractive\Api;

final class CollectionAdapter implements Adapter
{
    private $request;

    public $results = [
        ['id' => '0', 'key' => 0],
        ['id' => '1', 'key' => 1],
        ['id' => '2', 'key' => 2],
        ['id' => '3', 'key' => 3],
        ['id' => '4', 'key' => 4],
    ];

    public function start(Request $request)
    {
        $this->request = $request;
    }

    public function end($handle)
    {
        if (substr_count($this->request->getUrl(), '/token') == 1) {
            return new Response(
                200,
                ['Content-Type' => ['application/json']],
                ['access_token' => 'foo', 'expires_in' => 1]
            );
        }

        if (substr_count($this->request->getUrl(), '/empty') == 1) {
            return new Response(
                200,
                ['Content-Type' => ['application/json']],
                ['pagination' => ['offset' => 0, 'total' => 0, 'limit' => 0], 'result' => []]
            );
        }

        if (substr_count($this->request->getUrl(), '/single') == 1) {
            return new Response(
                200,
                ['Content-Type' => ['application/json']],
                ['pagination' => ['offset' => 0, 'total' => 1, 'limit' => 1], 'result' => [['id' => '0', 'key' => 0]]]
            );
        }

        if (substr_count($this->request->getUrl(), '/basic') === 1) {
            $queryString = parse_url($this->request->getUrl(), PHP_URL_QUERY);
            $queryParams = [];
            parse_str($queryString, $queryParams);

            $offset = (int)$queryParams['offset'];
            $limit = (int)$queryParams['limit'];

            $result = [
                'pagination' => [
                    'offset' => $offset,
                    'total' => count($this->results),
                    'limit' => min($limit, count($this->results)),
                ],
                'result' => array_slice($this->results, $offset, $limit),
            ];

            return new Response(200, ['Content-Type' => ['application/json']], $result);
        }

        throw new \Exception('Unexpected request');
    }
}
