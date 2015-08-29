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
        $this->stream->seek(3);
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

    public function testSizeIsNull()
    {
        $this->stream->close();
        $this->assertNull($this->stream->getSize());
    }

    public function testIsSeekable()
    {
        $this->assertTrue($this->stream->isSeekable());
    }

    public function testIsNotSeekable()
    {
        $this->stream->detach();
        $this->assertFalse($this->stream->isSeekable());
    }

    public function testIsNotSeekableStream()
    {
        $this->stream = new Stream(fopen('php://output', 'r'));
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
        $this->stream->getContents();
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
        $this->assertEquals(10, $this->stream->tell());
        $this->stream->seek(5, SEEK_SET);
        $this->assertEquals(5, $this->stream->tell());
        $this->stream->seek(3, SEEK_CUR);
        $this->assertEquals(8, $this->stream->tell());
    }

    public function testDetachingStream()
    {
        $this->assertEquals('stream', get_resource_type($this->stream->detach()));
    }

    public function testStreamIsUnusable()
    {
        $this->assertEquals('stream', get_resource_type($this->stream->detach()));
        $this->assertNull($this->stream->detach());
    }

    public function testStreamIsntWritableNotStream()
    {
        $this->stream->detach();
        $this->assertFalse($this->stream->isWritable());
    }

    public function testStreamIsntReadableNotStream()
    {
        $this->stream->detach();
        $this->assertFalse($this->stream->isReadable());
    }

    public function testGettingMetadata()
    {
        $this->assertInternalType('array', $this->stream->getMetadata());
    }

    public function testGettingMetadataKey()
    {
        $this->assertTrue($this->stream->getMetadata('seekable'));
    }

    public function testNotGettingMetadataKey()
    {
        $this->assertNull($this->stream->getMetadata('foobar'));
    }

    public function testGettingExceptionMessageFromToString()
    {
        $this->stream->detach();
        $this->assertEquals(
            'Unable to rewind. The stream is not seekable.',
            $this->stream->__toString()
        );
    }

    //Test for various exceptions

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testStreamIsntResource()
    {
        $this->stream = new Stream('foobar');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testStreamIsntStreamResource()
    {
        $this->stream = new Stream(stream_context_create());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testStreamCantTell()
    {
        $this->stream->detach();
        $this->stream->tell();
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testStreamCantRewind()
    {
        $this->stream->detach();
        $this->stream->rewind();
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testStreamCantWriteNotResource()
    {
        $this->stream->detach();
        $this->stream->write('foobar');
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testStreamCantWriteNotWritable()
    {
        $this->stream = new Stream(fopen('php://memory', 'r'));
        $this->stream->write('foobar');
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testStreamCantReadNotResource()
    {
        $this->stream->detach();
        $this->stream->read(10);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testStreamCantReadNotReadable()
    {
        $this->stream = new Stream(fopen('php://output', 'r'));
        $this->stream->read(10);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testStreamCantSeekNotSeekableStream()
    {
        $this->stream = new Stream(fopen('php://output', 'r'));
        $this->stream->seek(10);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testStreamCantGetContentsNotResource()
    {
        $this->stream->detach();
        $this->stream->getContents();
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testStreamCantGetContentsNotReadable()
    {
        $this->stream = new Stream(fopen('php://output', 'r'));
        $this->stream->getContents();
    }
}