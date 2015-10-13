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
     * Constructor.
     *
     * Configures all properties with the default implementations. These can be
     * overridden when calling the buildServerRequest() method.
     *
     * @see \Fusion\Http\ServerRequestFactory::buildServerRequest()
     */
    public function __construct()
    {
        $this->configureDefaults();
    }


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
     *  - 'query' : Key-value pairs from query information (e.g.: $_GET)
     *  - 'cookies' : Incoming cookie key-value pairs (e.g.: $_COOKIE)
     *  - 'uploads' : Normalized array of Psr\Http\Message\UploadedFileInterface instances.
     *
     * @param array $params An array of optional values that will override
     *     default values specified by the implementation.
     * @return \Fusion\Http\ServerRequest
     */
    public function makeServerRequest(array $params = [])
    {
        $method = isset($params['method']) ? $params['method'] : $this->method;
        $uri = isset($params['uri']) ? $params['uri'] : $this->uri;
        $headers = isset($params['headers']) ? $params['headers'] : $this->headers;
        $body = isset($params['body']) ? $params['body'] : $this->body;
        $attributes = isset($params['attributes']) ? $params['attributes'] : $this->attributes;
        $query = isset($params['query']) ? $params['query'] : $this->query;
        $cookies = isset($params['cookies']) ? $params['cookies'] : $this->cookies;
        $uploads = isset($params['uploads']) ? $params['uploads'] : $this->uploads;

        $request = new ServerRequest(
            $method,
            $uri,
            $headers,
            $body,
            $attributes,
            $query,
            $cookies,
            $uploads
        );

        if(isset($_POST))
        {
            $request = $request->withParsedBody($_POST);
        }

        return $request;
    }

    /**
     * Configures the request method.
     *
     * @param string $method The default method to send if one can't be determined.
     */
    public function configureMethod($method = 'GET')
    {
        $this->method = $method;

        if (isset($_SERVER['REQUEST_METHOD']))
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
        $uri .= isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) ? 's://' : '://';
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
        $request = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $uri .= $host . $request;

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
     */
    public function configureHeaders()
    {
        if (isset($_SERVER))
        {
            foreach ($_SERVER as $header => $value)
            {
                if (strtolower(substr($header, 0, 5)) === "http_")
                {
                    $header = $this->formatHeader(substr($header, 5));
                    $this->headers[$header] = $value;
                }

                if (strtolower(substr($header, 0, 8)) === "content_")
                {
                    $header = $this->formatHeader($header);
                    $this->headers[$header] = $value;
                }
            }
        }
    }

    /**
     * Configures the request message body.
     *
     * Creates an instance of \Psr\Http\Message\StreamInterface representing the
     * incoming server request body.  Typically this would be PHP built-in stream
     * php://input or a copy of it in another stream.
     */
    public function configureBody()
    {
        $temp = fopen('php://temp', 'r+');
        if (array_key_exists('Content-Type', $this->headers)
            && $this->headers['Content-Type'] !== 'multipart/form-data'
        )
        {
            $input = fopen('php://input', 'r');
            stream_copy_to_stream($input, $temp);
        }

        $this->body = new Stream($temp);
    }

    /**
     * Configures additional attributes.
     *
     * Attributes are key-value pairs with pieces of information relevant to the
     * incoming request at hand.  This value is typically overridden by client code.
     */
    public function configureAttributes()
    {
        $this->attributes = [];
    }

    /**
     * Configures query variables, typically from values stored in $_GET.
     */
    public function configureQuery()
    {
        if (isset($_GET))
        {
            $this->query = $_GET;
        }
    }

    /**
     * Configures cookies, if present, from the incoming request headers.
     *
     * This method MUST return an array that is consistent in structure with
     * the $_COOKIE super-global.
     */
    public function configureCookies()
    {
        if (isset($_COOKIE))
        {
            $this->cookies = $_COOKIE;
        }
    }

    /**
     * Normalizes entries in $_FILES array.
     *
     * Checks the contents of the $_FILES super-global and creates a normalized
     * array of \Psr\Http\Message\UploadedFileInterface instances.
     *
     * This method SHOULD only attempt to configure uploads in SAPI environments
     * and will only operate properly on upload inputs names separately or a
     * single dimension array of files.
     */
    public function configureUploads()
    {
        if (isset($_FILES))
        {
            foreach($_FILES as $id => $data)
            {
                if(is_array($data) && array_key_exists('tmp_name', $data))
                {
                    if(is_array($data['tmp_name'])) //multiple files
                    {
                        $this->processUploads(
                            $this->processMultiFileUploadData($_FILES[$id])
                        );
                    }
                    else //dangerous assumption only a single file
                    {
                        $this->processUploads(
                            $this->processSingleFileUploadData($_FILES[$id])
                        );
                    }
                }
            }
        }
    }

    /**
     * Accepts uploaded file metadata and creates an UploadedFileInterface instance.
     *
     * @param array $uploads An array of uploaded file metadata found in $_FILES.
     */
    protected function processUploads(array $uploads)
    {
        foreach ($uploads as $upload)
        {
            if (isset($upload['tmp_name'])
                && !empty($upload['tmp_name'])
                && file_exists($upload['tmp_name'])
            )
            {
                $this->uploads[] = new UploadedFile(
                    new Stream(fopen($upload['tmp_name'], 'r')),
                    $upload['size'],
                    $upload['error'],
                    $upload['name'],
                    $upload['type']
                );
            }
        }
    }

    /**
     * Restructures and returns metadata of a single file for processing.
     *
     * @param array $file An array with file metadata.
     * @return array
     */
    protected function processSingleFileUploadData(array $file)
    {
        $data = [];
        foreach ($file as $field => $info)
        {
            $data[0][$field] = $info;
        }

        return $data;
    }

    /**
     * Restructures and returns metadata of multiple files for processing.
     *
     * @param array $files A multidimensional array with file metadata.
     * @return array
     */
    protected function processMultiFileUploadData(array $files)
    {
        $data = [];
        foreach ($files as $field => $info)
        {
            foreach ($info as $key => $value)
            {
                $data[$key][$field] = $files[$field][$key];
            }
        }

        return $data;
    }

    /**
     * Properly formats a header for later lookup and transmittal.
     *
     * The expectancy is that PHP will store HTTP header names similar to that
     * of constants in the $_SERVER super-global. The header names are in all
     * caps and hyphens are replaced with underscores. This method will normalize
     * those names into something normally seen in HTTP messages.
     *
     * Example: `HTTP_HOST` becomes `Host` and `HTTP_X_REQUESTED_WITH` becomes
     * `X-Requested-With`.
     *
     * @param string $header The header name.
     * @return string The formatted header.
     */
    protected function formatHeader($header)
    {
        $header = str_replace("_", " ", str_replace("-", " ", strtolower($header)));
        $header = str_replace(" ", "-", ucwords($header));
        return $header;
    }

    public function configureDefaults()
    {
        $this->configureMethod();
        $this->configureUri();
        $this->configureHeaders();
        $this->configureBody();
        $this->configureQuery();
        $this->configureCookies();
        $this->configureAttributes();
        $this->configureUploads();
    }
}