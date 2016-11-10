<?php

namespace DominionEnterprises\Api;
use DominionEnterprises\Util;

/**
 * Represents a response to an API request
 */
interface ResponseInterface
{

    /**
     * Returns the HTTP status code of the response
     *
     * @return int
     */
    function getHttpCode();

    /**
     * Returns an array representing the response from the API
     *
     * @return array
     */
    function getResponse();

    /**
     * Returns the parsed response headers from the API
     *
     * @return array array where each header key has an array of values
     */
    function getResponseHeaders();
}

