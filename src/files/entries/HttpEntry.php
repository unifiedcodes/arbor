<?php

namespace Arbor\files\entries;

use Arbor\files\contracts\FileEntryInterface;
use Arbor\files\state\Payload;
use Arbor\http\components\UploadedFile;
use RuntimeException;

/**
 * Represents a file entry sourced from an HTTP upload.
 *
 * Handles both raw PHP file arrays (from $_FILES) and {@see UploadedFile} instances,
 * normalizing either into a standardized {@see Payload} representation.
 *
 * @package Arbor\files\entries
 */
final class HttpEntry implements FileEntryInterface
{
    /**
     * @param UploadedFile|array|null $file The uploaded file source, either as a raw PHP
     *                                      file array, an {@see UploadedFile} instance, or null.
     */
    public function __construct(
        private UploadedFile|array|null $file = null
    ) {}

    /**
     * Return a new instance with the given input applied as the file source.
     *
     * @param  mixed        $input The raw file input to associate with the entry.
     * @return static              A new instance with the provided file input.
     */
    public function withInput(mixed $input): static
    {
        $clone = clone $this;
        $clone->file = $input;

        return $clone;
    }

    /**
     * Convert the HTTP file source into a normalized payload.
     *
     * Resolves the appropriate conversion strategy based on whether the file
     * source is a raw PHP file array or an {@see UploadedFile} instance.
     *
     * @return Payload                The normalized payload derived from the HTTP file source.
     * @throws RuntimeException       If no valid file source has been provided.
     */
    public function toPayload(): Payload
    {
        if (is_array($this->file)) {
            return $this->fromRawFile($this->file);
        }

        if ($this->file instanceof UploadedFile) {
            return $this->fromUploadedFile();
        }

        throw new RuntimeException('No uploaded file provided');
    }

    /**
     * Build a payload from a raw PHP file array (e.g., from $_FILES).
     *
     * @param  array<string, mixed> $file The raw PHP file array to convert.
     * @return Payload                    The normalized payload.
     */
    protected function fromRawFile(array $file): Payload
    {
        return new Payload(
            name: $file['name'],
            mime: $file['type'] ?? 'application/octet-stream',
            size: (int) $file['size'],
            path: $file['tmp_name'],
        );
    }

    /**
     * Build a payload from an {@see UploadedFile} instance.
     *
     * @return Payload The normalized payload derived from the uploaded file.
     */
    protected function fromUploadedFile(): Payload
    {
        return new Payload(
            name: $this->file->clientFilename,
            mime: $this->file->clientMediaType ?? 'application/octet-stream',
            size: $this->file->size,
            path: $this->file->tmpPath,
            extension: $this->file->clientExtension(),
        );
    }
}
