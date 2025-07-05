<?php

namespace Arbor\http\components;

use Arbor\http\components\Stream;

/**
 * Class UploadedFile
 * 
 * Represents a file uploaded through an HTTP request.
 * This implementation handles both file paths and stream representations.
 * 
 * @package Arbor\http\components
 */
class UploadedFile
{
    /**
     * Path to the uploaded file on the server.
     * 
     * @var string|null
     */
    private ?string $file = null;

    /**
     * Stream instance representing the uploaded file's contents.
     * 
     * @var Stream|null
     */
    private ?Stream $stream = null;

    /**
     * Size of the uploaded file in bytes.
     * 
     * @var int|null
     */
    private ?int $size;

    /**
     * Error code associated with the file upload.
     * Uses PHP's UPLOAD_ERR_* constants.
     * 
     * @var int
     */
    private int $error;

    /**
     * Original filename as provided by the client.
     * 
     * @var string|null
     */
    private ?string $clientFilename;

    /**
     * MIME type of the file as provided by the client.
     * 
     * @var string|null
     */
    private ?string $clientMediaType;

    /**
     * Flag indicating whether the file has been moved from its original location.
     * 
     * @var bool
     */
    private bool $moved = false;

    /**
     * Creates a new UploadedFile instance.
     *
     * @param string|Stream $fileOrStream The filename or stream representing the uploaded file
     * @param int|null $size The file size in bytes
     * @param int $error The error status (one of PHP's UPLOAD_ERR_* constants)
     * @param string|null $clientFilename The original filename as provided by the client
     * @param string|null $clientMediaType The MIME type as provided by the client
     * 
     * @throws \InvalidArgumentException If the provided file or stream is not valid
     */
    public function __construct(
        string|Stream $fileOrStream,
        ?int $size,
        int $error = UPLOAD_ERR_OK,
        ?string $clientFilename = null,
        ?string $clientMediaType = null
    ) {
        if (is_string($fileOrStream)) {
            $this->file = $fileOrStream;
        } elseif ($fileOrStream instanceof Stream) {
            $this->stream = $fileOrStream;
        } else {
            throw new \InvalidArgumentException('Invalid file or stream provided');
        }

        $this->size = $size;
        $this->error = $error;
        $this->clientFilename = $clientFilename;
        $this->clientMediaType = $clientMediaType;
    }

    /**
     * Retrieve a stream representing the uploaded file.
     * 
     * Creates a stream from the file path if only a file path is available.
     * Returns the existing stream if already created.
     *
     * @return Stream Stream representation of the uploaded file
     * 
     * @throws \RuntimeException If the upload had an error, the file was already moved,
     *                          or the file cannot be opened for reading
     */
    public function getStream(): Stream
    {
        if ($this->error !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Cannot retrieve stream due to upload error');
        }

        if ($this->moved) {
            throw new \RuntimeException('Cannot retrieve stream after it has been moved');
        }

        if ($this->stream instanceof Stream) {
            return $this->stream;
        }

        $resource = fopen($this->file, 'r');
        if ($resource === false) {
            throw new \RuntimeException('Could not open file for reading');
        }

        $this->stream = new Stream($resource);
        return $this->stream;
    }

    /**
     * Move the uploaded file to a new location.
     * 
     * For actual uploaded files, uses move_uploaded_file() for secure handling.
     * For streams, copies the stream contents to the new location.
     *
     * @param string $targetPath Path to which to move the uploaded file
     * 
     * @throws \InvalidArgumentException If the target path is invalid
     * @throws \RuntimeException On any error during the move operation, including
     *                          if the file was already moved or had an upload error
     */
    public function moveTo(string $targetPath): void
    {
        if ($this->error !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Cannot move file due to upload error');
        }

        if ($this->moved) {
            throw new \RuntimeException('Cannot move file more than once');
        }

        if (empty($targetPath)) {
            throw new \InvalidArgumentException('Invalid target path provided');
        }

        $targetDirectory = dirname($targetPath);
        if (!is_dir($targetDirectory) || !is_writable($targetDirectory)) {
            throw new \RuntimeException('Target directory is not writable or does not exist');
        }

        if ($this->file) {
            $success = move_uploaded_file($this->file, $targetPath);
            if (!$success) {
                throw new \RuntimeException('Failed to move uploaded file');
            }
        } elseif ($this->stream) {
            $handle = fopen($targetPath, 'w');
            if ($handle === false) {
                throw new \RuntimeException('Unable to open target file for writing');
            }

            $this->stream->rewind();
            while (!$this->stream->eof()) {
                fwrite($handle, $this->stream->read(4096));
            }
            fclose($handle);
        }

        $this->moved = true;
    }

    /**
     * Retrieve the file size.
     *
     * @return int|null The file size in bytes or null if unknown
     */
    public function getSize(): ?int
    {
        return $this->size;
    }

    /**
     * Retrieve the error associated with the uploaded file.
     * 
     * @return int One of PHP's UPLOAD_ERR_XXX constants
     * @see https://www.php.net/manual/en/features.file-upload.errors.php
     */
    public function getError(): int
    {
        return $this->error;
    }

    /**
     * Retrieve the original filename sent by the client.
     *
     * @return string|null The filename sent by the client or null if none was provided
     */
    public function getClientFilename(): ?string
    {
        return $this->clientFilename;
    }

    /**
     * Retrieve the media type sent by the client.
     *
     * @return string|null The media type sent by the client or null if none was provided
     */
    public function getClientMediaType(): ?string
    {
        return $this->clientMediaType;
    }

    /**
     * Retrieve the file extension based on the client's original filename.
     *
     * @return string|null The file extension (without dot), or null if unavailable
     */
    public function getClientExtension(): ?string
    {
        if (!$this->clientFilename) {
            return null;
        }

        $extension = pathinfo($this->clientFilename, PATHINFO_EXTENSION);
        return $extension !== '' ? strtolower($extension) : null;
    }


    /**
     * Set or override the client-provided filename.
     *
     * @param string $newFilename
     * @return void
     */
    public function setClientFilename(string $newFilename): void
    {
        $this->clientFilename = $newFilename;
    }


    public function isMoved(): bool
    {
        return $this->moved;
    }
}
