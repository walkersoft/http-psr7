<?php
/**
 * Created by PhpStorm.
 * User: Jason Walker
 * Date: 10/6/2015
 * Time: 9:42 PM
 */

namespace Fusion\Http\Test;

require '../vendor/autoload.php';

use Fusion\Http\ServerRequestFactory;

class ServerRequestFactoryTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var \Fusion\Http\ServerRequestFactory
     */
    private $request;

    public function setUp()
    {
        if(!isset($_SERVER) || (isset($_SERVER) && !is_array($_SERVER)))
        {
            $_SERVER = [];
        }
        $this->request = new ServerRequestFactory();
    }

    public function tearDown()
    {
        unset($this->request);
    }

    public function testBuildingVanillaRequest()
    {
        $request = $this->request->makeServerRequest();
        $this->assertInstanceOf('\Psr\Http\Message\ServerRequestInterface', $request);
    }

    private function injectServerVars()
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['HTTP_CONNECTION'] = 'keep-alive';

    }
}