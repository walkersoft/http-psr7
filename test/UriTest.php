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
    }

    public function tearDown()
    {
        $this->uri = null;
    }

    //Basic checking to make sure the URL is being parsed correctly without attempting
    //to encode any characters.

    public function testGettingScheme()
    {
        $this->uri = new Uri($this->testUrl);
        $this->assertEquals('http', $this->uri->getScheme());
    }

    public function testGettingAuthority()
    {
        $this->uri = new Uri($this->testUrl);
        $this->assertEquals('billybob:foobar@example.org:8080', $this->uri->getAuthority());
    }

    public function testGettingUserInfo()
    {
        $this->uri = new Uri($this->testUrl);
        $this->assertEquals('billybob:foobar', $this->uri->getUserInfo());
    }

    public function testGettingHost()
    {
        $this->uri = new Uri($this->testUrl);
        $this->assertEquals('example.org', $this->uri->getHost());
    }

    public function testGettingNonStandardPort()
    {
        $this->uri = new Uri($this->testUrl);
        $this->assertEquals(8080, $this->uri->getPort());
    }

    public function testGettingPath()
    {
        $this->uri = new Uri($this->testUrl);
        $this->assertEquals('/resource/target', $this->uri->getPath());
    }

    public function testGettingQuery()
    {
        $this->uri = new Uri($this->testUrl);
        $this->assertEquals('query=blah', $this->uri->getQuery());
    }

    public function testGettingFragment()
    {
        $this->uri = new Uri($this->testUrl);
        $this->assertEquals('fragmerunning', $this->uri->getFragment());
    }

    public function testGettingPort()
    {
        $this->uri = new Uri('https://www.example.org');
        $uri = $this->uri->withPort(443);
        $this->assertNull($uri->getPort());
    }
}