<?php
/**
 * Unit test for \Fusion\Http\Uri class.
 */
namespace Fusion\Http\Test;

use Fusion\Http\Uri;

require '../vendor/autoload.php';

class UriTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Fusion\Http\Uri
     */
    private $uri = null;

    private $testUrl = 'http://billybob:foobar@example.org:8080/resource/target?query=blah#fragmerunning';

    public function setUp()
    {
        $this->uri = new Uri($this->testUrl);
    }

    public function tearDown()
    {
        $this->uri = null;
    }

    //Basic checking to make sure the URL is being parsed correctly without attempting
    //to encode any characters.

    public function testGettingScheme()
    {
        $this->assertEquals('http', $this->uri->getScheme());
    }

    public function testGettingAuthority()
    {
        $this->assertEquals('billybob:foobar@example.org:8080', $this->uri->getAuthority());
    }

    public function testGettingUserInfo()
    {
        $this->assertEquals('billybob:foobar', $this->uri->getUserInfo());
    }

    public function testGettingHost()
    {
        $this->assertEquals('example.org', $this->uri->getHost());
    }

    public function testGettingStandardPort()
    {
        $this->uri = new Uri('https://www.example.org');
        $uri = $this->uri->withPort(443);
        $this->assertNull($uri->getPort());
    }

    public function testGettingNonStandardPort()
    {
        $this->assertEquals(8080, $this->uri->getPort());
    }

    public function testGettingPath()
    {
        $this->assertEquals('/resource/target', $this->uri->getPath());
    }

    public function testGettingQuery()
    {
        $this->assertEquals('query=blah', $this->uri->getQuery());
    }

    public function testGettingFragment()
    {
        $this->assertEquals('fragmerunning', $this->uri->getFragment());
    }

    public function testChangingScheme()
    {
        $uri = $this->uri->withScheme('https');
        $this->assertNotSame($uri, $this->uri);
        $this->assertEquals('http', $this->uri->getScheme());
        $this->assertEquals('https', $uri->getScheme());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @dataProvider badStringData
     */
    public function testChangingSchemeWithBad($data)
    {
        $this->uri->withScheme($data);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @dataProvider badStringData
     */
    public function testChangingSchemeWithInvalid($scheme)
    {
        $this->uri->withScheme('ftp://');
    }

    public function testChangingUserInfo()
    {
        $uri = $this->uri->withUserInfo('foo', 'bar');
        $this->assertNotSame($uri, $this->uri);
        $this->assertEquals('billybob:foobar', $this->uri->getUserInfo());
        $this->assertEquals('foo:bar', $uri->getUserInfo());
    }

    public function testChangingUserWithoutPassword()
    {
        $uri = $this->uri->withUserInfo('foo');
        $this->assertNotSame($uri, $this->uri);
        $this->assertEquals('billybob:foobar', $this->uri->getUserInfo());
        $this->assertEquals('foo', $uri->getUserInfo());
    }

    public function testChangingUserRemovingUserNullValue()
    {
        $uri = $this->uri->withUserInfo(null, 'pwWontMakeTheCut');
        $this->assertNotSame($uri, $this->uri);
        $this->assertEquals('billybob:foobar', $this->uri->getUserInfo());
        $this->assertEquals('', $uri->getUserInfo());
    }

    public function testChangingUserRemovingUserEmptyString()
    {
        $uri = $this->uri->withUserInfo('', 'pwWontMakeTheCut');
        $this->assertNotSame($uri, $this->uri);
        $this->assertEquals('billybob:foobar', $this->uri->getUserInfo());
        $this->assertEquals('', $uri->getUserInfo());
    }

    public function testChangingHost()
    {
        $this->uri = new Uri('http://www.foobar.net');
        $uri = $this->uri->withHost('www.blazbim.org');
        $this->assertNotSame($uri, $this->uri);
        $this->assertEquals('www.foobar.net', $this->uri->getHost());
        $this->assertEquals('www.blazbim.org', $uri->getHost());
        $this->assertEquals('http://www.blazbim.org', $uri->make());
    }

    public function testRemovingHost()
    {
        $uri = $this->uri->withHost('');
        $this->assertNotSame($uri, $this->uri);
        $this->assertEquals('http:/resource/target?query=blah#fragmerunning', $uri->make());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @dataProvider badStringData
     */
    public function testChangingHostWithBadHost($data)
    {
        $this->uri->withHost($data);
    }

    public function badStringData()
    {
        return [
            [PHP_INT_MAX],
            [3.14159265359],
            [[]],
            [true],
            [null],
            [fopen('CrashTestFile.txt', 'r')]
        ];
    }

}