<?php
namespace DominionEnterprises\Api;

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
    function getTokens();

    /**
     * Search the API resource using the specified $filters
     *
     * @param string $resource
     * @param array $filters
     *
     * @return mixed opaque handle to be given to endIndex()
     */
    function startIndex($resource, array $filters = []);

    /**
     * @see startIndex()
     */
    function index($resource, array $filters = []);

    /**
     * Get the details of an API resource based on $id
     *
     * @param string $resource
     * @param string $id
     * @param array  $parameters
     *
     * @return mixed opaque handle to be given to endGet()
     */
    function startGet($resource, $id, array $parameters = []);

    /**
     * @see startGet()
     */
    function get($resource, $id, array $parameters = []);

    /**
     * Create a new instance of an API resource using the provided $data
     *
     * @param string $resource
     * @param array $data
     *
     * @return mixed opaque handle to be given to endPost()
     */
    function startPost($resource, array $data);

    /**
     * @see startPost()
     */
    function post($resource, array $data);

    /**
     * Update an existing instance of an API resource specified by $id with the provided $data
     *
     * @param string $resource
     * @param string $id
     * @param array $data
     *
     * @return mixed opaque handle to be given to endPut()
     */
    function startPut($resource, $id, array $data);

    /**
     * @see startPut()
     */
    function put($resource, $id, array $data);

    /**
     * Delete an existing instance of an API resource specified by $id
     *
     * @param string $resource
     * @param string $id
     * @param array $data
     *
     * @return mixed opaque handle to be given to endDelete()
     */
    function startDelete($resource, $id = null, array $data = null);

    /**
     * @see startDelete()
     */
    function delete($resource, $id = null, array $data = null);

    /**
     * Get response of start*() method
     *
     * @param mixed $handle opaque handle from start*()
     *
     * @return Response
     */
    function end($handle);

    /**
     * Set the default headers
     *
     * @param array The default headers
     *
     * @return void
     */
    function setDefaultHeaders($defaultHeaders);
}
