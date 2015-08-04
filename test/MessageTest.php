<?php
/**
 * Unit test for \Fusion\Http\Message class.
 */

namespace Fusion\Http\Test;

use Fusion\Http\Message;

require '../vendor/autoload.php';

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
    }

    public function testAddingAdditionalHeaders()
    {
        $http = $this->http->withHeader('foo', ['bar', 'baz', 'bim']);
        $this->assertTrue($http->hasHeader('FOO'));
        $this->assertEquals(3, count($http->getHeader('foo')));
        $http = $http->withAddedHeader('foo', ['bam', 'blip']);
        $this->assertEquals(5, count($http->getHeader('foo')));
    }

    public function testAddingAdditionalHeadersToMissingHeader()
    {
        $http = $this->http->withAddedHeader('foo', ['bar', 'baz', 'bim']);
        $this->assertTrue($http->hasHeader('FOO'));
        $this->assertEquals(3, count($http->getHeader('foo')));
    }

    public function testGettingHeader()
    {
        $http = $this->http->withHeader('Content-Type', 'application/json');
        $this->assertEquals('application/json', $http->getHeader('content-type')[0]);
        $this->assertEquals('application/json', $http->getHeader('CONTENT-TYPE')[0]);
        $this->assertEquals('application/json', $http->getHeaders()['Content-Type'][0]);
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

    public function testGetAllHeaders()
    {
        $http = $this->http->withHeader('Content-Type', 'application/json')
                           ->withHeader('X-Requested-With', 'foobar')
                           ->withHeader('Length', '302');
        $this->assertInternalType('array', $http->getHeaders());
        $this->assertEquals('foobar', $http->getHeaders()['X-Requested-With'][0]);
    }

    public function testGetHeaderLines()
    {
        $http = $this->http->withHeader('foo', ['bar', 'baz', 'bim']);
        $this->assertEquals('bar,baz,bim', $http->getHeaderLine('FOO'));
        $this->assertEmpty($http->getHeaderLine('X-nothing-to-see-here'));
    }

    public function testSettingBodyStream()
    {
        $mock = $this->getMock('\Psr\Http\Message\StreamInterface');
        $http = $this->http->withBody($mock);
        $this->assertInstanceOf('\Psr\Http\Message\StreamInterface', $http->getBody());
    }

    public function testRemoveNonexistentHeader()
    {
        $http = $this->http->withoutHeader('Content-Type');
        $this->assertSame($http, $this->http);
    }

    /**
     * @dataProvider badHeaders
     * @expectedException \InvalidArgumentException
     */
    public function testSettingBadHeaders($header, $value)
    {
        $http = $this->http->withHeader($header, $value);
        $this->assertEmpty($http->getHeaders());
    }

    /**
     * @dataProvider badHeaders
     * @expectedException \InvalidArgumentException
     */
    public function testSettingBadAdditionalHeaders($header, $value)
    {
        $http = $this->http->withHeader('Content-Type', 'foobar')->withAddedHeader($header, $value);
        $this->assertEquals(1, count($http->getHeaders()));
    }

    /**
     * @dataProvider badHeaderValues
     * @expectedException \InvalidArgumentException
     */
    public function testSettingBadAdditionalHeaderValues($header, $value)
    {
        $http = $this->http->withHeader('Content-Type', 'foobar')->withAddedHeader($header, $value);
        $this->assertEquals(1, count($http->getHeaders()));
    }

    /**
     * @dataProvider badHeaderValues
     * @expectedException \InvalidArgumentException
     */
    public function testSettingBadHeaderValues($header, $value)
    {
        $http = $this->http->withHeader($header, $value);
        $this->assertEmpty($http->getHeaders());
    }

    public function badHeaders()
    {
        return [
            [1234, 'foo'],
            [210.1118, 'foo'],
            [['foo'], 'foo'],
            [new \stdClass, 'foo'],
            [fopen('php://memory', 'r'), 'foo'],
            [true, 'foo'],
            [null, 'foo']
        ];
    }

    public function badHeaderValues()
    {
        return [
            ['Content-Type', PHP_INT_MAX],
            ['Content-Type', 10.11],
            ['Content-Type', new \stdClass],
            ['Content-Type', fopen('php://memory', 'r')],
            ['Content-Type', false],
            ['Content-Type', ['foo', 'bad-val-next', []]],
            ['Content-Type', null]
        ];
    }
}