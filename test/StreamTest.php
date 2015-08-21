<?php
/**
 * Created by PhpStorm.
 * User: jwalker
 * Date: 8/21/2015
 * Time: 1:20 PM
 */


namespace Fusion\Http\Test;

use Fusion\Http\Stream;

require '../vendor/autoload.php';

class StreamTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Fusion\Http\Stream
     */
    private $stream;

    public function setUp()
    {
        $this->stream = new Stream(fopen('php://memory', 'w+'));
    }

    public function tearDown()
    {
        $this->stream->close();
        $this->stream = null;
    }

    public function testPrintingStream()
    {
        $this->assertEquals(6, $this->stream->write('foobar'));
        $this->assertEquals(
            'echoing: foobar',
            sprintf('echoing: %s', $this->stream)
        );
    }
}