<?php

namespace DominionEnterprises\Api;

/**
 * interface for api requests
 */
interface Adapter
{
    /**
     * Start a request
     *
     * @param Request $request
     *
     * @return mixed opaque handle to give to end()
     */
    public function start(Request $request);

    /**
     * End a request
     *
     * @param mixed $handle opaque handle from start()
     *
     * @return Response
     */
    public function end($handle);
}
