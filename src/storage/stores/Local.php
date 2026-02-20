<?php

namespace Arbor\storage\stores;


use Arbor\stream\StreamInterface;
use Arbor\stream\StreamFactory;
use RuntimeException;


/**
 * A {@see StoreInterface} implementation backed by the local filesystem.
 *
 * Performs all I/O operations directly against the host filesystem using standard
 * PHP file functions. All paths are expected to be absolute, pre-resolved by the
 * storage layer.
 *
 * @package Arbor\storage\stores
 */
class Local implements StoreInterface
{
    /**
     * Reads the file at the given path and returns its contents as a stream.
     *
     * @param  string $path The absolute filesystem path of the file to read.
     * @return StreamInterface A readable stream of the file's contents.
     *
     * @throws RuntimeException If no file exists at the given path.
     */
    public function read(string $path): StreamInterface
    {
        if (!is_file($path)) {
            throw new RuntimeException("File not found: {$path}");
        }

        return StreamFactory::fromFile($path);
    }


    /**
     * Writes (creates or overwrites) a file at the given path with the contents of the stream.
     *
     * Ensures the target directory exists before writing. The stream is rewound to
     * its start, detached to a raw resource, and copied to the target file handle.
     * The target file handle is always closed via a finally block.
     *
     * @param string          $path The absolute filesystem path to write to.
     * @param StreamInterface $data A readable stream containing the content to write.
     *
     * @throws RuntimeException If the file cannot be opened for writing, or if the stream
     *                          has already been detached or closed.
     */
    public function write(string $path, StreamInterface $data): void
    {
        $dir = dirname($path);

        ensureDir($dir);

        $target = fopen($path, 'wb');

        if ($target === false) {
            throw new RuntimeException("Unable to open file for writing: {$path}");
        }

        try {
            $data->fromStart();

            $source = $data->detach();

            if (!is_resource($source)) {
                throw new RuntimeException('Cannot write from a detached or closed stream');
            }

            stream_copy_to_stream($source, $target);
            fclose($source);
        } finally {
            fclose($target);
        }
    }


    /**
     * Deletes the file at the given path.
     *
     * If no file exists at the path, the method returns silently without error.
     *
     * @param string $path The absolute filesystem path of the file to delete.
     */
    public function delete(string $path): void
    {
        if (is_file($path)) {
            unlink($path);
        }
    }


    /**
     * Lists the entries in the directory at the given path.
     *
     * Returns a re-indexed array of entry names, excluding "." and "..".
     *
     * @param  string $path The absolute filesystem path of the directory to list.
     * @return array  A flat array of entry names within the directory.
     *
     * @throws RuntimeException If the path is not a directory or cannot be read.
     */
    public function list(string $path): array
    {
        if (!is_dir($path)) {
            throw new RuntimeException("Not a directory: {$path}");
        }

        $items = scandir($path);
        if ($items === false) {
            throw new RuntimeException("Unable to list directory: {$path}");
        }

        return array_values(
            array_filter($items, fn($i) => $i !== '.' && $i !== '..')
        );
    }


    /**
     * Renames (moves) a file on the local filesystem.
     *
     * Creates the destination directory if it does not already exist before
     * performing the rename.
     *
     * @param string $from The absolute source path.
     * @param string $to   The absolute destination path.
     *
     * @throws RuntimeException If the rename operation fails.
     */
    public function rename(string $from, string $to): void
    {
        $dir = dirname($to);

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        if (!rename($from, $to)) {
            throw new RuntimeException("Unable to rename {$from} to {$to}");
        }
    }


    /**
     * Checks whether a file or directory exists at the given path.
     *
     * @param  string $path The absolute filesystem path to check.
     * @return bool   True if the path exists, false otherwise.
     */
    public function exists(string $path): bool
    {
        return file_exists($path);
    }


    /**
     * Returns metadata for the file or directory at the given path.
     *
     * Retrieves information via {@see stat()} and, for files, detects the MIME type
     * using the {@see finfo} extension. The returned array contains the following keys:
     *
     * - `path`        — The absolute path of the resource.
     * - `type`        — Either "file" or "directory".
     * - `size`        — File size in bytes (0 for directories).
     * - `mime`        — MIME type string for files, or null if unavailable/not a file.
     * - `modified`    — Unix timestamp of the last modification time.
     * - `created`     — Unix timestamp of the inode change time.
     * - `accessed`    — Unix timestamp of the last access time.
     * - `permissions` — Four-character octal permission string (e.g. "0644").
     * - `inode`       — Inode number of the file.
     *
     * @param  string $path The absolute filesystem path of the resource.
     * @return array  An associative array of file metadata.
     *
     * @throws RuntimeException If the path does not exist or stat information cannot be read.
     */
    public function stats(string $path): array
    {
        if (!file_exists($path)) {
            throw new RuntimeException("Path not found: {$path}");
        }

        $stat = stat($path);

        if ($stat === false) {
            throw new RuntimeException("Unable to read stats for: {$path}");
        }

        $isFile = is_file($path);

        $mime = null;

        if ($isFile) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $mime = finfo_file($finfo, $path) ?: null;
            }
        }

        return [
            'path'        => $path,
            'type'        => $isFile ? 'file' : 'directory',
            'size'        => $isFile ? filesize($path) : 0,
            'mime'        => $mime,
            'modified'    => $stat['mtime'],
            'created'     => $stat['ctime'],
            'accessed'    => $stat['atime'],
            'permissions' => substr(sprintf('%o', fileperms($path)), -4),
            'inode'       => $stat['ino'],
        ];
    }


    /**
     * Appends content from the provided stream to a file at the given path.
     *
     * The stream is rewound to its start before reading. Content is written in
     * 8 KB chunks until the stream is exhausted.
     *
     * @param string          $path The absolute filesystem path of the file to append to.
     * @param StreamInterface $data A readable stream containing the content to append.
     *
     * @throws RuntimeException If the file cannot be opened for appending.
     */
    public function append(string $path, StreamInterface $data): void
    {
        $data->fromStart();

        $handle = fopen($path, 'ab');

        if ($handle === false) {
            throw new RuntimeException("Unable to open file for writing: {$path}");
        }

        while (!$data->eof()) {
            fwrite($handle, $data->read(8192));
        }

        fclose($handle);
    }


    /**
     * Copies a file from one local path to another.
     *
     * Uses PHP's native {@see copy()} function for an efficient kernel-level copy.
     *
     * @param string $from The absolute source path.
     * @param string $to   The absolute destination path.
     *
     * @throws RuntimeException If the copy operation fails.
     */
    public function copy(string $from, string $to): void
    {
        if (!copy($from, $to)) {
            throw new RuntimeException(
                "Failed to copy file from '{$from}' to '{$to}'"
            );
        }
    }
}
