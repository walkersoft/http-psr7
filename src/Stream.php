<?php
/**
 * Part of the Fusion.Http component package.
 *
 * @author Jason L. Walker
 * @license MIT
 */

namespace Fusion\Http;

use Psr\Http\Message\StreamInterface;

class Stream implements StreamInterface
{

    /**
     * Holds the stream resource being wrapped.
     *
     * @var mixed
     */
    private $stream;

    /**
     * Metadata about the streams as captured when constructed.
     *
     * @var array
     */
    private $metadata;

    /**
     * Constructor.
     *
     * Default behavior is to work on resource types of `stream`. Other desired
     * stream types should extend this class and override the necessary methods
     * or implement the StreamInterface in its entirety.
     *
     * @param resource $stream The resource stream to wrap.
     * @throws \InvalidArgumentException When given stream is not a resource or
     *     a resource type of `stream`.
     */
    public function __construct($stream)
    {
        //Check if the a resource was given
        if (!is_resource($stream))
        {
            throw new \InvalidArgumentException(
                sprintf('A resource value is expected to construct a stream. %s given.', gettype($stream))
            );
        }

        //This resource implementation must be a stream
        if (get_resource_type($stream) != 'stream')
        {
            throw new \InvalidArgumentException(
                sprintf('The resource type must be a %s. Resource of %s given.',
                        'stream',
                        get_resource_type($stream)
                )
            );
        }

        $this->stream = $stream;
        $this->metadata = stream_get_meta_data($this->stream);
    }

    /**
     * Reads all data from the stream into a string, from the beginning to end.
     *
     * This method MUST attempt to seek to the beginning of the stream before
     * reading data and read the stream until the end is reached.
     *
     * Warning: This could attempt to load a large amount of data into memory.
     *
     * This method MUST NOT raise an exception in order to conform with PHP's
     * string casting operations.
     *
     * @see http://php.net/manual/en/language.oop5.magic.php#object.tostring
     * @return string
     */
    public function __toString()
    {
        // Suppress an exception if output can't be gathered through normal means.
        try
        {
            $this->rewind();
            $output = $this->getContents();
        }
        catch (\RuntimeException $e)
        {
            $output = $e->getMessage();
        }

        return $output;
    }

    /**
     * Closes the stream and any underlying resources.
     *
     * @return void
     */
    public function close()
    {
        if (is_resource($this->stream))
        {
            fclose($this->stream);
            $this->metadata = [];
        }
    }

    /**
     * Separates any underlying resources from the stream.
     *
     * After the stream has been detached, the stream is in an unusable state.
     *
     * @return resource|null Underlying PHP stream, if any
     */
    public function detach()
    {
        $stream = $this->stream;
        $this->stream = null;
        $this->metadata = [];
        return $stream;
    }

    /**
     * Get the size of the stream if known.
     *
     * @return int|null Returns the size in bytes if known, or null if unknown.
     */
    public function getSize()
    {
        $size = null;
        if (is_resource($this->stream))
        {
            $stats = fstat($this->stream);

            if ($stats !== false)
            {
                $size = $stats['size'];
            }
        }

        return $size;
    }

    /**
     * Returns the current position of the file read/write pointer
     *
     * @return int Position of the file pointer
     * @throws \RuntimeException on error.
     */
    public function tell()
    {
        if (is_resource($this->stream))
        {
            return ftell($this->stream);
        }

        throw new \RuntimeException('The underlying resource is missing or not a valid stream.');
    }

    /**
     * Returns true if the stream is at the end of the stream.
     *
     * @return bool
     */
    public function eof()
    {
        return feof($this->stream);
    }

    /**
     * Returns whether or not the stream is seekable.
     *
     * @return bool
     */
    public function isSeekable()
    {
        if (is_resource($this->stream))
        {
            return $this->getMetadata('seekable');
        }

        return false;
    }

    /**
     * Seek to a position in the stream.
     *
     * @link http://www.php.net/manual/en/function.fseek.php
     * @param int $offset Stream offset
     * @param int $whence Specifies how the cursor position will be calculated
     *     based on the seek offset. Valid values are identical to the built-in
     *     PHP $whence values for `fseek()`.  SEEK_SET: Set position equal to
     *     offset bytes SEEK_CUR: Set position to current location plus offset
     *     SEEK_END: Set position to end-of-stream plus offset.
     * @throws \RuntimeException on failure.
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        if (is_resource($this->stream))
        {
            if (!$this->getMetadata('seekable'))
            {
                throw new \RuntimeException(
                    sprintf('Unable to seek. The stream is not seekable')
                );
            }

            fseek($this->stream, $offset, $whence);
        }
    }

    /**
     * Seek to the beginning of the stream.
     *
     * If the stream is not seekable, this method will raise an exception;
     * otherwise, it will perform a seek(0).
     *
     * @see seek()
     * @link http://www.php.net/manual/en/function.fseek.php
     * @throws \RuntimeException on failure.
     */
    public function rewind()
    {
        if (!$this->isSeekable())
        {
            throw new \RuntimeException(
                sprintf('Unable to rewind. The stream is not seekable.')
            );
        }

        $this->seek(0);
    }

    /**
     * Returns whether or not the stream is writable.
     *
     * @return bool
     */
    public function isWritable()
    {
        if (!is_resource($this->stream))
        {
            return false;
        }

        return (preg_match('/[w+]/', $this->getMetadata('mode')) === 1)
            ? true
            : false;
    }

    /**
     * Write data to the stream.
     *
     * @param string $string The string that is to be written.
     * @return int Returns the number of bytes written to the stream.
     * @throws \RuntimeException on failure.
     */
    public function write($string)
    {
        if (!is_resource($this->stream))
        {
            throw new \RuntimeException(
                sprintf('Unable to write to stream. The stream is not a valid resource.')
            );
        }

        if (!$this->isWritable())
        {
            throw new \RuntimeException(
                sprintf('Unable to write to stream. Writing is not available for this stream.')
            );
        }

        return fwrite($this->stream, $string);
    }

    /**
     * Returns whether or not the stream is readable.
     *
     * @return bool
     */
    public function isReadable()
    {
        if (!is_resource($this->stream))
        {
            return false;
        }

        return (preg_match('/[r+]/', $this->getMetadata('mode')) === 1)
            ? true
            : false;
    }

    /**
     * Read data from the stream.
     *
     * @param int $length Read up to $length bytes from the object and return
     *     them. Fewer than $length bytes may be returned if underlying stream
     *     call returns fewer bytes.
     * @return string Returns the data read from the stream, or an empty string
     *     if no bytes are available.
     * @throws \RuntimeException if an error occurs.
     */
    public function read($length)
    {
        if (!is_resource($this->stream))
        {
            throw new \RuntimeException(
                sprintf('Unable to read from stream. The stream is not a valid resource.')
            );
        }

        if (!$this->isReadable())
        {
            throw new \RuntimeException(
                sprintf('Unable to read from stream. The stream is not readable.')
            );
        }

        return fgets($this->stream, $length);
    }

    /**
     * Returns the remaining contents in a string
     *
     * @return string
     * @throws \RuntimeException if unable to read or an error occurs while
     *     reading.
     */
    public function getContents()
    {
        if (!is_resource($this->stream))
        {
            throw new \RuntimeException(
                sprintf('Unable to get stream contents. The stream is not a valid resource.')
            );
        }

        if (!$this->isReadable())
        {
            throw new \RuntimeException(
                sprintf('Unable to get stream contents. The stream is not readable.')
            );
        }

        $contents = '';

        while (($buffer = $this->read(4096)) !== false)
        {
            $contents .= $buffer;
        }

        return $contents;
    }

    /**
     * Get stream metadata as an associative array or retrieve a specific key.
     *
     * The keys returned are identical to the keys returned from PHP's
     * stream_get_meta_data() function.
     *
     * @link http://php.net/manual/en/function.stream-get-meta-data.php
     * @param string $key Specific metadata to retrieve.
     * @return array|mixed|null Returns an associative array if no key is
     *     provided. Returns a specific key value if a key is provided and the
     *     value is found, or null if the key is not found.
     */
    public function getMetadata($key = null)
    {
        if ($key === null)
        {
            return $this->metadata;
        }

        if (array_key_exists($key, $this->metadata))
        {
            return $this->metadata[$key];
        }

        return null;
    }
}