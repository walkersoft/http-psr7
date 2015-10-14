<?php
/**
 * Part of the Fusion.Http component package.
 *
 * @author Jason L. Walker
 * @license MIT
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

    public function testGettingInvalidPort()
    {
        $uri = $this->uri->withPort(67000);
        $this->assertNotSame($uri, $this->uri);
        $this->assertEquals(8080, $this->uri->getPort());
        $this->assertNull($uri->getPort());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @dataProvider badPortData
     */
    public function testChangingPortWithBad($data)
    {
        $this->uri->withPort($data);
    }

    public function testChangingPort()
    {
        $uri = $this->uri->withPort(1337);
        $this->assertNotSame($uri, $this->uri);
        $this->assertEquals(1337, $uri->getPort());
        $this->assertEquals(8080, $this->uri->getPort());
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

    public function testChangingPath()
    {
        $uri = $this->uri->withPath('/resource/endpoint');
        $this->assertNotSame($uri, $this->uri);
        $this->assertEquals('/resource/endpoint', $uri->getPath());
        $this->assertEquals('/resource/target', $this->uri->getPath());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @dataProvider badStringData
     */
    public function testChangingPathWithBad($data)
    {
        $this->uri->withPath($data);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testChangingPathWithQueryData()
    {
        $this->uri->withPath('?query');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testChangingPathWithFragmentData()
    {
        $this->uri->withPath('#fragment');
    }

    public function testChangingQueryData()
    {
        $uri = $this->uri->withQuery('foo=bar&baz=blim');
        $this->assertNotSame($uri, $this->uri);
        $this->assertEquals('foo=bar&baz=blim', $uri->getQuery());
        $this->assertEquals('query=blah', $this->uri->getQuery());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @dataProvider badStringData
     */
    public function testChangingQueryWithBad($data)
    {
        $this->uri->withQuery($data);
    }

    public function testChangingQueryTrimmingDelimiter()
    {
        $uri = $this->uri->withQuery('?foo=bar&baz=blim');
        $this->assertNotSame($uri, $this->uri);
        $this->assertEquals('foo=bar&baz=blim', $uri->getQuery());
        $this->assertEquals('query=blah', $this->uri->getQuery());
    }

    public function testRemovingQuery()
    {
        $uri = $this->uri->withQuery('');
        $this->assertNotSame($uri, $this->uri);
        $this->assertEmpty($uri->getQuery());
        $this->assertEquals('query=blah', $this->uri->getQuery());
    }

    public function testChangingFragment()
    {
        $uri = $this->uri->withFragment('abruptlyCutOffBegi');
        $this->assertNotSame($uri, $this->uri);
        $this->assertEquals('abruptlyCutOffBegi', $uri->getFragment());
        $this->assertEquals('fragmerunning', $this->uri->getFragment());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @dataProvider badStringData
     */
    public function testChangingFragmentWithBad($data)
    {
        $this->uri->withFragment($data);
    }

    public function testChangingFragmentTrimmingDelimiter()
    {
        $uri = $this->uri->withFragment('#abruptlyCutOffBegi');
        $this->assertNotSame($uri, $this->uri);
        $this->assertEquals('abruptlyCutOffBegi', $uri->getFragment());
        $this->assertEquals('fragmerunning', $this->uri->getFragment());
    }

    public function testRemovingFragment()
    {
        $uri = $this->uri->withFragment('');
        $this->assertNotSame($uri, $this->uri);
        $this->assertEquals('', $uri->getFragment());
        $this->assertEquals('fragmerunning', $this->uri->getFragment());
    }

    public function testNothingToEncode()
    {
        $this->uri = new Uri('http://www.example.com/some/where?query#fragment');
        $this->assertEquals('http://www.example.com/some/where?query#fragment', $this->uri->__toString());
    }

    public function testEncodingCharacters()
    {
        $this->uri = new Uri('http://www.example.com/so<%>me/wh . . ere?qu+={}ery#fragme()nt');
        $this->assertEquals('http://www.example.com/so%3C%25%3Eme/wh%20.%20.%20ere?qu+=%7B%7Dery#fragme()nt', $this->uri->__toString());
        $this->uri = new Uri('http://www.example.com/some/where?v=1;foo=bar&percent=?%#fragment#');
        $this->assertEquals('http://www.example.com/some/where?v=1;foo=bar&percent=?%#fragment%23', $this->uri->__toString());
        $this->uri = new Uri('http://www.example.com/some/where?qu*()^$¥?ery%2Fpercent%#fragment%%%');
        $this->assertEquals('http://www.example.com/some/where?qu*()%5E$%A5?ery%2Fpercent%25#fragment%25%25%25', $this->uri->__toString());
    }

    public function testQueryKeysWithoutValues()
    {
        $uri = new Uri('http://www.example.com/resource?v=1&action=edit&mode&time=money');
        $this->assertEquals('http://www.example.com/resource?v=1&action=edit&mode&time=money', $uri->make());
    }

    public function testAddingSlashBetweenAuthorityAndPath()
    {
        $uri = (new Uri('https://www.example.com'))->withPath('resource/node');
        $this->assertEquals('https://www.example.com/resource/node', $uri->make());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @dataProvider badStringData
     */
    public function testCreateUriWithBad($data)
    {
        new Uri($data);
    }

    public function badStringData()
    {
        return [
            [PHP_INT_MAX],
            [3.14159265359],
            [[]],
            [true],
            [null],
            [fopen('php://memory', 'r')]
        ];
    }

    public function badPortData()
    {
        return [
            [false],
            [null],
            [fopen('php://memory', 'r')],
            [[]],
            ['not a port']
        ];
    }

}