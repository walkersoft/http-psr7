<?php
/**
 * Part of the Fusion.Http component package.
 *
 * @author Jason L. Walker
 * @license MIT
 */


namespace Fusion\Http;

use Psr\Http\Message\UriInterface;

class Uri implements UriInterface
{

    /**
     * URI scheme; e.g. http or https
     *
     * @var string
     */
    private $uriScheme = '';

    /**
     * URI authority; e.g. example.org, foo@example.org, foo:bar@example.org:8080
     *
     * @var string
     */
    private $uriAuthority = '';

    /**
     * URI user info; e.g. foo or foo:bar
     *
     * @var string
     */
    private $uriUserInfo = '';

    /**
     * URI host; e.g. example.org
     *
     * @var string
     */
    private $uriHost = '';

    /**
     * URI port; e.g. 80, 443, etc.
     *
     * @var int
     */
    private $uriPort = null;

    /**
     * URI path; e.g. /, index.php, /route/to/resource, etc.
     *
     * @var string
     */
    private $uriPath = '';

    /**
     * URI query, segement after a '?'; e.g. foo=bar, target=user&id=55, etc.
     *
     * @var string
     */
    private $uriQuery = '';

    /**
     * URI fragment, segment after a '#'; e.g. top, title, etc.
     *
     * @var string
     */
    private $uriFragment = '';

    /**
     * Array of supported URI schemes.
     *
     * @var array
     */
    private $supportedSchemes = [
        'http' => 80,
        'https' => 443
    ];

    /**
     * Regex to identify general delimiters. Part of the URI reserved characters.
     *
     * @var string
     */
    const REGEX_GEN_DELIMITERS = ':@#\/\?\[\]';

    /**
     * Regex to identify sub-delimiters. Part of the URI reserved characters.
     *
     * @var string
     */
    const REGEX_SUB_DELIMITERS = '!&,;=\$\'\*\(\)\+';

    /**
     * Regex to identify unreserved characters.
     *
     * @var string
     */
    const REGEX_UNRESERVED = 'a-zA-Z0-9_~\-\.';

    /**
     * Regex to identify percent encoded characters.
     *
     * @var string
     */
    const REGEX_PERCENT_ENCODED = '%(?![a-fA-F0-9]{2})';

    /**
     * Constructor.
     *
     * Take a given URI and parses it.
     *
     * @param string $uri The input URI to parse.
     */
    public function __construct($uri)
    {
        if (!is_string($uri))
        {
            throw new \InvalidArgumentException(
                sprintf('Invalid URI. The URI must be a string. %s given.', gettype($uri))
            );
        }

        $this->parse($uri);
    }

    /**
     * Retrieve the scheme component of the URI.
     *
     * If no scheme is present, this method MUST return an empty string.
     *
     * The value returned MUST be normalized to lowercase, per RFC 3986
     * Section 3.1.
     *
     * The trailing ":" character is not part of the scheme and MUST NOT be
     * added.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.1
     * @return string The URI scheme.
     */
    public function getScheme()
    {
        return $this->uriScheme;
    }

    /**
     * Retrieve the authority component of the URI.
     *
     * If no authority information is present, this method MUST return an empty
     * string.
     *
     * The authority syntax of the URI is:
     *
     * <pre>
     * [user-info@]host[:port]
     * </pre>
     *
     * If the port component is not set or is the standard port for the current
     * scheme, it SHOULD NOT be included.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.2
     * @return string The URI authority, in "[user-info@]host[:port]" format.
     */
    public function getAuthority()
    {
        $host = $this->getHost();

        if (empty($host))
        {
            return '';
        }

        $userInfo = $this->getUserInfo();
        $port = $this->getPort();

        if (!empty($userInfo))
        {
            $userInfo .= "@";
        }
        if ($port !== null && $this->isStandardPort() === false)
        {
            $port = ':' . $port;
        }

        return sprintf('%s%s%s', $userInfo, $host, $port);
    }

    /**
     * Retrieve the user information component of the URI.
     *
     * If no user information is present, this method MUST return an empty
     * string.
     *
     * If a user is present in the URI, this will return that value;
     * additionally, if the password is also present, it will be appended to the
     * user value, with a colon (":") separating the values.
     *
     * The trailing "@" character is not part of the user information and MUST
     * NOT be added.
     *
     * @return string The URI user information, in "username[:password]" format.
     */
    public function getUserInfo()
    {
        return $this->uriUserInfo;
    }

    /**
     * Retrieve the host component of the URI.
     *
     * If no host is present, this method MUST return an empty string.
     *
     * The value returned MUST be normalized to lowercase, per RFC 3986
     * Section 3.2.2.
     *
     * @see http://tools.ietf.org/html/rfc3986#section-3.2.2
     * @return string The URI host.
     */
    public function getHost()
    {
        return $this->uriHost;
    }

    /**
     * Retrieve the port component of the URI.
     *
     * If a port is present, and it is non-standard for the current scheme,
     * this method MUST return it as an integer. If the port is the standard port
     * used with the current scheme, this method SHOULD return null.
     *
     * If no port is present, and no scheme is present, this method MUST return
     * a null value.
     *
     * If no port is present, but a scheme is present, this method MAY return
     * the standard port for that scheme, but SHOULD return null.
     *
     * @return null|int The URI port.
     */
    public function getPort()
    {
        if ($this->isStandardPort())
        {
            return null;
        }
        return $this->uriPort;
    }

    /**
     * Retrieve the path component of the URI.
     *
     * The path can either be empty or absolute (starting with a slash) or
     * rootless (not starting with a slash). Implementations MUST support all
     * three syntaxes.
     *
     * Normally, the empty path "" and absolute path "/" are considered equal as
     * defined in RFC 7230 Section 2.7.3. But this method MUST NOT automatically
     * do this normalization because in contexts with a trimmed base path, e.g.
     * the front controller, this difference becomes significant. It's the task
     * of the user to handle both "" and "/".
     *
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.3.
     *
     * As an example, if the value should include a slash ("/") not intended as
     * delimiter between path segments, that value MUST be passed in encoded
     * form (e.g., "%2F") to the instance.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.3
     * @return string The URI path.
     */
    public function getPath()
    {
        return $this->uriPath;
    }

    /**
     * Retrieve the query string of the URI.
     *
     * If no query string is present, this method MUST return an empty string.
     *
     * The leading "?" character is not part of the query and MUST NOT be
     * added.
     *
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.4.
     *
     * As an example, if a value in a key/value pair of the query string should
     * include an ampersand ("&") not intended as a delimiter between values,
     * that value MUST be passed in encoded form (e.g., "%26") to the instance.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.4
     * @return string The URI query string.
     */
    public function getQuery()
    {
        return $this->uriQuery;
    }

    /**
     * Retrieve the fragment component of the URI.
     *
     * If no fragment is present, this method MUST return an empty string.
     *
     * The leading "#" character is not part of the fragment and MUST NOT be
     * added.
     *
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.5.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.5
     * @return string The URI fragment.
     */
    public function getFragment()
    {
        return $this->uriFragment;
    }

    /**
     * Return an instance with the specified scheme.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified scheme.
     *
     * Implementations MUST support the schemes "http" and "https" case
     * insensitively, and MAY accommodate other schemes if required.
     *
     * An empty scheme is equivalent to removing the scheme.
     *
     * @param string $scheme The scheme to use with the new instance.
     * @return self A new instance with the specified scheme.
     * @throws \InvalidArgumentException for invalid or unsupported schemes.
     */
    public function withScheme($scheme)
    {
        if (!is_string($scheme))
        {
            throw new \InvalidArgumentException(
                sprintf('The scheme must be presented as a string. %s given.', gettype($scheme))
            );
        }

        $scheme = $this->filterScheme($scheme);

        if (!array_key_exists($scheme, $this->supportedSchemes))
        {
            throw new \InvalidArgumentException(
                sprintf('The scheme provided: %s is not supported by this implementation.', $scheme)
            );
        }

        $clone = clone $this;
        $clone->uriScheme = $scheme;

        return $clone;
    }

    /**
     * Return an instance with the specified user information.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified user information.
     *
     * Password is optional, but the user information MUST include the
     * user; an empty string for the user is equivalent to removing user
     * information.
     *
     * @param string $user The user name to use for authority.
     * @param null|string $password The password associated with $user.
     * @return self A new instance with the specified user information.
     */
    public function withUserInfo($user, $password = null)
    {
        $user = (string)$user;

        if (!empty($user) && (is_string($password) && !empty($password)))
        {
            $user .= ':' . $password;
        }

        $clone = clone $this;
        $clone->uriUserInfo = $user;
        return $clone;
    }

    /**
     * Return an instance with the specified host.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified host.
     *
     * An empty host value is equivalent to removing the host.
     *
     * @param string $host The hostname to use with the new instance.
     * @return self A new instance with the specified host.
     * @throws \InvalidArgumentException for invalid hostnames.
     */
    public function withHost($host)
    {
        if (!is_string($host))
        {
            throw new \InvalidArgumentException(
                sprintf('The host must be presented as a string. %s given', gettype($host))
            );
        }

        $clone = clone $this;
        $clone->uriHost = $host;

        return $clone;
    }

    /**
     * Return an instance with the specified port.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified port.
     *
     * Implementations MUST raise an exception for ports outside the
     * established TCP and UDP port ranges.
     *
     * A null value provided for the port is equivalent to removing the port
     * information.
     *
     * @param null|int $port The port to use with the new instance; a null value
     *     removes the port information.
     * @return self A new instance with the specified port.
     * @throws \InvalidArgumentException for invalid ports.
     */
    public function withPort($port)
    {
        if ($this->filterPort($port) === null)
        {
            $port = null;
        }

        $clone = clone $this;
        $clone->uriPort = $port;

        return $clone;
    }

    /**
     * Return an instance with the specified path.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified path.
     *
     * The path can either be empty or absolute (starting with a slash) or
     * rootless (not starting with a slash). Implementations MUST support all
     * three syntaxes.
     *
     * If the path is intended to be domain-relative rather than path relative then
     * it must begin with a slash ("/"). Paths not starting with a slash ("/")
     * are assumed to be relative to some base path known to the application or
     * consumer.
     *
     * Users can provide both encoded and decoded path characters.
     * Implementations ensure the correct encoding as outlined in getPath().
     *
     * @param string $path The path to use with the new instance.
     * @return self A new instance with the specified path.
     * @throws \InvalidArgumentException for invalid paths.
     */
    public function withPath($path)
    {
        if (!is_string($path))
        {
            throw new \InvalidArgumentException(
                sprintf('Path information must be presented as a string. %s given.', gettype($path))
            );
        }

        if (preg_match('/^[?#]+/', $path) === 1)
        {
            throw new \InvalidArgumentException(
                sprintf('The path data: %s is invalid. The path must not begin with a query (?) delimiter
                         or fragment (#) delimiter.', $path
                )
            );
        }

        $clone = clone $this;
        $clone->uriPath = $this->filterPath($path);

        return $clone;
    }

    /**
     * Return an instance with the specified query string.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified query string.
     *
     * Users can provide both encoded and decoded query characters.
     * Implementations ensure the correct encoding as outlined in getQuery().
     *
     * An empty query string value is equivalent to removing the query string.
     *
     * @param string $query The query string to use with the new instance.
     * @return self A new instance with the specified query string.
     * @throws \InvalidArgumentException for invalid query strings.
     */
    public function withQuery($query)
    {
        if (!is_string($query))
        {
            throw new \InvalidArgumentException(
                sprintf('The query must be presented as a string. %s given.', gettype($query))
            );
        }

        //Remove a leading query delimiter (?) is present
        $query = (strpos($query, '?') === 0) ? substr($query, 1) : $query;

        $clone = clone $this;
        $clone->uriQuery = $this->filterQuery($query);

        return $clone;
    }

    /**
     * Return an instance with the specified URI fragment.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified URI fragment.
     *
     * Users can provide both encoded and decoded fragment characters.
     * Implementations ensure the correct encoding as outlined in getFragment().
     *
     * An empty fragment value is equivalent to removing the fragment.
     *
     * @param string $fragment The fragment to use with the new instance.
     * @return self A new instance with the specified fragment.
     */
    public function withFragment($fragment)
    {
        if (!is_string($fragment))
        {
            throw new \InvalidArgumentException(
                sprintf('The fragment must be presented as a string. %s given.', gettype($fragment))
            );
        }

        //Remove a leading query delimiter (?) is present
        $fragment = (strpos($fragment, '#') === 0) ? substr($fragment, 1) : $fragment;

        $clone = clone $this;
        $clone->uriFragment = $this->filterFragment($fragment);

        return $clone;
    }

    /**
     * Return the string representation as a URI reference.
     *
     * Depending on which components of the URI are present, the resulting
     * string is either a full URI or relative reference according to RFC 3986,
     * Section 4.1. The method concatenates the various components of the URI,
     * using the appropriate delimiters:
     *
     * - If a scheme is present, it MUST be suffixed by ":".
     * - If an authority is present, it MUST be prefixed by "//".
     * - The path can be concatenated without delimiters. But there are two
     *   cases where the path has to be adjusted to make the URI reference
     *   valid as PHP does not allow to throw an exception in __toString():
     *     - If the path is rootless and an authority is present, the path MUST
     *       be prefixed by "/".
     *     - If the path is starting with more than one "/" and no authority is
     *       present, the starting slashes MUST be reduced to one.
     * - If a query is present, it MUST be prefixed by "?".
     * - If a fragment is present, it MUST be prefixed by "#".
     *
     * @see http://tools.ietf.org/html/rfc3986#section-4.1
     * @return string
     */
    public function __toString()
    {
        return $this->make();
    }

    /**
     * Makes a percent-encoded representation of this URI instance.
     *
     * @return string The percent-encoded URI.
     */
    public function make()
    {
        $uri = '';

        //Add scheme
        if (!empty($this->uriScheme))
        {
            $uri .= sprintf('%s:', $this->uriScheme);
        }

        //Add authority
        $authority = $this->getAuthority();
        if (!empty($authority))
        {
            $uri .= sprintf('//%s', $authority);
        }

        //Add path
        if (!empty($this->uriPath))
        {
            //Look for a slash before tacking on the path, add one if it is missing
            if (strrpos($uri, '/') !== (strlen($uri) - 1) && strpos($this->uriPath, '/') !== 0)
            {
                $uri .= '/';
            }
            $uri .= $this->uriPath;
        }

        //Add query
        if (!empty($this->uriQuery))
        {
            $uri .= sprintf('?%s', $this->uriQuery);
        }

        //Add fragment
        if (!empty($this->uriFragment))
        {
            $uri .= sprintf('#%s', $this->uriFragment);
        }

        return $uri;
    }

    /**
     * Parses the URI and populates this classes properties with the information.
     *
     * @param string $uri
     */
    private function parse($uri)
    {
        $pieces = parse_url($uri);

        if ($pieces !== false)
        {
            $this->uriScheme = (isset($pieces['scheme']))
                ? $this->filterScheme($pieces['scheme'])
                : '';
            $this->uriHost = (isset($pieces['host']))
                ? $pieces['host']
                : '';
            $this->uriPort = (isset($pieces['port']))
                ? $this->filterPort($pieces['port'])
                : null;
            $this->uriPath = (isset($pieces['path']))
                ? $this->filterPath($pieces['path'])
                : '';
            $this->uriQuery = (isset($pieces['query']))
                ? $this->filterQuery($pieces['query'])
                : '';
            $this->uriFragment = (isset($pieces['fragment']))
                ? $this->filterFragment($pieces['fragment'])
                : '';
            $this->uriUserInfo = (isset($pieces['user']))
                ? $pieces['user']
                : '';
            $this->uriUserInfo .= (isset($pieces['pass']))
                ? ':' . $pieces['pass']
                : '';
        }
    }

    /**
     * Indicates if current port is the standard port for the current scheme.
     *
     * @return bool Returns true if the current port is the standard port for the
     *      current port or false otherwise.
     */
    private function isStandardPort()
    {
        if ($this->uriPort == null || $this->getScheme() == null)
        {
            return false;
        }

        if ($this->uriPort == $this->supportedSchemes[$this->getScheme()])
        {
            return true;
        }

        return false;
    }

    /**
     * Percent-encode the query segment of the URI.
     *
     * @param string $query The query to percent-encode.
     * @return string The percent-encoded query.
     */
    private function encodeQuery($query)
    {
        $regex = '/(?:[^' . self::REGEX_UNRESERVED . self::REGEX_SUB_DELIMITERS . ':@\/\?%]+|' .
            self::REGEX_PERCENT_ENCODED . ')/';

        return preg_replace_callback($regex, [$this, 'encodeChar'], $query);
    }

    /**
     * Percent-encode a path segment based on RFC 3986.
     *
     * @param string $segment The segment to percent-encode.
     * @return string The percent-encoded segment.
     */
    private function encodeSegment($segment)
    {
        $regex = '/(?:[^' . self::REGEX_UNRESERVED . self::REGEX_SUB_DELIMITERS . ':@%]+|' .
            self::REGEX_PERCENT_ENCODED . ')/';

        return preg_replace_callback($regex, [$this, 'encodeChar'], $segment);
    }

    /**
     * Encoding callback to transform characters into percent-encoded form.
     *
     * @param array $matches An array with a matched character from the regex.
     * @return string The percent-encoded value;
     */
    private function encodeChar(array $matches)
    {
        return rawurlencode($matches[0]);
    }

    /**
     * Percent-encode the fragment segment of the URI.
     *
     * This method serves as an alias to the `self::encodeQuery()` method since
     * the same rules apply to query strings and fragments.
     *
     * @param string $fragment The fragment to percent-encode.
     * @return string The percent-encoded fragment.
     */
    private function filterFragment($fragment)
    {
        return $this->encodeQuery($fragment);
    }

    /**
     * Filters and normalizes the input scheme.
     *
     * @param string $scheme The scheme to filter.
     * @return string The filtered scheme.
     */
    private function filterScheme($scheme)
    {
        if (!empty($scheme))
        {
            $scheme = preg_replace('/:(\/\/)?/', '', $scheme);
        }

        $scheme = strtolower($scheme);

        return $scheme;
    }

    /**
     * Filters the query string and percent-encodes the contents.
     *
     * @param string $query The query to filter.
     * @return string The filtered/encoded query string.
     */
    private function filterQuery($query)
    {
        $filtered = '';

        if (!empty($query))
        {
            $query = (strpos($query, '?') === 0) ? substr($query, 1) : $query;
            $sets = explode('&', $query);
            $count = count($sets);

            if (empty($sets) || $count === 1)
            {
                $filtered = $this->encodeQuery($query);
            }
            else
            {
                $rebuilt = [];
                $i = 0;

                while ($i < $count)
                {
                    $pair = explode('=', $sets[$i], 2);
                    if (count($pair) === 1)
                    {
                        $pair[] = null;
                    }

                    array_walk($pair, [$this, 'encodeQuery']);

                    $rebuilt[] = ($pair[1] === null) ? $pair[0] : implode('=', $pair);
                    ++$i;
                }

                $filtered = implode('&', $rebuilt);
            }

        }

        return $filtered;
    }

    /**
     * Filters the path and encodes the appropriate characters.
     *
     * @param string $path The path the filter.
     * @return string The filtered string.
     */
    private function filterPath($path)
    {
        $segments = explode('/', $path);
        for ($i = 0; $i < count($segments); $i++)
        {
            $segments[$i] = $this->encodeSegment($segments[$i]);
        }

        return implode('/', $segments);
    }

    /**
     * Validates a port number.
     *
     * @param int $port The port number to filter.
     * @return int|null Returns the port or null for an invalid port
     * @throws \InvalidArgumentException When given port is not valid data.
     */
    private function filterPort($port)
    {
        if (!is_int($port))
        {
            throw new \InvalidArgumentException(
                sprintf(
                    "The port must be presented as an integer.  %s given.",
                    gettype($port)
                )
            );
        }

        $port = (int)$port;
        if ($port > 0 && $port < 65536)
        {
            return $port;
        }

        return null;
    }
}