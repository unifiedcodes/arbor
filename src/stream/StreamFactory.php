<?php

namespace Arbor\stream;

use Arbor\stream\StreamInterface;
use RuntimeException;


/**
 * StreamFactory
 *
 * Factory class for creating StreamInterface instances from various sources.
 * Provides convenient static methods to instantiate streams from files, strings,
 * PHP input, raw resources, and other sources. Also provides utilities for
 * converting non-seekable streams into seekable ones.
 *
 * @package Arbor\stream
 */
final class StreamFactory
{
    /**
     * Create a stream from a file.
     *
     * Opens the specified file in binary read mode and returns it as a Stream.
     * The stream will be readable but not writable.
     *
     * @param string $path The file path to open. Can be a local path or stream wrapper URI.
     *
     * @return StreamInterface A readable stream wrapping the opened file.
     *
     * @throws RuntimeException If the file cannot be opened.
     */
    public static function fromFile(string $path): StreamInterface
    {
        $resource = fopen($path, 'rb');

        if ($resource === false) {
            throw new RuntimeException("Unable to open file: {$path}");
        }

        return new Stream($resource);
    }


    /**
     * Create a stream from a string buffer.
     *
     * Creates an in-memory temporary stream (php://temp) and populates it with
     * the provided string. The stream position is reset to the beginning.
     * The returned stream will be both readable and writable.
     *
     * @param string $buffer The string content to populate the stream with.
     *
     * @return StreamInterface A readable and writable stream containing the string data.
     *
     * @throws RuntimeException If the temporary stream cannot be created.
     */
    public static function fromString(string $buffer): StreamInterface
    {
        $resource = fopen('php://temp', 'rb+');

        if ($resource === false) {
            throw new RuntimeException('Unable to create memory stream');
        }

        fwrite($resource, $buffer);
        rewind($resource);

        return new Stream($resource);
    }


    /**
     * Create a stream from PHP's standard input.
     *
     * Opens the php://input stream which allows reading the raw request body
     * in HTTP requests. Useful for accessing POST/PUT request bodies.
     * Note: php://input is non-seekable and can only be read once.
     *
     * @return StreamInterface A readable stream wrapping php://input.
     *
     * @throws RuntimeException If php://input cannot be opened.
     */
    public static function fromPhpInput(): StreamInterface
    {
        $resource = fopen('php://input', 'rb');

        if ($resource === false) {
            throw new RuntimeException('Unable to open php://input');
        }

        return new Stream($resource);
    }


    /**
     * Create a stream from an existing PHP resource.
     *
     * Wraps a raw PHP stream resource in a StreamInterface instance.
     * Useful for converting existing PHP stream resources or creating streams
     * from socket connections, pipes, or other resource types.
     *
     * @param resource $resource A valid PHP stream resource.
     *
     * @return StreamInterface A stream wrapping the provided resource.
     *
     * @throws RuntimeException If the resource is invalid (thrown by Stream constructor).
     */
    public static function fromResource($resource): StreamInterface
    {
        return new Stream($resource);
    }


    /**
     * Convert a stream into a rewindable stream.
     *
     * If the stream is already seekable, it is rewound and returned as-is.
     * If the stream is non-seekable, it is buffered into a temporary stream
     * (php://temp) to make it seekable. The current position must be at the
     * start of the stream (position 0) for non-seekable streams, otherwise
     * an exception is thrown.
     *
     * @param StreamInterface $stream The stream to make rewindable.
     *
     * @return StreamInterface A seekable/rewindable stream.
     *
     * @throws RuntimeException If the stream is non-seekable and has been partially read,
     *                          or if temporary stream creation fails.
     */
    public static function toRewindable(StreamInterface $stream): StreamInterface
    {
        $stream = $stream->fromStart();

        $resource = fopen('php://temp', 'wb+');

        if ($resource === false) {
            throw new RuntimeException('Unable to create temporary stream');
        }

        while (!$stream->eof()) {
            fwrite($resource, $stream->read(8192));
        }

        rewind($resource);

        return new Stream($resource);
    }


    /**
     * Create an empty stream.
     *
     * Creates a new in-memory temporary stream (php://temp) with no initial content.
     * The stream is both readable and writable, and is positioned at the beginning.
     * Useful as a base for building stream content or for testing.
     *
     * @return StreamInterface An empty, readable, and writable stream.
     *
     * @throws RuntimeException If the temporary stream cannot be created.
     */
    public static function empty(): StreamInterface
    {
        $resource = fopen('php://temp', 'rb+');

        if ($resource === false) {
            throw new RuntimeException('Unable to create empty stream');
        }

        return new Stream($resource);
    }
}
