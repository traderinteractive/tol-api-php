<?php

namespace TraderInteractive\Api;
use DominionEnterprises\Util;
use DominionEnterprises\Util\Arrays;

/**
 * Class to cache API results in a Redis store.
 */
final class PredisCache implements Cache
{
    /**
     * Predis client for storing cache.
     *
     * @var \Predis\Client
     */
    private $_client;

    /**
     * Construct a cache instance.
     *
     * @param \Predis\Client $client The predis client to send data to.
     */
    public function __construct(\Predis\Client $client)
    {
        $this->_client = $client;
    }

    /**
     * @see Cache::set()
     */
    public function set(Request $request, Response $response, $expires = null)
    {
        if ($expires === null) {
            $expiresHeader = null;
            if (!Arrays::tryGet($response->getResponseHeaders(), 'Expires', $expiresHeader)) {
                return;
            }

            $expires = Util::ensureNot(false, strtotime($expiresHeader[0]), "Unable to parse Expires value of '{$expiresHeader[0]}'");
        }

        $key = self::_getKey($request);
        $this->_client->set(
            $key,
            json_encode(
                [
                    'httpCode' => $response->getHttpCode(),
                    'headers' => $response->getResponseHeaders(),
                    'body' => $response->getResponse(),
                ]
            )
        );
        $this->_client->expireat($key, $expires);
    }

    /**
     * @see Cache::get()
     */
    public function get(Request $request)
    {
        $cached = $this->_client->get(self::_getKey($request));
        if ($cached !== null) {
            $data = json_decode($cached, true);
            return new Response($data['httpCode'], $data['headers'], $data['body']);
        }

        return null;
    }

    /**
     * Helper method to get a unique key for an API request.
     *
     * This generator does not use the request headers so there is a chance for conflicts
     *
     * @param Request $request The request from which to generate an unique key
     *
     * @return string the unique identifier
     */
    private static function _getKey(Request $request)
    {
        return "{$request->getUrl()}:{$request->getBody()}";
    }
}
