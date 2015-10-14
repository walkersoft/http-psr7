<?php
/**
 * Part of the Fusion.Http component package.
 *
 * @author Jason L. Walker
 * @license MIT
 */

require '../vendor/autoload.php';

use Fusion\Http\ServerRequest;

class ServerRequestTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \Fusion\Http\ServerRequest
     */
    private $serverRequest;

    public function setUp()
    {
        $this->serverRequest = new ServerRequest('GET', 'http://example.com');
    }

    public function tearDown()
    {
        $this->serverRequest = null;
    }

    public function testGettingServerVars()
    {
        $this->assertInternalType('array', $this->serverRequest->getServerParams());
        $this->assertGreaterThan(0, count($this->serverRequest->getServerParams()));
    }

    public function testGettingSpecificServerParam()
    {
        //Inject a value into $_SERVER to make sure we have something to check for
        $_SERVER['fusionHttp'] = 'PSR-7';
        $this->serverRequest = $this->buildRequestWithAllFields();
        $this->assertEquals('PSR-7', $this->serverRequest->getServerParams()['fusionHttp']);
    }

    public function testGettingEmptyQueryParams()
    {
        $this->assertInternalType('array', $this->serverRequest->getQueryParams());
        $this->assertEquals(0, count($this->serverRequest->getQueryParams()));
    }

    public function testGettingSpecificQueryParam()
    {
        $this->serverRequest = $this->buildRequestWithAllFields();
        $this->assertEquals('bar', $this->serverRequest->getQueryParams()['foo']);
    }

    public function testChangingQueryParams()
    {
        $request = $this->serverRequest->withQueryParams(['bar' => 'foo']);
        $this->assertEquals('foo', $request->getQueryParams()['bar']);
        $this->assertNotSame($request, $this->serverRequest);
    }

    public function testGettingEmptyCookies()
    {
        $this->assertInternalType('array', $this->serverRequest->getCookieParams());
        $this->assertEquals(0, count($this->serverRequest->getCookieParams()));
    }

    public function testGettingSpecificCookie()
    {
        $this->serverRequest = $this->buildRequestWithAllFields();
        $this->assertEquals('thorton123', $this->serverRequest->getCookieParams()['token']);
    }

    public function testChangingCookies()
    {
        $request = $this->serverRequest->withCookieParams(['name' => 'billy']);
        $this->assertEquals('billy', $request->getCookieParams()['name']);
        $this->assertNotSame($request, $this->serverRequest);
    }

    public function testGettingEmptyAttributes()
    {
        $this->assertInternalType('array', $this->serverRequest->getAttributes());
        $this->assertEquals(0, count($this->serverRequest->getAttributes()));
    }

    public function testGettingSpecificAttribute()
    {
        $this->serverRequest = $this->buildRequestWithAllFields();
        $this->assertTrue($this->serverRequest->getAttribute('routeMatch'));
    }

    public function testChangingAttribute()
    {
        $request = $this->serverRequest->withAttribute('blah', 'bleh');
        $this->assertEquals('bleh', $request->getAttribute('blah'));
        $this->assertNotSame($request, $this->serverRequest);
    }

    public function testRemovingAttribute()
    {
        $this->serverRequest = $this->buildRequestWithAllFields();
        $this->assertTrue($this->serverRequest->getAttribute('routeMatch'));
        $request = $this->serverRequest->withoutAttribute('routeMatch');
        $this->assertNull($request->getAttribute('routeMatch'));
        $this->assertNotSame($request, $this->serverRequest);
    }

    public function testGettingEmptyParsedBody()
    {
        $this->assertNull($this->serverRequest->getParsedBody());
    }

    public function testGettingNonEmptyParsedBody()
    {
        $data = [];
        parse_str('foo=bar&baz=bim&blam', $data);
        $request = $this->serverRequest->withParsedBody($data);
        $this->assertEquals('bar', $request->getParsedBody()['foo']);
        $this->assertEquals('bim', $request->getParsedBody()['baz']);
        $this->assertEquals('', $request->getParsedBody()['blam']);
        $this->assertNotSame($request, $this->serverRequest);
    }

    public function testChangingParsedBodyAsArray()
    {
        $request = $this->serverRequest->withParsedBody([]);
        $this->assertInternalType('array', $request->getParsedBody());
        $this->assertNotSame($request, $this->serverRequest);
    }

    public function testChangingParsedBodyAsObject()
    {
        $request = $this->serverRequest->withParsedBody(new \stdClass());
        $this->assertInstanceOf('\stdClass', $request->getParsedBody());
        $this->assertNotSame($request, $this->serverRequest);
    }

    public function testChangingParsedBodyAsNull()
    {
        $request = $this->serverRequest->withParsedBody(null);
        $this->assertNull($request->getParsedBody());
        $this->assertNotSame($request, $this->serverRequest);
    }

    public function testGettingUploadedFiles()
    {
        $this->assertInternalType('array', $this->serverRequest->getUploadedFiles());
        $this->assertEquals(0, count($this->serverRequest->getUploadedFiles()));
    }

    public function testChangingUploadedFiles()
    {
        $request = $this->serverRequest->withUploadedFiles(
            [
                $this->getMock('\Psr\Http\Message\UploadedFileInterface'),
                $this->getMock('\Psr\Http\Message\UploadedFileInterface'),
                $this->getMock('\Psr\Http\Message\UploadedFileInterface')
            ]
        );
        $this->assertInternalType('array', $request->getUploadedFiles());
        $this->assertEquals(3, count($request->getUploadedFiles()));
        $this->assertNotSame($request, $this->serverRequest);
    }

    public function testForcingQueryStringNormalization()
    {
        $_SERVER['QUERY_STRING'] = 'foo=bar&baz=bim&blap';
        $this->serverRequest = new ServerRequest('GET', 'http://example.com/');
        $this->assertEquals('bar', $this->serverRequest->getQueryParams()['foo']);
        $this->assertEquals('bim', $this->serverRequest->getQueryParams()['baz']);
        $this->assertEquals('', $this->serverRequest->getQueryParams()['blap']);
    }

    /**
     * @dataProvider badUploadedFiles
     * @expectedException \InvalidArgumentException
     */
    public function testExceptionWhenGivenBadUploaded($files)
    {
        $this->serverRequest->withUploadedFiles([$files]);
    }

    /**
     * @dataProvider badParsedBody
     * @expectedException \InvalidArgumentException
     */
    public function testExceptionWhenGivenBadParsedBody($content)
    {
        $this->serverRequest->withParsedBody($content);
    }

    public function badUploadedFiles()
    {
        return [
            [PHP_INT_MAX],
            [20815.2210331],
            [false],
            [null],
            [new \stdClass()],
            [fopen('php://memory', 'w+')],
            ['foobar']
        ];
    }

    public function badParsedBody()
    {
        return [
            [PHP_INT_MAX],
            [20815.2210331],
            [false],
            [fopen('php://memory', 'w+')],
            ['foobar']
        ];
    }

    private function buildRequestWithAllFields()
    {
        $request = new ServerRequest(
            'GET', //HTTP Method
            'http://example.net/foo/bar?baz=bam&bim=bop', //The URI
            [ //Headers
                'X-Http-Powered-By' => 'Fusion HTTP PSR-7',
                'User-Agent' => 'PHPUnit TestCase'
            ],
            new Fusion\Http\Stream(fopen('php://input', 'r')), //Stream
            [ //Attributes
                'error' => 'none',
                'routeMatch' => true
            ],
            [ //Query Vars
                'foo' => 'bar',
                'baz' => 'bim'
            ],
            [ //Cookies
                'user' => 'bob',
                'token' => 'thorton123'
            ],
            [ //Uploaded files
                $this->getMock('\Psr\Http\Message\UploadedFileInterface'),
                $this->getMock('\Psr\Http\Message\UploadedFileInterface'),
                [
                    $this->getMock('\Psr\Http\Message\UploadedFileInterface'),
                    [
                        $this->getMock('\Psr\Http\Message\UploadedFileInterface'),
                        $this->getMock('\Psr\Http\Message\UploadedFileInterface'),
                    ],
                    $this->getMock('\Psr\Http\Message\UploadedFileInterface')
                ],
                $this->getMock('\Psr\Http\Message\UploadedFileInterface')
            ]
        );

        return $request;
    }
}