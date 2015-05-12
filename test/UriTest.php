<?php
/**
 * Unit test for \Fusion\Http\Uri class.
 */
namespace Fusion\Http\Test;

use Fusion\Http\Uri;

require '../vendor/autoload.php';

class UriTest extends \PHPUnit_Framework_TestCase
{
    private $uri = null;

    private $testUrl = 'http://billybob:foobar@example.org/resource/target?query=blah#fragmerunning';

    public function setUp()
    {
        $this->uri = new Uri();
    }

    public function tearDown()
    {
        $this->uri = null;
    }


}