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
    private $_url;

    /**
     * The HTTP method for this request
     *
     * @var string
     */
    private $_method;

    /**
     * The body for this request
     *
     * @var string
     */
    private $_body;

    /**
     * The HTTP headers for this request
     *
     * @var array
     */
    private $_headers;

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

        $this->_url = $url;
        $this->_method = $method;
        $this->_body = $body;
        $this->_headers = $headers;
    }

    /**
     * Get the url of this request
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->_url;
    }

    /**
     * Get the method of this request
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->_method;
    }

    /**
     * Get the body of this request
     *
     * @return string
     */
    public function getBody()
    {
        return $this->_body;
    }

    /**
     * Get the headers of this request
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->_headers;
    }
}
