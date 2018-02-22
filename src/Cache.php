<?php

namespace TraderInteractive\Api;

/**
 * Interface for caching API responses
 */
interface Cache
{
    /**
     * Store the api $response as the cached result of the api $request
     *
     * @param Request $request the request for which the response will be cached
     * @param Response $response the reponse to cache
     * @param int $expires Timestamp at which the cache should expire. If null the cache will attempt to extract the timestamp from the
     *                     response headers
     *
     * @return void
     */
    public function set(Request $request, Response $response, $expires = null);

    /**
     * Retrieve the cached results of the api $request
     *
     * @param Request $request a request for which the response may be cached
     *
     * @return Response|null
     */
    public function get(Request $request);
}
