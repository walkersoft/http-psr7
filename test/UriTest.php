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

    public function testGettingScheme()
    {
        $this->assertEquals('http', $this->uri->getScheme());
    }

    public function testGettingAuthority()
    {
        $this->assertEquals('billybob:foobar@example.org', $this->uri->getAuthority());
    }

    public function testGettingUserInfo()
    {
        $this->assertEquals('billybob:foobar', $this->uri->getUserInfo());
    }

    public function testGettingHost()
    {
        $this->assertEquals('example.org', $this->uri->getHost());
    }

    public function testGettingPort()
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
}