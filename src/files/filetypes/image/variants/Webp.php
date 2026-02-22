<?php

namespace Arbor\files\filetypes\image\variants;

use Arbor\files\contracts\VariantProfileInterface;
use Arbor\files\state\FileContext;
use Arbor\files\filetypes\image\transformers\ImageWebp;

/**
 * Defines the variant profile for generating a WebP-converted image.
 *
 * Converts the source image to WebP format without applying any filters
 * or resizing. The resulting variant is stored under the 'webp' path
 * and suffixed with 'webp'.
 *
 * @package Arbor\files\filetypes\image\variants
 */
final class Webp implements VariantProfileInterface
{
    /**
     * Return the name suffix used to identify this variant.
     *
     * @return string The suffix appended to the file name (e.g., 'webp').
     */
    public function nameSuffix(): string
    {
        return 'webp';
    }

    /**
     * Return the list of filters to apply for this variant.
     *
     * No filters are applied to the WebP variant.
     *
     * @param  FileContext $context The file context to evaluate.
     * @return array<empty>         An empty array — no filters are applied.
     */
    public function filters(FileContext $context): array
    {
        return [];
    }

    /**
     * Return the list of transformers to apply for this variant.
     *
     * Converts the source image to WebP format using the {@see ImageWebp} transformer.
     *
     * @param  FileContext                    $context The file context to evaluate.
     * @return array<FileTransformerInterface>         An array containing the WebP transformer.
     */
    public function transformers(FileContext $context): array
    {
        return [
            new ImageWebp(),
        ];
    }

    /**
     * Return the storage path where this variant should be saved.
     *
     * @return string The destination directory for the WebP variant.
     */
    public function path(): string
    {
        return 'webp';
    }
}
