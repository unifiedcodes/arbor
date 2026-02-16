<?php

namespace Arbor\stream;

use RuntimeException;

/**
 * StreamInterface
 *
 * Defines the contract for working with stream resources in a unified manner.
 * Streams represent sequences of bytes that can be read from, written to, or both.
 * This interface provides methods for reading, writing, seeking, and managing the lifecycle
 * of stream resources such as files, network connections, or memory buffers.
 *
 * @package Arbor\stream
 */
interface StreamInterface
{
    /**
     * Read data from the stream.
     *
     * Reads up to $length bytes from the current position in the stream.
     * If fewer bytes are available than requested, returns what is available.
     * Returns an empty string if EOF is reached.
     *
     * @param int $length The maximum number of bytes to read. Must be greater than 0.
     *
     * @return string The data read from the stream. Returns empty string if no data is available.
     *
     * @throws RuntimeException If the stream is not readable or an error occurs during reading.
     */
    public function read(int $length): string;

    /**
     * Determine if the stream has reached end-of-file.
     *
     * @return bool True if the stream position is at EOF, false otherwise.
     */
    public function eof(): bool;

    /**
     * Write data to the stream.
     *
     * Writes the given bytes to the current position in the stream.
     * If the stream is not writable, should throw an exception.
     *
     * @param string $bytes The data to write to the stream.
     *
     * @return int The number of bytes written. May be less than the length of $bytes if the stream
     *             could not write all data at once.
     *
     * @throws RuntimeException If the stream is not writable or an error occurs during writing.
     */
    public function write(string $bytes): int;

    /**
     * Check if the stream is readable.
     *
     * @return bool True if the stream can be read from, false otherwise.
     */
    public function isReadable(): bool;

    /**
     * Check if the stream is writable.
     *
     * @return bool True if the stream can be written to, false otherwise.
     */
    public function isWritable(): bool;

    /**
     * Check if the stream is seekable.
     *
     * Seekable streams allow the position to be changed via the rewind() method.
     *
     * @return bool True if the stream supports seeking, false otherwise.
     */
    public function isSeekable(): bool;

    /**
     * Rewind the stream to the beginning.
     *
     * Resets the stream position to the start. Only available for seekable streams.
     * This is equivalent to seeking to position 0.
     *
     * @return void
     *
     * @throws RuntimeException If the stream is not seekable.
     */
    public function rewind(): void;

    /**
     * Close the stream and release resources.
     *
     * Closes the stream resource and performs any necessary cleanup.
     * After closing, the stream should no longer be usable.
     * Calling this multiple times should be safe (idempotent).
     *
     * @return void
     */
    public function close(): void;

    /**
     * Detach the underlying stream resource.
     *
     * Detaches and returns the underlying PHP stream resource, or null if none exists.
     * After detaching, the StreamInterface instance should be considered unusable,
     * but the underlying resource remains open and can be used directly.
     *
     * @return resource|null The underlying stream resource, or null if not applicable.
     */
    public function detach();

    /**
     * Get the current position in the stream.
     *
     * Returns the current byte offset or position within the stream.
     * For non-seekable streams, the result may not be meaningful.
     *
     * @return int|false The current position in the stream, or false if the position cannot be determined.
     */
    public function tell(): ?int;
}
