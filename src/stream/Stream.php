<?php

namespace Arbor\stream;

use Arbor\stream\StreamInterface;
use RuntimeException;


/**
 * Stream
 *
 * Concrete implementation of StreamInterface that wraps PHP stream resources.
 * Provides a unified interface for reading, writing, and managing native PHP streams
 * such as files, network sockets, and other stream-based resources.
 *
 * @package Arbor\stream
 */
final class Stream implements StreamInterface
{
    /**
     * @var resource|null The underlying PHP stream resource.
     */
    private $resource;

    /**
     * @var bool Whether the stream is readable.
     */
    private bool $readable;

    /**
     * @var bool Whether the stream is writable.
     */
    private bool $writable;

    /**
     * @var bool Whether the stream is seekable.
     */
    private bool $seekable;


    /**
     * Constructor.
     *
     * Initializes a new Stream instance with a PHP stream resource.
     * Validates that the resource is valid and determines its capabilities
     * (readable, writable, seekable) based on the stream's metadata.
     *
     * @param resource $resource A valid PHP stream resource (e.g., from fopen(), socket_accept()).
     *
     * @throws RuntimeException If the resource is invalid or missing mode information.
     */
    public function __construct($resource)
    {
        if (!is_resource($resource)) {
            throw new RuntimeException('Invalid stream resource');
        }

        $meta = stream_get_meta_data($resource);

        if (!isset($meta['mode'])) {
            throw new RuntimeException('Invalid stream resource (no mode)');
        }

        $this->readable = strpbrk($meta['mode'], 'r+') !== false;
        $this->writable = strpbrk($meta['mode'], 'waxc+') !== false;
        $this->seekable = (bool) ($meta['seekable'] ?? false);

        $this->resource = $resource;
    }


    /**
     * Read data from the stream.
     *
     * Attempts to read up to $length bytes from the current position.
     * Returns fewer bytes if they are not available or EOF is reached.
     * Returns an empty string if $length is less than 1.
     *
     * @param int $length The maximum number of bytes to read.
     *
     * @return string The data read from the stream.
     *
     * @throws RuntimeException If the stream is detached, closed, or not readable,
     *                          or if the read operation fails.
     */
    public function read(int $length): string
    {
        $this->assertAttached();

        if ($length < 1) {
            return '';
        }

        if (!$this->isReadable()) {
            throw new RuntimeException('Stream is not readable');
        }

        $data = fread($this->resource, $length);

        if ($data === false) {
            throw new RuntimeException('Failed to read from stream');
        }

        return $data;
    }


    /**
     * Write data to the stream.
     *
     * Writes the given bytes to the stream at the current position.
     * May write fewer bytes than provided if the buffer is full or write limit is reached.
     *
     * @param string $bytes The data to write.
     *
     * @return int The number of bytes actually written.
     *
     * @throws RuntimeException If the stream is detached, closed, or not writable,
     *                          or if the write operation fails.
     */
    public function write(string $bytes): int
    {
        $this->assertAttached();

        if (!$this->isWritable()) {
            throw new RuntimeException('Stream is not writable');
        }

        $written = fwrite($this->resource, $bytes);

        if ($written === false) {
            throw new RuntimeException('Failed to write to stream');
        }

        return $written;
    }


    /**
     * Check if the stream has reached end-of-file.
     *
     * @return bool True if the stream position is at EOF, false otherwise.
     *
     * @throws RuntimeException If the stream is detached or closed.
     */
    public function eof(): bool
    {
        $this->assertAttached();

        return feof($this->resource);
    }


    /**
     * Close the stream and release its resources.
     *
     * Closes the underlying stream resource and detaches it from this instance.
     * Safe to call multiple times (idempotent).
     * After closing, the stream cannot be used.
     *
     * @return void
     */
    public function close(): void
    {
        if (is_resource($this->resource)) {
            fclose($this->resource);
        }

        $this->resource = null;
    }


    /**
     * Detach the underlying stream resource.
     *
     * Detaches the PHP stream resource from this instance and returns it.
     * After detaching, this instance can no longer be used, but the underlying
     * resource remains open and can be used directly with PHP stream functions.
     *
     * @return resource|null The underlying stream resource, or null if already detached.
     */
    public function detach()
    {
        $resource = $this->resource;
        $this->resource = null;

        return $resource;
    }


    /**
     * Check if the stream is readable.
     *
     * Determined during construction based on the stream's open mode.
     *
     * @return bool True if the stream can be read from, false otherwise.
     */
    public function isReadable(): bool
    {
        return $this->readable;
    }

    /**
     * Check if the stream is writable.
     *
     * Determined during construction based on the stream's open mode.
     *
     * @return bool True if the stream can be written to, false otherwise.
     */
    public function isWritable(): bool
    {
        return $this->writable;
    }

    /**
     * Check if the stream is seekable.
     *
     * Determined during construction based on the stream's metadata.
     *
     * @return bool True if the stream supports seeking, false otherwise.
     */
    public function isSeekable(): bool
    {
        return $this->seekable;
    }


    /**
     * Get the current position in the stream.
     *
     * Returns the current byte offset within the stream.
     * For non-seekable streams, this may not be meaningful.
     *
     * @return int|false The current position in bytes, or false if the position cannot be determined.
     *
     * @throws RuntimeException If the stream is detached or closed.
     */
    public function tell(): ?int
    {
        $this->assertAttached();
        return ftell($this->resource);
    }


    /**
     * Rewind the stream to the beginning.
     *
     * Resets the stream position to the start (byte 0).
     * Only available for seekable streams.
     *
     * @return void
     *
     * @throws RuntimeException If the stream is detached, closed, or not seekable.
     */
    public function rewind(): void
    {
        $this->assertAttached();

        if (!$this->isSeekable()) {
            throw new RuntimeException('Stream is not seekable');
        }

        rewind($this->resource);
    }


    /**
     * Assert that the stream resource is still attached and valid.
     *
     * @return void
     *
     * @throws RuntimeException If the stream has been detached or closed.
     */
    private function assertAttached(): void
    {
        if (!is_resource($this->resource)) {
            throw new RuntimeException('Stream is detached or closed');
        }
    }
}
