<?php
/**
 * Part of the Fusion.Http component package.
 *
 * @author Jason L. Walker
 * @license MIT
 */

namespace Fusion\Http;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

class Request extends Message implements RequestInterface
{

    /**
     * Request target.
     *
     * @var string
     */
    private $requestTarget = '';

    /**
     * Request method (HTTP Verb).
     *
     * @var string
     */
    private $requestMethod = '';

    /**
     * Request URI.
     *
     * @var UriInterface
     */
    private $requestUri = null;

    /**
     * Supported HTTP Verbs.
     *
     * @var array
     */
    private $supportedMethods = [
        'GET',
        'POST',
        'PUT',
        'PATCH',
        'DELETE',
        'OPTIONS',
        'HEAD',
        'TRACE',
        'CONNECT'
    ];

    /**
     * Constructor.
     *
     * Creates a new HTTP request message.
     *
     * @param string $method The HTTP method of the request.
     * @param string|UriInterface $uri The URI of the request.
     * @param array $headers Initial headers of the request.
     */
    public function __construct($method, $uri, array $headers = [])
    {
        // TODO: Figure out all the needed params for a request.

        //need a method
        //need starting headers
        //need uri
        $this->prepare($method, $uri, $headers);

    }

    /**
     * Retrieves the message's request target.
     *
     * Retrieves the message's request-target either as it will appear (for
     * clients), as it appeared at request (for servers), or as it was
     * specified for the instance (see withRequestTarget()).
     *
     * In most cases, this will be the origin-form of the composed URI,
     * unless a value was provided to the concrete implementation (see
     * withRequestTarget() below).
     *
     * If no URI is available, and no request-target has been specifically
     * provided, this method MUST return the string "/".
     *
     * @return string
     */
    public function getRequestTarget()
    {
        if ($this->requestTarget !== null && $this->requestTarget == '/')
        {
            return $this->requestTarget;
        }

        $request = $this->requestUri->getPath();
        if (!empty($this->requestUri->getQuery()))
        {
            $request .= sprintf('?%s', $this->requestUri->getQuery());
        }

        if (empty($request))
        {
            $request = '/';
        }

        return $request;
    }

    /**
     * Return an instance with the specific request-target.
     *
     * If the request needs a non-origin-form request-target — e.g., for
     * specifying an absolute-form, authority-form, or asterisk-form —
     * this method may be used to create an instance with the specified
     * request-target, verbatim.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * changed request target.
     *
     * @link http://tools.ietf.org/html/rfc7230#section-2.7 (for the various
     *     request-target forms allowed in request messages)
     * @param mixed $requestTarget
     * @return self
     */
    public function withRequestTarget($requestTarget)
    {
        $clone = clone $this;
        $clone->requestTarget = $requestTarget;
        return $clone;
    }

    /**
     * Retrieves the HTTP method of the request.
     *
     * @return string Returns the request method.
     */
    public function getMethod()
    {
        return $this->requestMethod;
    }

    /**
     * Return an instance with the provided HTTP method.
     *
     * While HTTP method names are typically all uppercase characters, HTTP
     * method names are case-sensitive and thus implementations SHOULD NOT
     * modify the given string.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * changed request method.
     *
     * @param string $method Case-sensitive method.
     * @return self
     * @throws \InvalidArgumentException for invalid HTTP methods.
     */
    public function withMethod($method)
    {
        if ($this->isValidMethod($method))
        {
            $clone = clone $this;
            $clone->requestMethod = $method;

            return $clone;
        }

        throw new \InvalidArgumentException(
            sprintf('The HTTP method: %s is not valid.', $method)
        );
    }

    /**
     * Retrieves the URI instance.
     *
     * This method MUST return a UriInterface instance.
     *
     * @link http://tools.ietf.org/html/rfc3986#section-4.3
     * @return UriInterface Returns a UriInterface instance
     *     representing the URI of the request.
     */
    public function getUri()
    {
        return $this->requestUri;
    }

    /**
     * Returns an instance with the provided URI.
     *
     * This method MUST update the Host header of the returned request by
     * default if the URI contains a host component. If the URI does not
     * contain a host component, any pre-existing Host header MUST be carried
     * over to the returned request.
     *
     * You can opt-in to preserving the original state of the Host header by
     * setting `$preserveHost` to `true`. When `$preserveHost` is set to
     * `true`, this method interacts with the Host header in the following ways:
     *
     * - If the the Host header is missing or empty, and the new URI contains
     *   a host component, this method MUST update the Host header in the returned
     *   request.
     * - If the Host header is missing or empty, and the new URI does not contain a
     *   host component, this method MUST NOT update the Host header in the returned
     *   request.
     * - If a Host header is present and non-empty, this method MUST NOT update
     *   the Host header in the returned request.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new UriInterface instance.
     *
     * @link http://tools.ietf.org/html/rfc3986#section-4.3
     * @param UriInterface $uri New request URI to use.
     * @param bool $preserveHost Preserve the original state of the Host header.
     * @return self
     */
    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        $clone = clone $this;

        if($preserveHost)
        {
            if (empty($this->requestUri->getHost()) && !empty($uri->getHost()))
            {
                $clone->requestUri = $uri;
                $clone = $clone->withHeader('Host', $uri->getHost());
                return $clone;
            }

            if (!empty($this->getHeader('Host')) || (empty($this->getHeader('Host')) && empty($uri->getHost())))
            {
                $clone->requestUri = $uri;
                return $clone;
            }
        }

        $clone->requestUri = $uri;
        $clone = $clone->withHeader('Host', $uri->getHost());

        return $clone;
    }

    /**
     * Validates an HTTP method.
     *
     * @param string $method The method to validate.
     * @return bool
     * @throws \InvalidArgumentException if given method is not a string.
     */
    public function isValidMethod($method)
    {
        if (!is_string($method))
        {
            throw new \InvalidArgumentException(
                sprintf('The HTTP method must be presented as a string. %s given.', gettype($method))
            );
        }

        return in_array(strtoupper($method), $this->supportedMethods);
    }

    /**
     * Prepares a newly created request for use.
     *
     * @param string $method The HTTP method of the request.
     * @param string|UriInterface $uri The URI of the request.
     * @param array $headers Initial headers of the request.
     * @throws \InvalidArgumentException for any invalid parameters.
     */
    protected function prepare($method, $uri, array $headers)
    {
        $this->requestMethod = $this->isValidMethod($method) ? $method : '';

        if($uri instanceof UriInterface)
        {
            $this->requestUri = $uri;
        }
        else
        {
            if(!is_string($uri))
            {
                throw new \InvalidArgumentException(
                    sprintf('Invalid URI, the URI must be a string or implementation of UriInterface. %s given.', gettype($uri))
                );
            }

            $this->requestUri = new Uri($uri);
        }

        foreach($headers as $name => $value)
        {
            if($this->verifyValidHeaderEntry($name, $value))
            {
                $this->realHeaders[$name] = $value;
            }
        }
    }
}