<?php
/**
 * Unit test for \Fusion\Http\Request class.
 */

namespace Fusion\Http\Test;

use Fusion\Http\Request;

require '../vendor/autoload.php';

class RequestTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Fusion\Http\Request
     */
    private $request;

    public function setUp()
    {
        $this->request = new Request(
            'GET',
            'http://www.example.com',
            ['X-Test-Header' => 'foobar', 'Content-Type' => 'text/plain'],
            $this->getMock('Psr\Http\Message\StreamInterface')
        );
    }

    public function tearDown()
    {
        $this->request = null;
    }

    public function testGetAbsoluteRequestTargetWithNoEntry()
    {
        $this->assertEquals('/', $this->request->getRequestTarget());
    }

    public function testGetAbsoluteRequestTargetSetToSlash()
    {
        $this->request = $this->request->withRequestTarget('/');
        $this->assertEquals('/', $this->request->getRequestTarget());
    }

    public function testGetRequestTarget()
    {
        $request = $this->request->withRequestTarget('/foo/bar');
        $this->assertEquals('/foo/bar', $request->getRequestTarget());
        $this->assertNotSame($request, $this->request);
    }

    public function testGettingHttpMethod()
    {
        $this->assertEquals('GET', $this->request->getMethod());
    }

    public function testChangingHttpMethod()
    {
        $request = $this->request->withMethod('post');
        $this->assertEquals('post', $request->getMethod());
        $this->assertNotSame($request, $this->request);
        $request = $this->request->withMethod('OpTiOnS');
        $this->assertEquals('OpTiOnS', $request->getMethod());
        $this->assertNotSame($request, $this->request);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSendingBadMethod()
    {
        $this->request->withMethod('FOOBAR');
    }

    public function testBringYourOwnUriInterface()
    {
        $this->request = new Request(
            'HEAD',
            $this->getMock('Psr\Http\Message\UriInterface'),
            ['X-Header-Foo' => 'foo'],
            $this->getMock('Psr\Http\Message\StreamInterface')
        );
        $this->assertInstanceOf('Psr\Http\Message\UriInterface', $this->request->getUri());
    }

    public function testSettingUriPreserveHostTrue()
    {
        $mock = $this->makeFooBarMockUri();
        $request = $this->request->withUri($mock, true);
        $this->assertInternalType('array', $request->getHeader('host'));
        $this->assertEquals('www.example.com', $request->getHeader('host')[0]);
    }

    /*public function testSettingUriPreserveHostTrueAndHadHostAlready()
    {
        $mock = $this->makeFooBarMockUri();
        $request = $request->withUri($mock, true);
        $this->assertEquals('www.barfoo.com', $request->getHeader('host')[0]);
    }*/

    private function makeFooBarMockUri()
    {
        $mock = $this->getMock('Psr\Http\Message\UriInterface');
        $mock->expects($this->any())
             ->method('getHost')
             ->will($this->returnValue('www.foobar.net'));
        return $mock;
    }


}