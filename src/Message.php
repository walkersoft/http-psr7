<?php
/**
 * Part of the Fusion.Http component package.
 *
 * @author Jason L. Walker
 * @license MIT
 */

namespace Fusion\Http;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

/**
 * HTTP Message class based on PSR-7 standards.
 */
class Message implements MessageInterface
{

    /**
     * HTTP protocol version.
     *
     * @var string
     */
    protected $httpVersion = '1.1';

    /**
     * Array of real message headers, i.e. As they were given.
     *
     * @var array
     */
    protected $realHeaders = [];

    /**
     * Array of normalized headers for lookup purposes.
     *
     * @var array
     */
    protected $headers = [];

    /**
     * HTTP body content as a stream.
     *
     * @var \Psr\Http\Message\StreamInterface
     */
    protected $httpBody = null;

    /**
     * Retrieves the HTTP protocol version as a string.
     *
     * The string MUST contain only the HTTP version number (e.g., "1.1", "1.0").
     *
     * @return string HTTP protocol version.
     */
    public function getProtocolVersion()
    {
        return $this->httpVersion;
    }

    /**
     * Return an instance with the specified HTTP protocol version.
     *
     * The version string MUST contain only the HTTP version number (e.g.,
     * "1.1", "1.0").
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new protocol version.
     *
     * @param string $version HTTP protocol version
     * @return self
     */
    public function withProtocolVersion($version)
    {
        $clone = clone $this;
        $clone->httpVersion = (string)$version;
        return $clone;
    }

    /**
     * Retrieves all message header values.
     *
     * The keys represent the header name as it will be sent over the wire, and
     * each value is an array of strings associated with the header.
     *
     *     // Represent the headers as a string
     *     foreach ($message->getHeaders() as $name => $values) {
     *         echo $name . ": " . implode(", ", $values);
     *     }
     *
     *     // Emit headers iteratively:
     *     foreach ($message->getHeaders() as $name => $values) {
     *         foreach ($values as $value) {
     *             header(sprintf('%s: %s', $name, $value), false);
     *         }
     *     }
     *
     * While header names are not case-sensitive, getHeaders() will preserve the
     * exact case in which headers were originally specified.
     *
     * @return array Returns an associative array of the message's headers. Each
     *     key MUST be a header name, and each value MUST be an array of strings
     *     for that header.
     */
    public function getHeaders()
    {
        return $this->realHeaders;
    }

    /**
     * Checks if a header exists by the given case-insensitive name.
     *
     * @param string $name Case-insensitive header field name.
     * @return bool Returns true if any header names match the given header
     *     name using a case-insensitive string comparison. Returns false if
     *     no matching header name is found in the message.
     */
    public function hasHeader($name)
    {
        //return isset($this->headers[strtolower($name)]);
        return array_key_exists(strtolower($name), $this->headers);
    }

    /**
     * Retrieves a message header value by the given case-insensitive name.
     *
     * This method returns an array of all the header values of the given
     * case-insensitive header name.
     *
     * If the header does not appear in the message, this method MUST return an
     * empty array.
     *
     * @param string $name Case-insensitive header field name.
     * @return string[] An array of string values as provided for the given
     *    header. If the header does not appear in the message, this method MUST
     *    return an empty array.
     */
    public function getHeader($name)
    {
        $header = [];
        if ($this->hasHeader($name))
        {
            $header = $this->realHeaders[$this->headers[strtolower($name)]];
            if(!is_array($header))
            {
                $header = [$header];
            }
        }
        return $header;
    }

    /**
     * Retrieves a comma-separated string of the values for a single header.
     *
     * This method returns all of the header values of the given
     * case-insensitive header name as a string concatenated together using
     * a comma.
     *
     * NOTE: Not all header values may be appropriately represented using
     * comma concatenation. For such headers, use getHeader() instead
     * and supply your own delimiter when concatenating.
     *
     * If the header does not appear in the message, this method MUST return
     * an empty string.
     *
     * @param string $name Case-insensitive header field name.
     * @return string A string of values as provided for the given header
     *    concatenated together using a comma. If the header does not appear in
     *    the message, this method MUST return an empty string.
     */
    public function getHeaderLine($name)
    {
        if ($this->hasHeader($name) && is_array($this->realHeaders[$this->headers[strtolower($name)]]))
        {
            return implode(',', $this->realHeaders[$this->headers[strtolower($name)]]);
        }
        return '';
    }

    /**
     * Return an instance with the provided value replacing the specified header.
     *
     * While header names are case-insensitive, the casing of the header will
     * be preserved by this function, and returned from getHeaders().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new and/or updated header and value.
     *
     * @param string $name Case-insensitive header field name.
     * @param string|string[] $value Header value(s).
     * @return self
     * @throws \InvalidArgumentException for invalid header names or values.
     */
    public function withHeader($name, $value)
    {
        //Check the data
        if (!$this->verifyValidHeaderEntry($name, $value))
        {
            $message = "HTTP header MUST be a string - %s given. ";
            $message .= "HTTP header values MUST be a string or an array of strings - %s given.";
            $message = sprintf($message, gettype($name), gettype($value));
            throw new \InvalidArgumentException($message);
        }

        //Integrity checks on the data passed, add the header to the mix.
        if (is_string($value))
        {
            $value = [$value];
        }

        $clone = clone $this;
        $clone->realHeaders[$name] = $value;
        $clone->headers[strtolower($name)] = $name;
        return $clone;
    }

    /**
     * Return an instance with the specified header appended with the given value.
     *
     * Existing values for the specified header will be maintained. The new
     * value(s) will be appended to the existing list. If the header did not
     * exist previously, it will be added.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new header and/or value.
     *
     * @param string $name Case-insensitive header field name to add.
     * @param string|string[] $value Header value(s).
     * @return self
     * @throws \InvalidArgumentException for invalid header names or values.
     */
    public function withAddedHeader($name, $value)
    {
        //Check the data
        if (!$this->verifyValidHeaderEntry($name, $value))
        {
            $message = "HTTP header MUST be a string - %s given. ";
            $message .= "HTTP header values MUST be a string or an array of strings - %s given.";
            $message = sprintf($message, gettype($name), gettype($value));
            throw new \InvalidArgumentException($message);
        }

        //See if the header exists, if not create it.
        if (!isset($this->headers[strtolower($name)]))
        {
            return $this->withHeader($name, $value);
        }

        //The values are good, merge the values in with the current set.
        $clone = clone $this;
        $clone->realHeaders[$name] = array_merge($clone->realHeaders[$name], $value);
        $clone->headers[strtolower($name)] = $name;
        return $clone;
    }

    /**
     * Return an instance without the specified header.
     *
     * Header resolution MUST be done without case-sensitivity.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that removes
     * the named header.
     *
     * @param string $name Case-insensitive header field name to remove.
     * @return self
     */
    public function withoutHeader($name)
    {
        if (!$this->hasHeader($name))
        {
            return $this;
        }
        $clone = clone $this;
        unset($clone->realHeaders[$name]);
        unset($clone->headers[strtolower($name)]);
        return $clone;
    }

    /**
     * Gets the body of the message.
     *
     * @return StreamInterface Returns the body as a stream.
     */
    public function getBody()
    {
        return $this->httpBody;
    }

    /**
     * Return an instance with the specified message body.
     *
     * The body MUST be a StreamInterface object.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * new body stream.
     *
     * @param StreamInterface $body Body.
     * @return self
     * @throws \InvalidArgumentException When the body is not valid.
     */
    public function withBody(StreamInterface $body)
    {
        $clone = clone $this;
        $clone->httpBody = $body;
        return $clone;
    }

    /**
     * Checks if an array contains only strings.
     *
     * Scans an array to confirm that all elements contains only strings.
     * Returns true if only strings exists or false otherwise.
     *
     * @param array $input The input array to verify.
     * @return bool
     */
    protected function verifyStringOnlyArray(array $input)
    {
        foreach ($input as $element)
        {
            if (!is_string($element))
            {
                return false;
            }
        }
        return true;
    }

    /**
     * Checks if a header name is valid.
     *
     * Header values MUST be presented as a string.  Returns true if the
     * header name is valid or false otherwise.
     *
     * @param string $name The header name to check.
     * @return bool
     */
    protected function verifyValidHeaderName($name)
    {
        return is_string($name);
    }

    /**
     * Checks if a header value is valid.
     *
     * Header values MUST presented as a string or an array of strings. Returns
     * true if the header value is valid or false otherwise.
     *
     * @param string $value The header value to verify.
     * @return bool
     */
    protected function verifyValidHeaderValue($value)
    {
        if (!is_string($value) && !is_array($value))
        {
            return false;
        }
        if (is_array($value) && !$this->verifyStringOnlyArray($value))
        {
            return false;
        }
        return true;
    }

    /**
     * Helper function to check valid header and value in one go.
     *
     * Encapsulates the verifyValidHeaderName() and verifyValidHeaderValue()
     * into a single function call.  Returns true if both pieces of information
     * are valid or false otherwise.
     *
     * @param string $name The header name to verify.
     * @param string|string[] $value The header value(s) to verify.
     * @return bool
     */
    protected function verifyValidHeaderEntry($name, $value)
    {
        return ($this->verifyValidHeaderName($name) && $this->verifyValidHeaderValue($value));
    }
}