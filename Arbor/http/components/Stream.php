<?php

namespace Arbor\http\components;

use Exception;
use Throwable;

/**
 * Stream class for handling PHP stream resources
 * 
 * This class provides a wrapper around PHP stream resources with methods
 * for reading, writing, and manipulating stream data safely and efficiently.
 * 
 * @package Arbor\http\components
 */
class Stream
{
    /**
     * The underlying PHP stream resource
     * 
     * @var resource|null
     */
    private $stream;

    /**
     * Stream metadata
     * 
     * @var array<string, mixed>|null
     */
    private $meta;

    /**
     * Whether the stream is seekable
     * 
     * @var bool
     */
    private bool $seekable = false;

    /**
     * Whether the stream is readable
     * 
     * @var bool
     */
    private bool $readable = false;

    /**
     * Whether the stream is writable
     * 
     * @var bool
     */
    private bool $writable = false;

    /**
     * The size of the stream in bytes
     * 
     * @var int|null
     */
    private ?int $size = null;

    /**
     * Creates a new stream instance
     *
     * @param resource|string|Stream $body Stream resource, string content, or another Stream object
     * 
     * @throws Exception If an invalid stream resource is provided
     */
    public function __construct($body = '')
    {
        if (is_string($body)) {
            $resource = fopen('php://temp', 'rw+');
            if ($resource === false) {
                throw new Exception('Failed to create temporary stream');
            }
            fwrite($resource, $body);
            rewind($resource);
            $body = $resource;
        } elseif ($body instanceof Stream) {
            $body = $body->detach();
        }

        if (!is_resource($body)) {
            throw new Exception('Invalid stream resource');
        }

        $this->stream = $body;
        $this->initMetadata();
    }

    /**
     * Initialize stream metadata and flags
     * 
     * @return void
     */
    private function initMetadata(): void
    {
        $this->meta = stream_get_meta_data($this->stream);
        $mode = $this->meta['mode'] ?? '';

        $this->seekable = $this->meta['seekable'] ?? false;
        $this->readable = strpbrk($mode, 'r+') !== false;
        $this->writable = strpbrk($mode, 'waxc+') !== false;
    }

    /**
     * Converts the stream to a string
     * 
     * @return string The string representation of the stream
     */
    public function __toString(): string
    {
        if (!$this->isReadable() || !$this->isSeekable()) {
            return '';
        }

        try {
            $this->rewind();
            return $this->getContents();
        } catch (Throwable $e) {
            return '';
        }
    }

    /**
     * Closes the stream and any underlying resources
     * 
     * @return void
     */
    public function close(): void
    {
        if ($this->stream && is_resource($this->stream)) {
            fclose($this->stream);
        }
        $this->detach();
    }

    /**
     * Detaches the underlying stream resource
     * 
     * @return resource|null The underlying stream resource or null if already detached
     */
    public function detach()
    {
        $result = $this->stream;
        $this->stream = null;
        $this->meta = null;
        $this->seekable = false;
        $this->readable = false;
        $this->writable = false;
        $this->size = null;
        return $result;
    }

    /**
     * Gets the size of the stream
     * 
     * @return int|null Returns the size in bytes, or null if unknown
     */
    public function getSize(): ?int
    {
        if ($this->size !== null) {
            return $this->size;
        }

        if (!isset($this->stream)) {
            return null;
        }

        $stats = fstat($this->stream);
        return $this->size = $stats['size'] ?? null;
    }

    /**
     * Returns the current position of the stream pointer
     * 
     * @return int Current position of the stream pointer
     * @throws Exception If unable to determine the position
     */
    public function tell(): int
    {
        $this->ensureStream();
        $pos = ftell($this->stream);
        if ($pos === false) {
            throw new Exception('Unable to get stream position');
        }
        return $pos;
    }

    /**
     * Returns whether the stream is at the end-of-file
     * 
     * @return bool True if at the end, false otherwise
     */
    public function eof(): bool
    {
        return !$this->stream || feof($this->stream);
    }

    /**
     * Returns whether the stream is seekable
     * 
     * @return bool True if seekable, false otherwise
     */
    public function isSeekable(): bool
    {
        return $this->seekable;
    }

    /**
     * Seeks to a position in the stream
     * 
     * @param int $offset Stream offset
     * @param int $whence One of SEEK_SET, SEEK_CUR, or SEEK_END
     * 
     * @return void
     * @throws Exception If seek operation fails
     */
    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        $this->ensureStream();
        if (!$this->seekable || fseek($this->stream, $offset, $whence) === -1) {
            throw new Exception('Unable to seek in stream');
        }
    }

    /**
     * Rewinds the stream to the beginning
     * 
     * @return void
     * @throws Exception If unable to rewind the stream
     */
    public function rewind(): void
    {
        $this->seek(0);
    }

    /**
     * Returns whether the stream is writable
     * 
     * @return bool True if writable, false otherwise
     */
    public function isWritable(): bool
    {
        return $this->writable;
    }

    /**
     * Writes data to the stream
     * 
     * @param string $string The data to write
     * 
     * @return int The number of bytes written
     * @throws Exception If unable to write to the stream
     */
    public function write(string $string): int
    {
        $this->ensureWritable();

        $this->size = null;
        $written = fwrite($this->stream, $string);

        if ($written === false) {
            throw new Exception('Unable to write to stream');
        }

        return $written;
    }

    /**
     * Returns whether the stream is readable
     * 
     * @return bool True if readable, false otherwise
     */
    public function isReadable(): bool
    {
        return $this->readable;
    }

    /**
     * Reads a specified number of bytes from the stream
     * 
     * @param int $length Number of bytes to read
     * 
     * @return string The data read from the stream
     * @throws Exception If unable to read from the stream
     */
    public function read(int $length): string
    {
        $this->ensureReadable();

        if ($length < 0) {
            throw new Exception('Length must be non-negative');
        }

        if ($length === 0) {
            return '';
        }

        $data = fread($this->stream, $length);
        if ($data === false) {
            throw new Exception('Unable to read from stream');
        }

        return $data;
    }

    /**
     * Returns the remaining contents of the stream as a string
     * 
     * @return string The remaining contents of the stream
     * @throws Exception If unable to read the stream contents
     */
    public function getContents(): string
    {
        $this->ensureReadable();

        $contents = stream_get_contents($this->stream);
        if ($contents === false) {
            throw new Exception('Unable to get stream contents');
        }

        return $contents;
    }

    /**
     * Gets metadata from the stream
     * 
     * @param string|null $key Specific metadata key to retrieve
     * 
     * @return array<string, mixed>|mixed|null The requested metadata or null if not available
     */
    public function getMetadata(?string $key = null)
    {
        if (!$this->stream) {
            return $key ? null : [];
        }

        if ($key === null) {
            return $this->meta;
        }

        return $this->meta[$key] ?? null;
    }

    /**
     * Ensures the stream is attached
     * 
     * @return void
     * @throws Exception If the stream is detached
     */
    private function ensureStream(): void
    {
        if (!isset($this->stream)) {
            throw new Exception('Stream is detached');
        }
    }

    /**
     * Ensures the stream is writable
     * 
     * @return void
     * @throws Exception If the stream is not writable
     */
    private function ensureWritable(): void
    {
        $this->ensureStream();

        if (!$this->writable) {
            throw new Exception('Stream is not writable');
        }
    }

    /**
     * Ensures the stream is readable
     * 
     * @return void
     * @throws Exception If the stream is not readable
     */
    private function ensureReadable(): void
    {
        $this->ensureStream();

        if (!$this->readable) {
            throw new Exception('Stream is not readable');
        }
    }
}
