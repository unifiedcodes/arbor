<?php

namespace Arbor\storage;

/**
 * Immutable value object representing file metadata.
 *
 * Stats encapsulates normalized filesystem information
 * retrieved from a storage driver (local, S3, cloud, etc.).
 *
 * It contains descriptive, structural, and temporal metadata
 * about a file at a specific point in time.
 *
 * This object is read-only and should not contain behavior.
 *
 * @package Arbor\storage
 */
final class Stats
{
    /**
     * @param string      $name        File name without path.
     * @param string|null $extension   File extension (without dot), if available.
     * @param string      $path        Absolute or resolved storage path.
     * @param string      $type        File type (e.g., file, dir).
     * @param int         $size        File size in bytes.
     * @param string|null $mime        MIME type if detectable.
     * @param int         $modified    Last modification timestamp (Unix).
     * @param int         $created     Creation timestamp (Unix).
     * @param int         $accessed    Last access timestamp (Unix).
     * @param string      $permissions Permission representation (driver-specific).
     * @param int|null    $inode       Inode number if available.
     * @param bool        $binary      Whether the file is detected as binary.
     */
    public function __construct(
        public readonly string $name,
        public readonly ?string $extension,
        public readonly string $path,
        public readonly string $type,
        public readonly int $size,
        public readonly ?string $mime,
        public readonly int $modified,
        public readonly int $created,
        public readonly int $accessed,
        public readonly string $permissions,
        public readonly ?int $inode,
        public readonly bool $binary,
    ) {}
}
