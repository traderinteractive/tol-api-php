<?php
namespace TraderInteractive\Api;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Client for apis
 */
interface ClientInterface
{
    /**
     * Get access token and refresh token
     *
     * @return array two string values, access token and refresh token
     */
    public function getTokens() : array;

    /**
     * Search the API resource using the specified $filters
     *
     * @param string $resource
     * @param array $filters
     *
     * @return string opaque handle to be given to endIndex()
     */
    public function startIndex(string $resource, array $filters = []) : string;

    /**
     * @see startIndex()
     */
    public function index(string $resource, array $filters = []) : ResponseInterface;

    /**
     * Get the details of an API resource based on $id
     *
     * @param string $resource
     * @param string $id
     * @param array  $parameters
     *
     * @return string opaque handle to be given to endGet()
     */
    public function startGet(string $resource, string $id, array $parameters = []) : string;

    /**
     * @see startGet()
     */
    public function get(string $resource, string $id, array $parameters = []) : ResponseInterface;

    /**
     * Create a new instance of an API resource using the provided $data
     *
     * @param string $resource
     * @param array $data
     *
     * @return string opaque handle to be given to endPost()
     */
    public function startPost(string $resource, array $data) : string;

    /**
     * @see startPost()
     */
    public function post(string $resource, array $data) : ResponseInterface;

    /**
     * Update an existing instance of an API resource specified by $id with the provided $data
     *
     * @param string $resource
     * @param string $id
     * @param array $data
     *
     * @return string opaque handle to be given to endPut()
     */
    public function startPut(string $resource, string $id, array $data) : string;

    /**
     * @see startPut()
     */
    public function put(string $resource, string $id, array $data) : ResponseInterface;

    /**
     * Delete an existing instance of an API resource specified by $id
     *
     * @param string $resource
     * @param string $id
     * @param array $data
     *
     * @return string opaque handle to be given to endDelete()
     */
    public function startDelete(string $resource, string $id = null, array $data = null) : string;

    /**
     * @see startDelete()
     */
    public function delete(string $resource, string $id = null, array $data = null) : ResponseInterface;

    /**
     * Get response of start*() method
     *
     * @param string $handle opaque handle from start*()
     *
     * @return ResponseInterface
     */
    public function end(string $handle) : ResponseInterface;

    /**
     * Set the default headers
     *
     * @param array The default headers
     *
     * @return void
     */
    public function setDefaultHeaders(array $defaultHeaders);
}
