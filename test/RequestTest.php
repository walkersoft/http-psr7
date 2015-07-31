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

    public function testGetEmptyRequestTarget()
    {
        $this->assertEquals('/', $this->request->getRequestTarget());
    }

}