<?php
/**
 * Part of the Fusion.Http component package.
 *
 * @author Jason L. Walker
 * @license MIT
 */

namespace Fusion\Http;

use Psr\Http\Message\ResponseInterface;

class Response extends Message implements ResponseInterface
{

    /**
     * List of standard response codes
     *
     * @see http://gif.phpnet.org/frederic/programs/http_status_codes/http_status_codes-php.txt
     * @var array
     */
    private $statusCodes = [
        100 => 'Continue',
        102 => 'Processing',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'unused',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Authorization Required',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Large',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'unused',
        419 => 'unused',
        420 => 'unused',
        421 => 'unused',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'No code',
        426 => 'Upgrade Required',
        500 => 'Internal Server Error',
        501 => 'Method Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Temporarily Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'unused',
        509 => 'unused',
        510 => 'Not Extended'
    ];

    /**
     * Current response code.
     *
     * @var int
     */
    private $statusCode;

    /**
     * Reason phrase for current response code.
     *
     * @var string
     */
    private $reasonPhrase;

    /**
     * Constructor.
     *
     * Accepts a status code and reason phrase to initialize the response with.
     * Both are optional.  If a status code is not specified that default of
     * '200 - OK' is issued.  If a status code is specified without the reason
     * phrase then a default phrase (if available) is selected instead.
     *
     * @param int $code The status code for the response
     * @param string $reasonPhrase A reason phrase.
     */
    public function __construct($code = 200, $reasonPhrase = '')
    {
        if(!$this->isValidCode($code))
        {
            throw new \InvalidArgumentException(
                sprintf('The code: %d is an invalid HTTP status code in accordance with RCF 7231', $code)
            );
        }

        $this->statusCode = $code;
        $this->reasonPhrase = $this->findReasonPhrase($this->statusCode);
    }

    /**
     * Gets the response status code.
     *
     * The status code is a 3-digit integer result code of the server's attempt
     * to understand and satisfy the request.
     *
     * @return int Status code.
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Return an instance with the specified status code and, optionally, reason phrase.
     *
     * If no reason phrase is specified, implementations MAY choose to default
     * to the RFC 7231 or IANA recommended reason phrase for the response's
     * status code.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated status and reason phrase.
     *
     * @link http://tools.ietf.org/html/rfc7231#section-6
     * @link http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     * @param int $code The 3-digit integer result code to set.
     * @param string $reasonPhrase The reason phrase to use with the
     *     provided status code; if none is provided, implementations MAY
     *     use the defaults as suggested in the HTTP specification.
     * @return self
     * @throws \InvalidArgumentException For invalid status code arguments.
     */
    public function withStatus($code, $reasonPhrase = '')
    {
        $code = intval($code);
        if (!$this->isValidCode($code))
        {
            throw new \InvalidArgumentException(
                sprintf('The code: %d is an invalid HTTP status code in accordance with RCF 7231', $code)
            );
        }

        $clone = clone $this;
        $clone->statusCode = $code;
        $clone->reasonPhrase = (is_string($reasonPhrase) && !empty($reasonPhrase))
            ? $reasonPhrase
            : $this->findReasonPhrase($code);

        return $clone;
    }

    /**
     * Gets the response reason phrase associated with the status code.
     *
     * Because a reason phrase is not a required element in a response
     * status line, the reason phrase value MAY be null. Implementations MAY
     * choose to return the default RFC 7231 recommended reason phrase (or those
     * listed in the IANA HTTP Status Code Registry) for the response's
     * status code.
     *
     * @link http://tools.ietf.org/html/rfc7231#section-6
     * @link http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     * @return string Reason phrase; must return an empty string if none present.
     */
    public function getReasonPhrase()
    {
        return $this->reasonPhrase;
    }

    /**
     * Validates a status code.
     *
     * @param int $code The code to validate.
     * @return bool
     */
    protected function isValidCode($code)
    {
        return preg_match('/^[1-5]\d{2}$/', $code) === 1 ? true : false;
    }

    /**
     * Returns a reason phrase.
     *
     * Looks up and returns a reason phrase (if available) based on the given
     * status code or an empty string if one does not exist.
     *
     * @param int $code HTTP response code to associate with a message.
     * @return string The reason phrase for a given status code.
     */
    protected function findReasonPhrase($code)
    {
        $reason = '';
        if($this->isValidCode(intval($code)))
        {
            $reason = (isset($this->statusCodes[$code]))
                ? $this->statusCodes[$code]
                : '';
        }
        return $reason;
    }
}