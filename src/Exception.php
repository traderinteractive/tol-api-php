<?php

namespace TraderInteractive\Api;

class Exception extends \Exception
{
    /**
     * @var Response
     */
    private $response;

    public function __construct(string $message, Response $response)
    {
        parent::__construct($message);
        $this->response = $response;
    }

    public function getResponse() : Response
    {
        return $this->response;
    }
}
