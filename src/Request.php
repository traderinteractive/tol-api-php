<?php

namespace TraderInteractive\Api;

use DominionEnterprises\Util;

/**
 * Concrete implementation of Request
 */
final class Request
{
    /**
     * The url for this request
     *
     * @var string
     */
    private $url;

    /**
     * The HTTP method for this request
     *
     * @var string
     */
    private $method;

    /**
     * The body for this request
     *
     * @var string
     */
    private $body;

    /**
     * The HTTP headers for this request
     *
     * @var array
     */
    private $headers;

    /**
     * Create a new request instance
     *
     * @param string $url
     * @param string $method
     * @param string|null $body
     * @param array $headers
     *
     * @throws \InvalidArgumentException Thrown if $url is not a non-empty string
     * @throws \InvalidArgumentException Thrown if $method is not a non-empty string
     * @throws \InvalidArgumentException Thrown if $body is not null or not a non-empty string
     */
    public function __construct($url, $method, $body = null, array $headers = [])
    {
        Util::throwIfNotType(['string' => [$url, $method]], true);
        Util::throwIfNotType(['string' => [$body]], true, true);

        $this->url = $url;
        $this->method = $method;
        $this->body = $body;
        $this->headers = $headers;
    }

    /**
     * Get the url of this request
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Get the method of this request
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Get the body of this request
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Get the headers of this request
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }
}
