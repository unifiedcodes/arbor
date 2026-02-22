<?php

namespace Arbor\files\state;


use Arbor\files\state\FileContext;
use Arbor\files\state\VariantRecord;
use LogicException;


/**
 * Immutable record representing a successfully stored file, capturing all
 * verified metadata alongside the URI under which the file was persisted.
 *
 * Instances are created exclusively via the named constructor {@see self::from()},
 * which enforces that the source {@see FileContext} is proved before any record
 * is produced. Variants (e.g. resized images, transcoded formats) may be attached
 * via {@see self::withVariants()}, which returns a new clone to preserve value
 * semantics.
 *
 * @package Arbor\files\state
 */
final class FileRecord
{
    /**
     * @param string                    $uri       The URI or path under which the file was stored.
     * @param string                    $mime      Verified MIME type of the file (e.g. "image/png").
     * @param string                    $extension Verified file extension without leading dot (e.g. "png").
     * @param int                       $size      Verified file size in bytes.
     * @param string                    $name      Base name of the file (without extension).
     * @param bool                      $binary    Whether the file is binary (true) or text (false).
     * @param string|null               $hash      Optional content hash of the stored file.
     * @param array<string,VariantRecord> $variants  Map of named variant records attached to this file.
     */
    private function __construct(
        public readonly string $uri,
        public readonly string $mime,
        public readonly string $extension,
        public readonly int $size,
        public readonly string $name,
        public readonly bool $binary,
        public readonly ?string $hash = null,
        public array $variants = [],
    ) {}


    /**
     * Creates a new FileRecord from a proved FileContext and a storage URI.
     *
     * All core metadata is copied directly from the context; the context must
     * be in the proved state to guarantee that all fields are verified and
     * non-null.
     *
     * @param FileContext $context A proved FileContext carrying verified file metadata.
     * @param string      $uri     The URI or path under which the file has been stored.
     *
     * @return self
     *
     * @throws LogicException If the provided FileContext is not yet proved.
     */
    public static function from(
        FileContext $context,
        string $uri,
    ): self {
        if (!$context->isProved()) {
            throw new LogicException(
                'Cannot create FileRecord from unproved FileContext'
            );
        }

        return new self(
            uri: $uri,
            mime: $context->mime(),
            extension: $context->extension(),
            size: $context->size(),
            hash: $context->hash(),
            name: $context->name(),
            binary: $context->isBinary()
        );
    }


    /**
     * Returns a clone of this record with the given variants attached,
     * replacing any previously set variants.
     *
     * The provided array must be a map of {@see VariantRecord} instances;
     * any non-VariantRecord value will cause an exception to be thrown before
     * the clone is produced.
     *
     * @param array<string,VariantRecord> $variants Map of named VariantRecord instances.
     *
     * @return self A new instance with the variants set.
     *
     * @throws LogicException If any value in the array is not a VariantRecord instance.
     */
    public function withVariants(array $variants): self
    {

        foreach ($variants as $variant) {
            if (!$variant instanceof VariantRecord) {
                throw new LogicException('Variants must be array<string, VariantRecord>');
            }
        }

        $clone = clone $this;
        $clone->variants = $variants;

        return $clone;
    }
}
