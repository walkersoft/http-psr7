<?php
/**
 * Created by PhpStorm.
 * User: Jason Walker
 * Date: 8/4/2015
 * Time: 10:38 PM
 */

namespace Fusion\Http\Test;

use Fusion\Http\Response;

require '../vendor/autoload.php';

class ResponseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Fusion\Http\Response
     */
    private $response;

    public function setUp()
    {
        $this->response = new Response();
    }

    public function tearDown()
    {
        $this->response = null;
    }

    public function testDefaultResponse()
    {
        $this->assertEquals(200, $this->response->getStatusCode());
        $this->assertEquals('OK', $this->response->getReasonPhrase());
    }

    public function testSettingStatusCode()
    {
        $response = $this->response->withStatus(500);
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('Internal Server Error', $response->getReasonPhrase());
        $this->assertNotSame($response, $this->response);
    }

    public function testSettingCustomCodeAndReason()
    {
        $response = $this->response->withStatus(475, 'About 25 away from an Internal Server Error');
        $this->assertEquals(475, $response->getStatusCode());
        $this->assertEquals('About 25 away from an Internal Server Error', $response->getReasonPhrase());
        $this->assertNotSame($response, $this->response);
    }

    public function testingSettingCustomCodeWithoutReason()
    {
        $response = $this->response->withStatus(475);
        $this->assertEquals(475, $response->getStatusCode());
        $this->assertEquals('', $response->getReasonPhrase());
        $this->assertNotSame($response, $this->response);
    }

    /**
     * @dataProvider standardResponses
     */
    public function testSettingStandardResponseCodes($code, $phrase)
    {
        $response = $this->response->withStatus($code);
        $this->assertEquals($code, $response->getStatusCode());
        $this->assertEquals($phrase, $response->getReasonPhrase());
        $this->assertNotSame($response, $this->response);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @dataProvider invalidResponses
     */
    public function testSettingBadStatusCodes($code)
    {
        $this->response->withStatus($code);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @dataProvider invalidResponses
     */
    public function testCreatingNewResponseWithBadStatusCodes($code)
    {
        $this->response = new Response($code);
    }

    /**
     * @dataProvider nonstandardOrOddButStillAcceptableResponses
     */
    public function testSettingOtherAcceptableResponses($code)
    {
        $response = $this->response->withStatus($code);
        $this->assertEquals(intval($code), $response->getStatusCode());
        $this->assertNotSame($response, $this->response);
    }

    public function nonstandardOrOddButStillAcceptableResponses()
    {
        return [
            [599],
            [200.1],
            ['404'],
            [1 + '101 dalmations']
        ];
    }

    public function invalidResponses()
    {
        return [
            [0],
            [12],
            [23],
            [34],
            [45],
            [56],
            [600],
            [2211],
            [true],
            [null],
            [20391.111029]
        ];
    }

    public function standardResponses()
    {
        return [
            [100, 'Continue'],
            [102, 'Processing'],
            [200, 'OK'],
            [201, 'Created'],
            [202, 'Accepted'],
            [203, 'Non-Authoritative Information'],
            [204, 'No Content'],
            [205, 'Reset Content'],
            [206, 'Partial Content'],
            [207, 'Multi-Status'],
            [300, 'Multiple Choices'],
            [301, 'Moved Permanently'],
            [302, 'Found'],
            [303, 'See Other'],
            [304, 'Not Modified'],
            [305, 'Use Proxy'],
            [306, 'unused'],
            [307, 'Temporary Redirect'],
            [400, 'Bad Request'],
            [401, 'Authorization Required'],
            [402, 'Payment Required'],
            [403, 'Forbidden'],
            [404, 'Not Found'],
            [405, 'Method Not Allowed'],
            [406, 'Not Acceptable'],
            [407, 'Proxy Authentication Required'],
            [408, 'Request Time-out'],
            [409, 'Conflict'],
            [410, 'Gone'],
            [411, 'Length Required'],
            [412, 'Precondition Failed'],
            [413, 'Request Entity Too Large'],
            [414, 'Request-URI Too Large'],
            [415, 'Unsupported Media Type'],
            [416, 'Requested Range Not Satisfiable'],
            [417, 'Expectation Failed'],
            [418, 'unused'],
            [419, 'unused'],
            [420, 'unused'],
            [421, 'unused'],
            [422, 'Unprocessable Entity'],
            [423, 'Locked'],
            [424, 'Failed Dependency'],
            [425, 'No code'],
            [426, 'Upgrade Required'],
            [500, 'Internal Server Error'],
            [501, 'Method Not Implemented'],
            [502, 'Bad Gateway'],
            [503, 'Service Temporarily Unavailable'],
            [504, 'Gateway Time-out'],
            [505, 'HTTP Version Not Supported'],
            [506, 'Variant Also Negotiates'],
            [507, 'Insufficient Storage'],
            [508, 'unused'],
            [509, 'unused'],
            [510, 'Not Extended']
        ];
    }

}