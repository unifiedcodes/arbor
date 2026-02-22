<?php

namespace Arbor\files\state;


use Arbor\stream\StreamInterface;
use InvalidArgumentException;


/**
 * Simple value object carrying the raw input data for a file operation before
 * any metadata verification or processing has taken place.
 *
 * A Payload represents the caller's initial description of a file — its name,
 * optional hints about MIME type, size, and extension, and a mandatory source
 * (either a filesystem path or a stream). It makes no guarantees about the
 * accuracy of the provided metadata; that responsibility belongs to the proving
 * step which produces a {@see FileContext}.
 *
 * @package Arbor\files\state
 */
final class Payload
{
    /**
     * @param string               $name      Base filename including extension as supplied by the caller.
     * @param string|null          $mime      Optional MIME type hint (e.g. "image/png"); may be unverified.
     * @param int|null             $size      Optional file size hint in bytes; may be unverified.
     * @param string|null          $extension Optional file extension hint without leading dot (e.g. "png").
     * @param string|null          $path      Filesystem path to the file. Required if $stream is null.
     * @param StreamInterface|null $stream    Stream containing the file contents. Required if $path is null.
     * @param string|null          $hash      Optional content hash supplied by the caller.
     *
     * @throws InvalidArgumentException If neither $path nor $stream is provided.
     */
    public function __construct(
        public readonly string $name,
        public readonly ?string $mime,
        public readonly ?int $size = null,
        public readonly ?string $extension = null,
        public readonly ?string $path = null,
        public readonly ?StreamInterface $stream = null,
        public readonly ?string $hash = null,
    ) {
        if ($this->path === null && $this->stream === null) {
            throw new InvalidArgumentException(
                'Either path or stream must be provided.'
            );
        }
    }
}
