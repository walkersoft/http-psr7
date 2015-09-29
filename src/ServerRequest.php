<?php
/**
 * Created by PhpStorm.
 * User: jwalker
 * Date: 9/22/2015
 * Time: 7:44 AM
 */

namespace Fusion\Http;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;

class ServerRequest extends Request implements ServerRequestInterface
{

    /**
     * Server parameters.
     *
     * @var array
     */
    private $serverVars = [];

    /**
     * Cookie values.
     *
     * @var array
     */
    private $cookies = [];

    /**
     * Uploaded files.
     *
     * @var UploadedFileInterface[]
     */
    private $uploads = [];

    /**
     * Parsed body data.
     *
     * @var mixed
     */
    private $parsedBody = null;

    /**
     * List of additional attributes.
     *
     * @var array
     */
    private $attributes = [];

    /**
     * List of query parameters.
     *
     * @var array
     */
    private $queryVars = [];

    /**
     * List of parameters from a POST request.
     *
     * @var array
     */
    private $postVars = [];

    /**
     * Constructor.
     *
     * Creates a new ServerRequest instance.
     *
     * @param string $method The HTTP method of the request.
     * @param string|UriInterface $uri The URI of the request.
     * @param array $headers Initial headers of the request.
     * @param StreamInterface $body StreamInterface for the HTTP message body.
     * @param array $attributes Set of initial attributes for the request.
     * @param array $queryVars Set of initial query variables (e.g. from $_GET).
     * @param array $cookies Set of initial cookie values (e.g. from $_COOKIES).
     * @param UploadedFileInterface[] $files Array tree of UploadedFileInterface
     *     instances.
     */
    public function __construct(
        $method,
        $uri,
        $headers = [],
        StreamInterface $body = null,
        $attributes = [],
        $queryVars = [],
        $cookies = [],
        $files = []
    )
    {
        parent::__construct($method, $uri, $headers, $body);

        $this->serverVars = isset($_SERVER) ? $_SERVER : [];
        $this->cookies = empty($cookies) ? $_COOKIE : $cookies;
        $this->queryVars = empty($queryVars) ? $this->normalizeQueryString() : $queryVars;
        $this->postVars = isset($_POST) ? $_POST : [];

        if(!empty($files))
        {
            $this->verifyUploadedFiles($files);
            $this->uploads = $files;
        }
    }

    /**
     * Retrieve server parameters.
     *
     * Retrieves data related to the incoming request environment,
     * typically derived from PHP's $_SERVER superglobal. The data IS NOT
     * REQUIRED to originate from $_SERVER.
     *
     * @return array
     */
    public function getServerParams()
    {
        return $this->serverVars;
    }

    /**
     * Retrieve cookies.
     *
     * Retrieves cookies sent by the client to the server.
     *
     * The data MUST be compatible with the structure of the $_COOKIE
     * superglobal.
     *
     * @return array
     */
    public function getCookieParams()
    {
        return $this->cookies;
    }

    /**
     * Return an instance with the specified cookies.
     *
     * The data IS NOT REQUIRED to come from the $_COOKIE superglobal, but MUST
     * be compatible with the structure of $_COOKIE. Typically, this data will
     * be injected at instantiation.
     *
     * This method MUST NOT update the related Cookie header of the request
     * instance, nor related values in the server params.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated cookie values.
     *
     * @param array $cookies Array of key/value pairs representing cookies.
     * @return self
     */
    public function withCookieParams(array $cookies)
    {
        $clone = clone $this;
        $clone->cookies = $cookies;

        return $clone;
    }

    /**
     * Retrieve query string arguments.
     *
     * Retrieves the deserialized query string arguments, if any.
     *
     * Note: the query params might not be in sync with the URI or server
     * params. If you need to ensure you are only getting the original
     * values, you may need to parse the query string from `getUri()->getQuery()`
     * or from the `QUERY_STRING` server param.
     *
     * @return array
     */
    public function getQueryParams()
    {
        return $this->queryVars;
    }

    /**
     * Return an instance with the specified query string arguments.
     *
     * These values SHOULD remain immutable over the course of the incoming
     * request. They MAY be injected during instantiation, such as from PHP's
     * $_GET superglobal, or MAY be derived from some other value such as the
     * URI. In cases where the arguments are parsed from the URI, the data
     * MUST be compatible with what PHP's parse_str() would return for
     * purposes of how duplicate query parameters are handled, and how nested
     * sets are handled.
     *
     * Setting query string arguments MUST NOT change the URI stored by the
     * request, nor the values in the server params.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated query string arguments.
     *
     * @param array $query Array of query string arguments, typically from
     *     $_GET.
     * @return self
     */
    public function withQueryParams(array $query)
    {
        $clone = clone $this;
        $clone->queryVars = $query;

        return $clone;
    }

    /**
     * Retrieve normalized file upload data.
     *
     * This method returns upload metadata in a normalized tree, with each leaf
     * an instance of Psr\Http\Message\UploadedFileInterface.
     *
     * These values MAY be prepared from $_FILES or the message body during
     * instantiation, or MAY be injected via withUploadedFiles().
     *
     * @return array An array tree of UploadedFileInterface instances; an empty
     *     array MUST be returned if no data is present.
     */
    public function getUploadedFiles()
    {
        return $this->uploads;
    }

    /**
     * Create a new instance with the specified uploaded files.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated body parameters.
     *
     * @param array $uploadedFiles An array tree of UploadedFileInterface instances.
     * @return self
     * @throws \InvalidArgumentException if an invalid structure is provided.
     */
    public function withUploadedFiles(array $uploadedFiles)
    {
        $this->verifyUploadedFiles($uploadedFiles);

        $clone = clone $this;
        $clone->uploads = $uploadedFiles;

        return $clone;
    }

    /**
     * Retrieve any parameters provided in the request body.
     *
     * If the request Content-Type is either application/x-www-form-urlencoded
     * or multipart/form-data, and the request method is POST, this method MUST
     * return the contents of $_POST.
     *
     * Otherwise, this method may return any results of deserializing
     * the request body content; as parsing returns structured content, the
     * potential types MUST be arrays or objects only. A null value indicates
     * the absence of body content.
     *
     * @return null|array|object The deserialized body parameters, if any.
     *     These will typically be an array or object.
     */
    public function getParsedBody()
    {
        return $this->parsedBody;
    }

    /**
     * Return an instance with the specified body parameters.
     *
     * These MAY be injected during instantiation.
     *
     * If the request Content-Type is either application/x-www-form-urlencoded
     * or multipart/form-data, and the request method is POST, use this method
     * ONLY to inject the contents of $_POST.
     *
     * The data IS NOT REQUIRED to come from $_POST, but MUST be the results of
     * deserializing the request body content. Deserialization/parsing returns
     * structured data, and, as such, this method ONLY accepts arrays or objects,
     * or a null value if nothing was available to parse.
     *
     * As an example, if content negotiation determines that the request data
     * is a JSON payload, this method could be used to create a request
     * instance with the deserialized parameters.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated body parameters.
     *
     * @param null|array|object $data The deserialized body data. This will
     *     typically be in an array or object.
     * @return self
     * @throws \InvalidArgumentException if an unsupported argument type is
     *     provided.
     */
    public function withParsedBody($data)
    {
        if ($data !== null || !is_object($data) || !is_array($data))
        {
            throw new \InvalidArgumentException(
                sprintf('Parsed body data must be an object, an array or null. %s given', gettype($data))
            );
        }

        $clone = clone $this;
        $clone->parsedBody = $data;

        return $clone;
    }

    /**
     * Retrieve attributes derived from the request.
     *
     * The request "attributes" may be used to allow injection of any
     * parameters derived from the request: e.g., the results of path
     * match operations; the results of decrypting cookies; the results of
     * deserializing non-form-encoded message bodies; etc. Attributes
     * will be application and request specific, and CAN be mutable.
     *
     * @return array Attributes derived from the request.
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Retrieve a single derived request attribute.
     *
     * Retrieves a single derived request attribute as described in
     * getAttributes(). If the attribute has not been previously set, returns
     * the default value as provided.
     *
     * This method obviates the need for a hasAttribute() method, as it allows
     * specifying a default value to return if the attribute is not found.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @param mixed $default Default value to return if the attribute does not exist.
     * @return mixed
     */
    public function getAttribute($name, $default = null)
    {
        if (array_key_exists($name, $this->attributes))
        {
            $default = $this->attributes[$name];
        }

        return $default;
    }

    /**
     * Return an instance with the specified derived request attribute.
     *
     * This method allows setting a single derived request attribute as
     * described in getAttributes().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated attribute.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @param mixed $value The value of the attribute.
     * @return self
     */
    public function withAttribute($name, $value)
    {
        $clone = new $this;
        $clone->attributes[$name] = $value;
        return $clone;
    }

    /**
     * Return an instance that removes the specified derived request attribute.
     *
     * This method allows removing a single derived request attribute as
     * described in getAttributes().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that removes
     * the attribute.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @return self
     */
    public function withoutAttribute($name)
    {
        $clone = new $this;

        if (array_key_exists($name, $clone->attributes))
        {
            unset($clone->attributes[$name]);
        }

        return $clone;
    }

    /**
     * Returns values from $_POST superglobal or an empty array.
     *
     * @return array
     */
    public function getPostVars()
    {
        return $this->postVars;
    }

    /**
     * Verifies instances of UploadedFileInterface.
     *
     * Checks a single instance or array of
     *
     * @param array|UploadedFileInterface $file The item to verify.
     * @throws \RuntimeException When $files is not an array or an instance
     *     of UploadedFileInterface.
     */
    private function verifyUploadedFiles($file)
    {
        if(is_array($file))
        {
            foreach($file as $item)
            {
                $this->verifyUploadedFiles($item);
            }
        }

        if (!$file instanceof UploadedFileInterface)
        {
            throw new \RuntimeException(
                sprintf('Uploaded files must be an instance of UploadedFileInterface. %s given.',
                        is_object($file) ? get_class($file) : gettype($file)
                )
            );
        }
    }

    /**
     * Normalizes data from QUERY_STRING to be injected into $queryVars.
     *
     * @return array
     */
    private function normalizeQueryString()
    {
        $data = [];

        if (isset($this->serverVars['QUERY_STRING']))
        {
            $sets = explode('&', $this->serverVars['QUERY_STRING']);
            $count = count($sets);

            for ($i = 0; $i < $count; ++$i)
            {
                $pair = explode('=', $sets[$i], 2);
                if (count($pair) === 1)
                {
                    $data[$pair[0]] = null;
                }
                else
                {
                    $data[$pair[0]] = $pair[1];
                }
            }
        }

        return $data;
    }
}