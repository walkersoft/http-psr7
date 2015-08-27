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

    public function testGetStreamContents()
    {
        $this->assertEquals(6, $this->stream->write('foobar'));
        $this->stream->rewind();
        $this->assertEquals(
            'echoing: foobar',
            sprintf('echoing: %s', $this->stream->getContents())
        );
    }

    public function testGetStreamContentsFromAbritraryPosition()
    {
        $this->assertEquals(6, $this->stream->write('foobar'));
        $this->stream->seek(-3);
        $this->assertEquals(
            'echoing: bar',
            sprintf('echoing: %s', $this->stream->getContents())
        );
    }

    public function testWritingToStream()
    {
        $this->assertEquals(9, $this->stream->write('foobarbaz'));
    }

    public function testTellWhereStreamIs()
    {
        $this->assertEquals(9, $this->stream->write('foobarbaz'));
        $this->assertEquals(9, $this->stream->tell());
    }

    public function testRewindStream()
    {
        $this->assertEquals(9, $this->stream->write('foobarbaz'));
        $this->stream->rewind();
        $this->assertEquals(0, $this->stream->tell());
    }

    public function testGetStreamSize()
    {
        $this->assertEquals(0, $this->stream->getSize());
        $this->stream->write('foo');
        $this->assertEquals(3, $this->stream->getSize());
        $this->stream->write("\n");
        $this->assertEquals(4, $this->stream->getSize());
    }

    public function testIsSeekable()
    {
        $this->assertTrue($this->stream->isSeekable());
    }

    public function testIsNotSeekable()
    {
        $this->stream = new Stream(fopen('php://input', 'r'));
        $this->assertFalse($this->stream->isSeekable());
    }

    public function testStreamIsWritable()
    {
        $this->assertTrue($this->stream->isWritable());
    }

    public function testStreamIsNotWritable()
    {
        $this->stream = new Stream(fopen('php://input', 'r'));
        $this->assertFalse($this->stream->isWritable());
    }

    public function testStreamIsReadable()
    {
        $this->assertTrue($this->stream->isReadable());
    }

    public function testStreamIsNotReadable()
    {
        $this->stream = new Stream(fopen('php://output', 'w'));
        $this->assertFalse($this->stream->isReadable());
    }

    public function testGetMetadata()
    {
        $this->assertInternalType('array', $this->stream->getMetadata());
    }

    public function testGetMetadataKey()
    {
        $this->assertEquals('php://memory', $this->stream->getMetadata('uri'));
    }

    public function testStreamIsAtEof()
    {
        $this->stream->write('foo');
        $this->assertTrue($this->stream->eof());
    }

    public function testStreamIsNotAtEof()
    {
        $this->stream->write('foo');
        $this->stream->rewind();
        $this->assertFalse($this->stream->eof());
    }

    public function testSeekingLocations()
    {
        $this->stream->write("foobarbaz\n");
        $this->stream->seek(0, SEEK_END);
    }
}