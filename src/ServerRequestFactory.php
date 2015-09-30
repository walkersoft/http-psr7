<?php
/**
 * Created by PhpStorm.
 * User: Jason Walker
 * Date: 9/29/2015
 * Time: 8:29 PM
 */

namespace Fusion\Http;

use Fusion\Http\Interfaces\ServerRequestFactoryInterface;

class ServerRequestFactory implements ServerRequestFactoryInterface
{
    /**
     * Configured request method.
     *
     * @var string
     */
    private $method = '';

    /**
     * Configured URI as a string or \Psr\Http\Message\UriInterface
     *
     * @var string|\Psr\Http\Message\UriInterface
     */
    private $uri = '';

    /**
     * Configured request headers.
     *
     * @var array
     */
    private $headers = [];

    /**
     * Configured body as Psr\Http\Message\StreamInterface
     *
     * @var \Psr\Http\Message\StreamInterface
     */
    private $body = null;

    /**
     * Configured server attributes for the incoming request.
     *
     * @var array
     */
    private $attributes = [];

    /**
     * Configured query parameters.
     *
     * @var array
     */
    private $query = [];

    /**
     * Configured cookie values.
     *
     * @var array
     */
    private $cookies = [];

    /**
     * Configured files as array of \Psr\Http\Message\UploadedFileInterface instances.
     *
     * @var \Psr\Http\Message\UploadedFileInterface[]
     */
    private $uploads = [];


    /**
     * Instantiates and configures a ServerRequest object.
     *
     * By default this factory will attempt to build the ServerRequest with
     * the information available to it via environment information or
     * otherwise.
     *
     * Optionally an associative array of parameters may be specified that will
     * override the default values so that the ServerRequest may be configured
     * by the client code.
     *
     * The implementation will accept the following array keys that can be
     * be defined as overrides in the $params array:
     *
     *  - 'method' : The HTTP method used for the incoming request.
     *  - 'uri' : A valid URI as a string or instance of Psr\Http\Message\UriInterface.
     *  - 'headers' : An array of headers for the incoming request.
     *  - 'body' : An instance of Psr\Http\Message\StreamInterface.
     *  - 'attributes' : Additional attributes associated with the incoming request
     *  - 'queryVars' : Key-value pairs from query information (e.g.: $_GET)
     *  - 'cookies' : Incoming cookie key-value pairs (e.g.: $_COOKIE)
     *  - 'files' : Normalized array of Psr\Http\Message\UploadedFileInterface instances.
     *
     * @param array $params An array of optional values that will override
     *     default values specified by the implementation.
     * @return \Fusion\Http\ServerRequest
     */
    public function buildServerRequest(array $params = [])
    {
        // TODO: Implement buildServerRequest() method.
    }

    /**
     * Configures the request method.
     *
     * @param string $method The default method to send if one can't be determined.
     */
    public function configureMethod($method = 'GET')
    {
        $this->method = $method;

        if(isset($_SERVER['REQUEST_METHOD']))
        {
            $this->method = $_SERVER['REQUEST_METHOD'];
        }
    }

    /**
     * Configures the Uri as a string or UriInterface instance.
     */
    public function configureUri()
    {
        $uri = 'http';
        $uri .= isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS'])
            ? 's://'
            : '://';
        $uri .= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

        $this->uri = $uri;
    }

    /**
     * Configures headers from the incoming request.
     *
     * The headers are returned as an associative array with the header name
     * as the key and header contents as the value.  Headers with multiple
     * values in their contents should have the value assigned as an array.
     *
     * @see \Psr\Http\Message\MessageInterface::withHeader()
     * @return array
     */
    public function configureHeaders()
    {
        
    }

    /**
     * Configures the request message body.
     *
     * Creates an instance of \Psr\Http\Message\StreamInterface representing the
     * incoming server request body.  Typically this would be PHP built-in stream
     * php://input.
     *
     * @return \Psr\Http\Message\StreamInterface A StreamInterface representing
     *     the incoming request body.
     */
    public function configureBody()
    {

    }

    /**
     * Configures additional attributes.
     *
     * Attributes are key-value pairs with pieces of information relevant to the
     * incoming request at hand.  This value is typically overridden by client code.
     *
     * @return array An array of key-value pairs detailing additional attributes.
     */
    public function configureAttributes()
    {

    }

    /**
     * Configures query variable, typically from $_GET, as an array.
     *
     * @return array An array of key-value pairs of query variables.
     */
    public function configureQuery()
    {

    }

    /**
     * Configures cookies, if present, from the incoming request headers.
     *
     * This method MUST return an array that is consistent in structure with
     * the $_COOKIE super-global.
     *
     * @return array An array of cookie values consistent with $_COOKIE.
     */
    public function configureCookies()
    {

    }

    /**
     * Normalizes entries in $_FILES array.
     *
     * Checks the contents of the $_FILES super-global and creates a normalized
     * array of \Psr\Http\Message\UploadedFileInterface instances.
     *
     * This method SHOULD only attempt to configure uploads in SAPI environments.
     *
     * @return string The HTTP request method being used.
     */
    public function configureUploads()
    {

    }
}