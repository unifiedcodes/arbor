<?php

namespace Arbor\files\filetypes\image;

use Arbor\files\state\FileContext;
use Arbor\files\utilities\BaseVariantPolicy;
use Arbor\files\filetypes\image\variants\Thumbnail;
use Arbor\files\filetypes\image\variants\Webp;

/**
 * Defines the variants policy for generating derivative image files.
 *
 * Governs how image variants are produced from a proved source image,
 * including the set of variant profiles to apply, supported MIME types,
 * the URI scheme, and the base storage path for generated variants.
 *
 * @package Arbor\files\filetypes\image
 */
final class ImageVariantPolicy extends BaseVariantPolicy
{
    /**
     * Return the default options for this variant policy.
     *
     * @return array<string, mixed> An empty array — no default options are defined.
     */
    public function defaultOptions(): array
    {
        return [];
    }

    /**
     * Return the list of variant profiles to generate for the given image file context.
     *
     * Produces a {@see Thumbnail} and a {@see Webp} variant for each processed image.
     *
     * @param  FileContext                   $context The file context to evaluate.
     * @return array<VariantProfileInterface>         An array containing the thumbnail and WebP variant profiles.
     */
    public function variants(FileContext $context): array
    {
        return [
            new Thumbnail(),
            new Webp(),
        ];
    }

    /**
     * Return the URI scheme identifier for this variant policy.
     *
     * @return string The scheme identifier for image variants (e.g., 'images').
     */
    public function scheme(): string
    {
        return 'images';
    }

    /**
     * Return the list of accepted MIME types for this variant policy.
     *
     * @return array<int, string> An array of supported image MIME type strings.
     */
    public function mimes(): array
    {
        return [
            'image/jpeg',
            'image/png',
            'image/webp',
        ];
    }

    /**
     * Return the base storage path where generated image variants should be saved.
     *
     * @param  FileContext $context The file context to evaluate.
     * @return string               The base destination path for generated image variants.
     */
    public function path(FileContext $context): string
    {
        return '/uploads/';
    }
}
