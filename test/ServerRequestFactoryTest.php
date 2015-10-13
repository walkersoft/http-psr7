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
    private $factory;

    public function setUp()
    {
        if(!isset($_SERVER) || (isset($_SERVER) && !is_array($_SERVER)))
        {
            $_SERVER = [];
        }
        $this->factory = new ServerRequestFactory();
        $this->factory->configureDefaults();
    }

    public function tearDown()
    {
        unset($this->request);
    }

    public function testBuildingVanillaRequest()
    {
        $request = $this->factory->makeServerRequest();
        $this->assertInstanceOf('\Psr\Http\Message\ServerRequestInterface', $request);
    }

    public function testConfiguringMethod()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->factory->configureDefaults();
        $request = $this->factory->makeServerRequest();
        $this->assertInstanceOf('\Psr\Http\Message\ServerRequestInterface', $request);
        $this->assertEquals('POST', $request->getMethod());
    }

    public function testConfiguringHeadersWithServerInfo()
    {
        $this->injectServerVars();
        $this->factory->configureDefaults();
        $request = $this->factory->makeServerRequest();
        $this->assertInstanceOf('\Psr\Http\Message\ServerRequestInterface', $request);
        $this->assertEquals('localhost', $request->getHeader('host')[0]);
        $this->assertEquals('', $request->getHeaderLine('host'));
    }

    private function injectServerVars()
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['HTTP_CONNECTION'] = 'keep-alive';
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'ServerRequestFactoryTest';
    }
}