<?php

namespace Arbor\files\filetypes\image\variants;

use Arbor\files\contracts\VariantProfileInterface;
use Arbor\files\filetypes\image\transformers\ImageWebp;
use Arbor\files\state\FileContext;
use Arbor\files\filetypes\image\transformers\ResizeImage;

/**
 * Defines the variant profile for generating a thumbnail image.
 *
 * Produces a 300×300 WebP thumbnail from the source image while preserving
 * the original aspect ratio. The resulting variant is stored under the
 * 'thumbnail' path and suffixed with 'thumb'.
 *
 * @package Arbor\files\filetypes\image\variants
 */
final class Thumbnail implements VariantProfileInterface
{
    /**
     * Return the name suffix used to identify this variant.
     *
     * @return string The suffix appended to the file name (e.g., 'thumb').
     */
    public function nameSuffix(): string
    {
        return 'thumb';
    }

    /**
     * Return the list of filters to apply for this variant.
     *
     * No filters are applied to the thumbnail variant.
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
     * Resizes the image to a maximum of 300×300 pixels while preserving
     * the aspect ratio, then converts the result to WebP format.
     *
     * @param  FileContext                    $context The file context to evaluate.
     * @return array<FileTransformerInterface>         An array containing the resize and WebP transformers.
     */
    public function transformers(FileContext $context): array
    {
        return [
            new ResizeImage(
                width: 300,
                height: 300,
                preserveAspectRatio: true,
            ),
            new ImageWebp(),
        ];
    }

    /**
     * Return the storage path where this variant should be saved.
     *
     * @return string The destination directory for the thumbnail variant.
     */
    public function path(): string
    {
        return 'thumbnail';
    }
}
