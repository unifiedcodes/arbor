<?php

namespace Arbor\files\state;


/**
 * Immutable record representing a single variant of a stored file.
 *
 * A variant is a derived version of an original file, such as a resized image,
 * a transcoded video, or a reformatted document. Each variant is independently
 * addressable via its own URI and carries its own verified metadata.
 *
 * Variant records are typically assembled into a map and attached to a
 * {@see FileRecord} via {@see FileRecord::withVariants()}.
 *
 * @package Arbor\files\state
 */
final class VariantRecord
{
    /**
     * @param string      $name      Identifier name for this variant (e.g. "thumbnail", "webp").
     * @param string      $uri       The URI or path under which this variant is stored.
     * @param string      $mime      MIME type of the variant (e.g. "image/webp").
     * @param string      $extension File extension of the variant without leading dot (e.g. "webp").
     * @param int         $size      File size of the variant in bytes.
     * @param string|null $type      Optional semantic type tag for the variant (e.g. "preview", "thumbnail").
     * @param string|null $hash      Optional content hash of the variant file.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $uri,
        public readonly string $mime,
        public readonly string $extension,
        public readonly int $size,
        public readonly ?string $type = null,
        public readonly ?string $hash = null
    ) {}
}
