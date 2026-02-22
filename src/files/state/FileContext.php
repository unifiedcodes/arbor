<?php

namespace Arbor\files\state;


use Arbor\stream\StreamInterface;
use RuntimeException;
use LogicException;


/**
 * Immutable value object representing the full context of a file, including its
 * source (path or stream), optional verified metadata, and arbitrary key/value
 * metadata bag.
 *
 * A FileContext may be in one of two states:
 *  - Unproved: source is known but metadata (mime, extension, size, isBinary)
 *    may be absent or unverified.
 *  - Proved: all core metadata fields have been verified and are guaranteed
 *    non-null; guarded accessors (mime(), extension(), size(), isBinary(),
 *    filename()) become safe to call.
 *
 * All mutating operations return a new instance, preserving immutability.
 *
 * @package Arbor\files\state
 */
final class FileContext
{
    /**
     * @param StreamInterface|null $stream    Optional stream source for the file contents.
     * @param string|null          $path      Optional filesystem path to the file.
     * @param string|null          $name      Base name of the file (without extension).
     * @param string|null          $mime      MIME type (e.g. "image/png").
     * @param string|null          $extension File extension (e.g. "png").
     * @param int|null             $size      File size in bytes.
     * @param bool|null            $isBinary  Whether the file is binary (true) or text (false).
     * @param string|null          $hash      Optional content hash of the file.
     * @param bool                 $proved    Whether core metadata has been verified.
     * @param array                $metadata  Arbitrary key/value metadata bag.
     *
     * @throws RuntimeException If neither $path nor $stream is provided.
     * @throws RuntimeException If $proved is true but any core metadata field is null.
     */
    public function __construct(
        private readonly ?StreamInterface $stream,
        private readonly ?string $path,
        private readonly ?string $name,
        private readonly ?string $mime,
        private readonly ?string $extension,
        private readonly ?int $size,
        private readonly ?bool $isBinary,
        private readonly ?string $hash,
        private readonly bool $proved,
        private array $metadata = [],
    ) {
        // Source invariant (always required)
        if ($path === null && $stream === null) {
            throw new RuntimeException(
                'FileContext must have either path or stream.'
            );
        }

        // Proven invariant
        if ($proved) {
            if (
                $mime === null ||
                $extension === null ||
                $size === null ||
                $isBinary === null
            ) {
                throw new RuntimeException(
                    'Proved FileContext must contain complete verified metadata.'
                );
            }
        }
    }


    /**
     * Returns a new instance with the metadata bag entirely replaced by the
     * given array, discarding any previously stored metadata entries.
     *
     * @param array $metadata The replacement metadata bag.
     *
     * @return self
     */
    public function withReplacedMeta(array $metadata): self
    {
        return new self(
            stream: $this->stream,
            path: $this->path,
            name: $this->name,
            mime: $this->mime,
            extension: $this->extension,
            size: $this->size,
            isBinary: $this->isBinary,
            hash: $this->hash,
            proved: $this->proved,
            metadata: $metadata,
        );
    }


    /**
     * Returns a new instance with a single metadata entry set or overwritten.
     * Dot-notation keys are supported via the underlying value_set_at() helper.
     *
     * @param string $key   Metadata key (supports dot notation for nested paths).
     * @param mixed  $value Value to associate with the key.
     *
     * @return self
     */
    public function withMeta(string $key, mixed $value): self
    {
        $metadata = $this->metadata;
        value_set_at($metadata, $key, $value);

        return new self(
            stream: $this->stream,
            path: $this->path,
            name: $this->name,
            mime: $this->mime,
            extension: $this->extension,
            size: $this->size,
            isBinary: $this->isBinary,
            hash: $this->hash,
            proved: $this->proved,
            metadata: $metadata,
        );
    }


    /**
     * Retrieves the value stored under the given metadata key.
     * Dot-notation keys are supported via the underlying value_at() helper.
     *
     * @param string $key Metadata key (supports dot notation for nested paths).
     *
     * @return mixed The stored value, which may itself be null if null was explicitly set.
     *
     * @throws LogicException If the key does not exist in the metadata bag.
     */
    public function getMeta(string $key): mixed
    {
        $value = value_at($this->metadata, $key, null);

        if ($value === null && !$this->has($key)) {
            throw new LogicException(
                'attribute not available: ' . $key
            );
        }

        return $value;
    }


    /**
     * Returns whether the given key exists in the metadata bag.
     * Dot-notation keys are supported via the underlying value_at() helper.
     *
     * @param string $key Metadata key (supports dot notation for nested paths).
     *
     * @return bool True if the key is present, false otherwise.
     */
    public function has(string $key): bool
    {
        return value_at($this->metadata, $key, '__missing__') !== '__missing__';
    }


    /**
     * Asserts that this context is in the proved state.
     *
     * @throws RuntimeException If the context has not yet been proved.
     */
    public function assertProved(): void
    {
        if (!$this->proved) {
            throw new RuntimeException(
                'FileContext is not yet proved.'
            );
        }
    }

    /**
     * Returns the full filename composed of the base name and extension.
     * Requires the context to be proved and both name and extension to be set.
     *
     * @return string The filename (e.g. "document.pdf").
     *
     * @throws RuntimeException If the context is not proved.
     * @throws RuntimeException If name or extension is missing.
     */
    public function filename(): string
    {
        $this->assertProved();

        if ($this->name === null || $this->extension === null) {
            throw new RuntimeException(
                'Cannot build filename: name or extension missing.'
            );
        }

        return $this->name . '.' . $this->extension;
    }

    /**
     * Returns the verified MIME type of the file.
     *
     * @return string The MIME type (e.g. "image/png").
     *
     * @throws RuntimeException If the context is not proved.
     */
    public function mime(): string
    {
        $this->assertProved();
        return $this->mime;
    }

    /**
     * Returns the verified file extension.
     *
     * @return string The file extension without leading dot (e.g. "png").
     *
     * @throws RuntimeException If the context is not proved.
     */
    public function extension(): string
    {
        $this->assertProved();
        return $this->extension;
    }

    /**
     * Returns the verified file size in bytes.
     *
     * @return int File size in bytes.
     *
     * @throws RuntimeException If the context is not proved.
     */
    public function size(): int
    {
        $this->assertProved();
        return $this->size;
    }

    /**
     * Returns whether the file has been verified as binary content.
     *
     * @return bool True if the file is binary, false if it is text.
     *
     * @throws RuntimeException If the context is not proved.
     */
    public function isBinary(): bool
    {
        $this->assertProved();
        return $this->isBinary;
    }

    /**
     * Returns the stream source for this file, if one was provided.
     *
     * @return StreamInterface|null The stream, or null if the file is path-based.
     */
    public function stream(): ?StreamInterface
    {
        return $this->stream;
    }

    /**
     * Returns the filesystem path for this file, if one was provided.
     *
     * @return string|null The path, or null if the file is stream-based.
     */
    public function path(): ?string
    {
        return $this->path;
    }

    /**
     * Returns the base name of the file (without extension), if set.
     *
     * @return string|null The base name, or null if not provided.
     */
    public function name(): ?string
    {
        return $this->name;
    }

    /**
     * Returns the content hash of the file, if one was computed or provided.
     *
     * @return string|null The hash string, or null if not available.
     */
    public function hash(): ?string
    {
        return $this->hash;
    }

    /**
     * Returns the full metadata bag as an associative array.
     *
     * @return array The metadata bag.
     */
    public function metadata(): array
    {
        return $this->metadata;
    }

    /**
     * Returns the raw MIME value without requiring the context to be proved.
     * Intended for inspection or diagnostic use only.
     *
     * @return string|null The MIME type, or null if not yet determined.
     */
    public function inspectMime(): ?string
    {
        return $this->mime;
    }

    /**
     * Returns the raw extension value without requiring the context to be proved.
     * Intended for inspection or diagnostic use only.
     *
     * @return string|null The extension, or null if not yet determined.
     */
    public function inspectExtension(): ?string
    {
        return $this->extension;
    }

    /**
     * Returns the raw size value without requiring the context to be proved.
     * Intended for inspection or diagnostic use only.
     *
     * @return int|null The size in bytes, or null if not yet determined.
     */
    public function inspectSize(): ?int
    {
        return $this->size;
    }

    /**
     * Returns the raw binary flag without requiring the context to be proved.
     * Intended for inspection or diagnostic use only.
     *
     * @return bool|null True if binary, false if text, or null if not yet determined.
     */
    public function inspectBinary(): ?bool
    {
        return $this->isBinary;
    }

    /**
     * Returns whether this context has been proved (i.e. all core metadata
     * fields are verified and non-null).
     *
     * @return bool True if proved, false otherwise.
     */
    public function isProved(): bool
    {
        return $this->proved;
    }
}
