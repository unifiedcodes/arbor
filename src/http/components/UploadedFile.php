<?php

namespace Arbor\http\components;

use Arbor\storage\streams\StreamInterface;
use Arbor\storage\streams\StreamFactory;

/**
 * UploadedFile
 *
 * Thin wrapper around PHP's $_FILES entry.
 * Holds metadata and exposes an explicit way to obtain a Storage Stream.
 *
 * No lifecycle management.
 * No file movement.
 * No internal state mutation.
 */
final class UploadedFile
{
    public function __construct(
        public readonly string  $tmpPath,
        public readonly int     $size,
        public readonly int     $error,
        public readonly ?string $clientFilename = null,
        public readonly ?string $clientMediaType = null,
    ) {}

    /**
     * Create a Storage stream from the temporary uploaded file.
     *
     * This does NOT move the file.
     * This does NOT cache the stream.
     * This does NOT manage lifecycle.
     *
     * @throws \RuntimeException if upload failed
     */
    public function stream(): StreamInterface
    {
        if ($this->error !== UPLOAD_ERR_OK) {
            throw new \RuntimeException(
                'Cannot create stream: upload error ' . $this->error
            );
        }

        return StreamFactory::fromFile($this->tmpPath);
    }

    /**
     * Client-provided file extension (claim).
     */
    public function clientExtension(): ?string
    {
        if ($this->clientFilename === null) {
            return null;
        }

        $ext = pathinfo($this->clientFilename, PATHINFO_EXTENSION);

        return $ext !== '' ? strtolower($ext) : null;
    }
}
