<?php
/**
 * Created by PhpStorm.
 * User: Jason Walker
 * Date: 11/30/2015
 * Time: 6:57 PM
 */

namespace Fusion\Http\Test;

use Fusion\Http\Response;
use Fusion\Http\ResponseTransmitter;
use Fusion\Http\Stream;

require '../vendor/autoload.php';

class ResponseTransmitterTest extends \PHPUnit_Framework_TestCase
{
    /** @var ResponseTransmitter */
    private $transmitter;

    public function setUp()
    {
    }

    public function tearDown()
    {
        unset($this->transmitter);
    }

    /**
     * @runInSeparateProcess
     */
    public function testEmittingVanillaResponse()
    {
        ob_start();
        $stream = new Stream(fopen('php://temp', 'w+'));
        $stream->write('foobar');
        $response = new Response();
        $response = $response->withHeader('X-Custom-Header', 'wish-status-line-was-shown');
        $response = $response->withBody($stream);
        $willBe = "X-Custom-Header: wish-status-line-was-shown\r\n\r\nfoobar";
        $this->transmitter = new ResponseTransmitter($response);
        $this->transmitter->send();
        $this->assertEquals($willBe, $this->getHeadersList() . ob_get_clean());
    }

    private function getHeadersList()
    {
        //bummer having to depend on xdebug, but worth it in this case
        $headers = xdebug_get_headers();
        $headers = implode("\r\n", $headers);
        $headers .= "\r\n";
        return $headers;
    }
}