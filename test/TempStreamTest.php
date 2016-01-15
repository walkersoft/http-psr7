<?php
/**
 * Created by PhpStorm.
 * User: jwalker
 * Date: 1/15/2016
 * Time: 8:52 AM
 */

namespace Fusion\Http\Test;

use Fusion\Http\TempStream;

require '../vendor/autoload.php';

class TempStreamTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Fusion\Http\TempStream
     */
    private $stream;

    public function setUp()
    {
        $this->stream = new TempStream();
    }

    public function tearDown()
    {
        $this->stream->close();
        $this->stream = null;
    }

    public function testIsTempStream()
    {
        $this->assertInstanceOf('\Fusion\Http\TempStream', $this->stream);
    }
}