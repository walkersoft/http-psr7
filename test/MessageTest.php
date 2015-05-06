<?php
/**
 * Created by PhpStorm.
 * User: jwalker
 * Date: 5/6/2015
 * Time: 9:47 AM
 */

namespace Fusion\Http\Test;

use Fusion\Http\Message;

class MessageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Fusion\Http\Message
     */
    protected $http;

    public function setUp()
    {
        $this->http = new Message();
    }

    public function tearDown()
    {
        $this->http = null;
    }

    public function testGettingProtocolVersion()
    {
        $this->assertEquals('1.1', $this->http->getProtocolVersion());
    }

    public function testChangeProtocolVersion()
    {
        $http = $this->http->withProtocolVersion('1.0');
        $this->assertInternalType('string', $http->getProtocolVersion());
        $this->assertEquals('1.0', $http->getProtocolVersion());
        $this->assertNotSame($this->http, $http);
    }

    public function testAddingHeader()
    {
        $http = $this->http->withHeader('foo', 'bar');
        $this->assertTrue($http->hasHeader('foo'));
        $this->assertFalse($this->http->hasHeader('foo'));
        $this->assertNotSame($this->http, $http);
    }

    public function testAddingHeaderWithMultipleValues()
    {
        $http = $this->http->withHeader('foo', ['bar', 'baz', 'bim']);
        $this->assertTrue($http->hasHeader('FOO'));
        $this->assertEquals(3, count($http->getHeader('foo')));
        $this->assertEquals();
    }

    public function testGettingHeader()
    {
        $http = $this->http->withHeader('Content-Type', 'application/json');
        $this->assertEquals('application/json', $http->getHeader('content-type')[0]);
        $this->assertEquals('application/json', $http->getHeader('CONTENT-TYPE')[0]);
        $this->assertEmpty($http->getHeader('foo'));
        $this->assertEmpty($this->http->getHeader('CoNtEnT-TyPe'));
    }

    public function testRemovingHeader()
    {
        $http = $this->http->withHeader('Content-Type', 'application/json');
        $this->assertEquals('application/json', $http->getHeader('content-type')[0]);
        $http = $http->withoutHeader('Content-Type');
        $this->assertEmpty($http->getHeader('CONTENT-TYPE'));
    }
}