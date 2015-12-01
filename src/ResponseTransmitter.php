<?php
/**
 * Part of the Fusion.Http component package.
 *
 * @author Jason L. Walker
 * @license MIT
 */

namespace Fusion\Http;


use Fusion\Http\Interfaces\ResponseTransmitterInterface;
use Psr\Http\Message\ResponseInterface;

class ResponseTransmitter implements ResponseTransmitterInterface
{

    /**
     * @var \Psr\Http\Message\ResponseInterface
     */
    private $response = null;

    /**
     * Constructor.
     *
     * @param \Psr\Http\Message\ResponseInterface
     */
    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    /**
     * {@inheritdoc}
     */
    public function send()
    {
        $this->transmitHeaders();
        $this->transmitBody();
    }

    /**
     * Transmit all HTTP headers to the client.
     *
     * This method MUST NOT send a `CRLF` after the headers are sent.
     *
     * This method MUST ensure that all headers returned by the response are
     * properly converted to strings.
     *
     * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec4.html#sec4
     */
    private function transmitHeaders()
    {
        header(
            sprintf(
                "HTTP/%s %d %s\r\n",
                $this->response->getProtocolVersion(),
                $this->response->getStatusCode(),
                $this->response->getReasonPhrase()
            )
        );

        foreach ($this->response->getHeaders() as $header => $value)
        {
            header(sprintf("%s: %s\r\n", $header, implode(',', $value)), false);
        }

    }

    /**
     * Transmit the stream contents to the client.
     *
     * This method MUST send a `CRLF` before sending the body to the client.
     *
     * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec4.html#sec4
     */
    private function transmitBody()
    {
        $stream = $this->response->getBody();
        $stream->rewind();
        echo "\r\n";

        while(!$stream->eof())
        {
            echo $stream->read(4096);
        }
    }
}