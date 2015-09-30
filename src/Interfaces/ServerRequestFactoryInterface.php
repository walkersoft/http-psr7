<?php
/**
 * Created by PhpStorm.
 * User: Jason Walker
 * Date: 9/29/2015
 * Time: 8:14 PM
 */

namespace Fusion\Http\Interfaces;

/**
 * Interface to specify that an implementing class can build a ServerRequest.
 */
interface ServerRequestFactoryInterface
{
    /**
     * Instantiates and configures a ServerRequest object.
     *
     * By default this factory will attempt to build the ServerRequest with
     * the information available to it via environment information or
     * otherwise.
     *
     * Optionally an associative array of parameters may be specified that will
     * override the default values so that the ServerRequest may be configured
     * by the client code.
     *
     * @param array $params An array of optional values that will override
     *     default values specified by the implementation.
     * @return \Fusion\Http\ServerRequest
     */
    public function buildServerRequest(array $params = []);
}