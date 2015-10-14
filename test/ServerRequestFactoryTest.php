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

    public function testMakingUploadedFile()
    {
        $this->injectSingleFile();
        $this->factory->configureDefaults();
        $request = $this->factory->makeServerRequest();
        $this->assertInstanceOf('\Psr\Http\Message\ServerRequestInterface', $request);
        $file = $request->getUploadedFiles()[0];
        $this->assertEquals('foobar.txt', $file->getClientFilename());
        $this->assertEquals(10, $file->getSize());
        $file->getStream()->close();
        //cleanup
        $text = 'foobar.txt';
        unlink($text);
    }

    public function testMakingMultipleUploadedFiles()
    {
        $this->injectMultipleFiles();
        $this->factory->configureDefaults();
        $request = $this->factory->makeServerRequest();
        $this->assertInstanceOf('\Psr\Http\Message\ServerRequestInterface', $request);

        $file1 = $request->getUploadedFiles()[0];
        $this->assertEquals('foobar.txt', $file1->getClientFilename());
        $this->assertEquals(10, $file1->getSize());
        $file1->getStream()->close();

        $file2 = $request->getUploadedFiles()[1];
        $this->assertEquals('barfoo.txt', $file2->getClientFilename());
        $this->assertEquals(10, $file2->getSize());
        $file2->getStream()->close();

        //cleanup
        $text1 = 'foobar.txt';
        $text2 = 'barfoo.txt';
        unlink($text1);
        unlink($text2);
    }

    private function injectServerVars()
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['HTTP_CONNECTION'] = 'keep-alive';
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'ServerRequestFactoryTest';
    }

    private function injectSingleFile()
    {
        //Interact with the actual filesystem to do this.
        $text = 'foobar.txt';
        $handle = fopen($text, 'w');
        fwrite($handle, $text);
        fclose($handle);
        $_FILES['test']['tmp_name'] = $text;
        $_FILES['test']['name'] = $text;
        $_FILES['test']['size'] = filesize($text);
        $_FILES['test']['error'] = 0;
        $_FILES['test']['type'] = 'text/plain';
    }

    private function injectMultipleFiles()
    {
        //Interact with the actual filesystem to do this.
        $text1 = 'foobar.txt';
        $handle = fopen($text1, 'w');
        fwrite($handle, $text1);
        fclose($handle);
        $_FILES['test']['tmp_name'][0] = $text1;
        $_FILES['test']['name'][0] = $text1;
        $_FILES['test']['size'][0] = filesize($text1);
        $_FILES['test']['error'][0] = 0;
        $_FILES['test']['type'][0] = 'text/plain';

        $text2 = 'barfoo.txt';
        $handle = fopen($text2, 'w');
        fwrite($handle, $text2);
        fclose($handle);
        $_FILES['test']['tmp_name'][1] = $text2;
        $_FILES['test']['name'][1] = $text2;
        $_FILES['test']['size'][1] = filesize($text2);
        $_FILES['test']['error'][1] = 0;
        $_FILES['test']['type'][1] = 'text/plain';
    }
}