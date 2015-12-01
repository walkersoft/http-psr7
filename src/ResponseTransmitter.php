<?php
/**
 * Created by PhpStorm.
 * User: Jason Walker
 * Date: 11/30/2015
 * Time: 6:10 PM
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
     */
    private function transmitHeaders()
    {
        header(
            sprintf(
                "HTTP/%s %d %s \r\n",
                $this->response->getProtocolVersion(),
                $this->response->getStatusCode(),
                $this->response->getReasonPhrase()
            )
        );

        foreach ($this->response->getHeaders() as $header => $value)
        {
            header(sprintf("%s: %s \r\n", $header, $value));
        }

        header("\r\n");
    }

    /**
     * Transmit the stream contents to the client.
     */
    private function transmitBody()
    {
        $stream = $this->response->getBody();
        $stream->rewind();

        while(!$stream->eof())
        {
            echo $stream->read(4096);
        }
    }
}