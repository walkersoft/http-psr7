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

class UploadedFileTest extends \PHPUnit_Framework_TestCase
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
        if (file_exists('CrashTestDummy.dat'))
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

    public function testMovingFileInCli()
    {
        $this->upload->moveTo('CrashTestDummy.dat');
        $this->assertEquals('FooBarBaz!', file_get_contents('CrashTestDummy.dat'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @dataProvider badErrorCodes
     */
    public function testCreatingUploadedFileWithBad($errorCode)
    {
        new UploadedFile($this->stream, $this->stream->getSize(), $errorCode);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testGettingStreamAfterMoving()
    {
        $this->upload->moveTo('CrashTestDummy.dat');
        $this->upload->getStream();
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testTryingToMoveASecondTime()
    {
        $this->upload->moveTo('CrashTestDummy.dat');
        $this->upload->moveTo('CrashTestDummy.dat');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @dataProvider badPathValues
     */
    public function testTryingToMoveStreamToAnInvalid($path)
    {
        $this->upload->moveTo($path);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testTryingToWriteToFileWithInvalidName()
    {
        @$this->upload->moveTo('#@fo`b*r.t?t');
    }

    /**
     * This test is mainly for code coverage purposes.
     *
     * Since move_uploaded_file() checks to make sure that it is moving a file
     * from a POST request, the moveTo() method fails anyway, even with a the
     * correct values.
     *
     */
    public function testMockMovingFileNotInCliMode()
    {
        $mock = $this->getMockBuilder('\Fusion\Http\UploadedFile')
            ->setMethods(['isCli'])
            ->disableOriginalConstructor()
            ->getMock();
        $mock->expects($this->any())
            ->method('isCli')
            ->will($this->returnValue(false));
        $mock->moveTo('CrashTestDummy.dat');
    }


    public function badErrorCodes()
    {
        return [
            [-1],
            [9],
            [20.11029],
            ['foo'],
            [null],
            [false],
            [[1]],
            [new \stdClass()],
            [fopen('php://memory', 'w')]
        ];
    }

    public function badPathValues()
    {
        return [
            [-1],
            [9],
            [20.11029],
            [''],
            [null],
            [false],
            [[1]],
            [new \stdClass()],
            [fopen('php://memory', 'w')]
        ];
    }
}