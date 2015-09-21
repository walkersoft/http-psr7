<?php
/**
 * Created by PhpStorm.
 * User: jwalker
 * Date: 9/21/2015
 * Time: 3:53 PM
 */

namespace Fusion\Http\Test;

use Fusion\Http\Stream;
use Fusion\Http\UploadedFile;

require '../vendor/autoload.php';

class UploadedFileTest
{

    /**
     * @var \Fusion\Http\UploadedFile
     */
    private $upload;

    /**
     * @var \Fusion\Http\Stream
     */
    private $stream;

    public function setUp()
    {
        $this->stream = new Stream(fopen('php://memory', 'wb+'));
        $this->stream->write('FooBarBaz!');
        $this->upload = new UploadedFile($this->stream, $this->stream->getSize(), UPLOAD_ERR_OK, 'foo.txt', 'text/plain');
    }

    public function tearDown()
    {
        $this->upload = null;
        if(file_exists('CrashTestDummy.dat'))
        {
            unlink('CrashTestDummy.dat');
        }
    }

    public function testGettingStream()
    {
        $this->assertSame($this->stream, $this->upload->getStream());
    }

    public function testGettingBytes()
    {
        $this->assertEquals(10, $this->upload->getSize());
    }

    public function testGettingError()
    {
        $this->assertEquals(0, $this->upload->getError());
    }

    public function testGettingClientFilename()
    {
        $this->assertEquals('foo.txt', $this->upload->getClientFilename());
    }

    public function testGettingClientMediaType()
    {
        $this->assertEquals('text/plain', $this->upload->getClientMediaType());
    }
}