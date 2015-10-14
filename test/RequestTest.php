<?php
/**
 * Part of the Fusion.Http component package.
 *
 * @author Jason L. Walker
 * @license MIT
 */

namespace Fusion\Http\Test;

use Fusion\Http\Request;
use Fusion\Http\Uri;

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

    public function testGetRequestTargetWithQuery()
    {
        $request = $this->request->withUri(new Uri('http://www.barfoo.net/request?target'));
        $this->assertEquals('/request?target', $request->getRequestTarget());
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
    public function testSettingBadMethod()
    {
        $this->request->withMethod('FOOBAR');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @dataProvider getBadUriInterfaceOrHttpMethod
     */
    public function testSettingInvalidMethod($badMethod)
    {
        $this->request->withMethod($badMethod);
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

    public function testSettingUriPreserveHostTrueWithExistingHost()
    {
        $request = $this->request->withUri($this->makeFooBarMockUri(), true);
        $this->assertEquals('www.example.com', $request->getHeader('host')[0]);
        $this->assertNotSame($request, $this->request);
    }

    public function testSettingUriPreserveHostWithNoExisingHost()
    {
        $request = $this->request->withoutHeader('host');
        $request = $request->withUri($this->makeFooBarMockUri(), true);
        $this->assertEquals('www.foobar.net', $request->getHeader('host')[0]);
        $this->assertNotSame($request, $this->request);
    }

    public function testSettingUriAndPreserveHostWithNewRequestTargetWithQuery()
    {
        $request = $this->request->withUri(new Uri('http://www.barfoo.org/new/target/with?queryAttached'), true);
        $this->assertEquals('/new/target/with?queryAttached', $request->getRequestTarget());
        $this->assertNotSame($request, $this->request);
    }

    public function testSettingUriAndNotPreserveHost()
    {
        $request = $this->request->withUri($this->makeFooBarMockUri());
        $this->assertEquals('www.foobar.net', $request->getHeader('host')[0]);
        $this->assertNotSame($request, $this->request);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @dataProvider getBadUriInterfaceOrHttpMethod
     */
    public function testSettingBadUriNotUriInterface($badUri)
    {
        $this->request = $this->request = new Request(
            'GET',
            $badUri,
            ['X-Test-Header' => 'foobar', 'Content-Type' => 'text/plain'],
            $this->getMock('Psr\Http\Message\StreamInterface')
        );
    }

    public function getBadUriInterfaceOrHttpMethod()
    {
        return [
            [1092],
            [3.14],
            [fopen('php://memory', 'r')],
            [null],
            [false],
            [new \stdClass()]
        ];
    }

    private function makeFooBarMockUri()
    {
        $mock = $this->getMock('Psr\Http\Message\UriInterface');
        $mock->expects($this->any())
             ->method('getHost')
             ->will($this->returnValue('www.foobar.net'));
        return $mock;
    }


}