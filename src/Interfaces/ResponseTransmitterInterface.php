<?php
/**
 * Part of the Fusion.Http component package.
 *
 * @author Jason L. Walker
 * @license MIT
 */

namespace Fusion\Http\Interfaces;

/**
 * Interface to transmit a `ResponseInterface` instance.
 */
interface ResponseTransmitterInterface
{
    /**
     * Sends the `ResponseInterface` instance to the client.
     *
     * This method MUST transmit the entire response. This includes the headers
     * and the message body of the response.
     */
    public function send();
}