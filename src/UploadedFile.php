<?php
/**
 * Part of the Fusion.Http component package.
 *
 * @author Jason L. Walker
 * @license MIT
 */


namespace Fusion\Http;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

class UploadedFile implements UploadedFileInterface
{

    /**
     * Stream that represents the uploaded file.
     *
     * @var \Psr\Http\Message\StreamInterface
     */
    private $stream;

    /**
     * Flag to specify if file has been moved already.
     *
     * @var bool
     */
    private $hasMoved;

    /**
     * File size of the uploaded file.
     *
     * @var int
     */
    private $bytes;

    /**
     * Associated uploaded file's error code.
     *
     * @var int
     */
    private $error;

    /**
     * File name of the uploaded file as reported by the client.
     *
     * @var string
     */
    private $filename;

    /**
     * File media type of the uploaded file as reported by the client.
     *
     * @var string
     */
    private $mediaType;

    /**
     * Constructor.
     *
     * @param StreamInterface $stream A stream representing the uploaded file.
     * @param int $bytes The file size of the uploaded file.
     * @param int $error Error code of the uploaded file.
     * @param string|null $name File name as reported by the client.
     * @param string|null $media Media type as reported by the client.
     * @throws \InvalidArgumentException when $error is not a valid UPLOAD_ERR_* constant.
     */
    public function __construct(StreamInterface $stream, $bytes, $error, $name = null, $media = null)
    {
        if (is_int($error) && ($error >= 0 && $error <= 8))
        {
            $this->error = $error;
        }
        else
        {
            throw new \InvalidArgumentException(
                sprintf('The error code must be a valid value as defined by the UPLOAD_ERR_* constants in PHP.')
            );
        }
        $this->stream = $stream;
        $this->bytes = (is_int($bytes)) ? $bytes : null;
        $this->filename = (is_string($name)) ? $name : null;
        $this->mediaType = (is_string($media)) ? $media : null;
        $this->hasMoved = false;
    }

    /**
     * Retrieve a stream representing the uploaded file.
     *
     * This method MUST return a StreamInterface instance, representing the
     * uploaded file. The purpose of this method is to allow utilizing native PHP
     * stream functionality to manipulate the file upload, such as
     * stream_copy_to_stream() (though the result will need to be decorated in a
     * native PHP stream wrapper to work with such functions).
     *
     * If the moveTo() method has been called previously, this method MUST raise
     * an exception.
     *
     * @return StreamInterface Stream representation of the uploaded file.
     * @throws \RuntimeException in cases when no stream is available or can be
     *     created.
     */
    public function getStream()
    {
        if (!$this->stream instanceof StreamInterface || $this->hasMoved)
        {
            throw new \RuntimeException(
                'Unable to get the stream. The stream has either moved or is no longer valid.'
            );
        }

        return $this->stream;
    }

    /**
     * Move the uploaded file to a new location.
     *
     * Use this method as an alternative to move_uploaded_file(). This method is
     * guaranteed to work in both SAPI and non-SAPI environments.
     * Implementations must determine which environment they are in, and use the
     * appropriate method (move_uploaded_file(), rename(), or a stream
     * operation) to perform the operation.
     *
     * $targetPath may be an absolute path, or a relative path. If it is a
     * relative path, resolution should be the same as used by PHP's rename()
     * function.
     *
     * The original file or stream MUST be removed on completion.
     *
     * If this method is called more than once, any subsequent calls MUST raise
     * an exception.
     *
     * When used in an SAPI environment where $_FILES is populated, when writing
     * files via moveTo(), is_uploaded_file() and move_uploaded_file() SHOULD be
     * used to ensure permissions and upload status are verified correctly.
     *
     * If you wish to move to a stream, use getStream(), as SAPI operations
     * cannot guarantee writing to stream destinations.
     *
     * @see http://php.net/is_uploaded_file
     * @see http://php.net/move_uploaded_file
     * @param string $targetPath Path to which to move the uploaded file.
     * @throws \InvalidArgumentException if the $path specified is invalid.
     * @throws \RuntimeException on any error during the move operation, or on
     *     the second or subsequent call to the method.
     */
    public function moveTo($targetPath)
    {
        if (!is_string($targetPath) || empty($targetPath))
        {
            throw new \InvalidArgumentException(
                'Target path must be a non-empty string.'
            );
        }

        if ($this->hasMoved)
        {
            throw new \RuntimeException(
                'Unable to move the file. The file or stream has already been moved and is no longer valid.'
            );
        }

        //Check if in command line
        if (empty(PHP_SAPI) || $this->isCli())
        {
            $this->writeToFile($targetPath);
        }
        else
        {
            move_uploaded_file($this->getStream()->getMetadata('uri'),$targetPath);
        }

        $this->hasMoved = true;
        $this->stream = null;
    }

    /**
     * Retrieve the file size.
     *
     * Implementations SHOULD return the value stored in the "size" key of
     * the file in the $_FILES array if available, as PHP calculates this based
     * on the actual size transmitted.
     *
     * @return int|null The file size in bytes or null if unknown.
     */
    public function getSize()
    {
        return $this->bytes;
    }

    /**
     * Retrieve the error associated with the uploaded file.
     *
     * The return value MUST be one of PHP's UPLOAD_ERR_XXX constants.
     *
     * If the file was uploaded successfully, this method MUST return
     * UPLOAD_ERR_OK.
     *
     * Implementations SHOULD return the value stored in the "error" key of
     * the file in the $_FILES array.
     *
     * @see http://php.net/manual/en/features.file-upload.errors.php
     * @return int One of PHP's UPLOAD_ERR_XXX constants.
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Retrieve the filename sent by the client.
     *
     * Do not trust the value returned by this method. A client could send
     * a malicious filename with the intention to corrupt or hack your
     * application.
     *
     * Implementations SHOULD return the value stored in the "name" key of
     * the file in the $_FILES array.
     *
     * @return string|null The filename sent by the client or null if none
     *     was provided.
     */
    public function getClientFilename()
    {
        return $this->filename;
    }

    /**
     * Retrieve the media type sent by the client.
     *
     * Do not trust the value returned by this method. A client could send
     * a malicious media type with the intention to corrupt or hack your
     * application.
     *
     * Implementations SHOULD return the value stored in the "type" key of
     * the file in the $_FILES array.
     *
     * @return string|null The media type sent by the client or null if none
     *     was provided.
     */
    public function getClientMediaType()
    {
        return $this->mediaType;
    }

    /**
     * Writes a stream to a file.
     *
     * Writes the associated stream to a file.  Useful in non-SAPI environments.
     *
     * @param string $targetFile The destination file.
     * @throws \RuntimeException When the file cannot be opened.
     */
    private function writeToFile($targetFile)
    {
        if (!$file = fopen($targetFile, 'wb+'))
        {
            throw new \RuntimeException(
                sprintf('Unable to open the target file: %s', $targetFile)
            );
        }

        $this->stream->rewind();

        while (!$this->stream->eof())
        {
            fwrite($file, $this->stream->read(4096));
        }

        fclose($file);
    }

    /**
     * Determines if PHP is running in the CLI mode.
     *
     * @return bool True if PHP is in CLI mode, false otherwise.
     */
    public function isCli()
    {
        return strpos(PHP_SAPI, 'cli') === 0;
    }

    /**
     * Determines if this instance still has a valid stream.
     *
     * @return bool
     */
    public function hasValidStream()
    {
        return (!$this->stream instanceof StreamInterface || $this->hasMoved) ? false : true;
    }
}