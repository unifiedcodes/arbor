<?php

namespace Arbor\storage\stores;

use Arbor\storage\Stats;
use Arbor\stream\StreamInterface;


/**
 * Defines the contract for all storage backend implementations.
 *
 * A store is responsible for performing raw I/O operations against a specific
 * storage medium (e.g. local filesystem, S3, GCS). All paths passed to store
 * methods are absolute paths, pre-resolved by the storage layer via {@see \Arbor\storage\Path}.
 *
 * Implementations should not concern themselves with URI parsing, scheme resolution,
 * or path normalisation — these are handled upstream by {@see \Arbor\storage\Storage}.
 *
 * @package Arbor\storage\stores
 */
interface StoreInterface
{
    /**
     * Reads the file at the given path and returns its contents as a stream.
     *
     * @param  string $path The absolute path of the file to read.
     * @return StreamInterface A readable stream of the file's contents.
     */
    public function read(string $path): StreamInterface;

    /**
     * Writes (creates or overwrites) a file at the given path with the provided stream.
     *
     * @param string          $path The absolute path of the file to write.
     * @param StreamInterface $data A readable stream containing the content to write.
     */
    public function write(string $path, StreamInterface $data): void;

    /**
     * Copies a file from one path to another within the same store.
     *
     * @param string $from The absolute source path.
     * @param string $to   The absolute destination path.
     */
    public function copy(string $from, string $to): void;

    /**
     * Deletes the file at the given path.
     *
     * @param string $path The absolute path of the file to delete.
     */
    public function delete(string $path): void;

    /**
     * Lists the contents of a directory at the given path.
     *
     * The shape and contents of the returned array are determined by each implementation,
     * but should represent the files and/or directories found at the given path.
     *
     * @param  string $path The absolute path of the directory to list.
     * @return array  An array of entries found at the given path.
     */
    public function list(string $path): array;

    /**
     * Renames (moves) a file within the same store.
     *
     * @param string $from The absolute source path.
     * @param string $to   The absolute destination path.
     */
    public function rename(string $from, string $to): void;

    /**
     * Appends content from the provided stream to an existing file at the given path.
     *
     * @param string          $path The absolute path of the file to append to.
     * @param StreamInterface $data A readable stream containing the content to append.
     */
    public function append(string $path, StreamInterface $data): void;

    /**
     * Checks whether a file exists at the given path.
     *
     * @param  string $path The absolute path to check.
     * @return bool   True if a file exists at the path, false otherwise.
     */
    public function exists(string $path): bool;

    /**
     * Returns metadata for the file at the given path.
     *
     * The shape of the returned array is determined by each implementation, but
     * typically includes information such as file size, MIME type, and last modified timestamp.
     *
     * @param  string $path The absolute path of the file.
     * @return Stats  An associative array of file metadata.
     */
    public function stats(string $path): Stats;
}
