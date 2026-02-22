<?php

namespace Arbor\files\filetypes\image;

use Arbor\files\contracts\FileStrategyInterface;
use Arbor\files\state\FileContext;
use Arbor\files\filetypes\image\ImageStrategy;
use Arbor\files\utilities\BaseIngressPolicy;

/**
 * Defines the ingress policy for handling incoming image file uploads.
 *
 * Governs how JPEG, PNG, and WebP image uploads are received and processed,
 * including the strategy used to prove the file context, supported MIME types,
 * applicable filters and transformers, and the destination storage path.
 *
 * @package Arbor\files\filetypes\image
 */
final class ImageIngressPolicy extends BaseIngressPolicy
{
    /**
     * Return the default options for image uploads.
     *
     * @return array<string, mixed> An empty array — no default options are defined.
     */
    protected function defaultOptions(): array
    {
        return [];
    }

    /**
     * Return the list of accepted MIME types for image uploads.
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
     * Resolve the file strategy to use for the given image file context.
     *
     * @param  FileContext           $context The file context to evaluate.
     * @return FileStrategyInterface          The {@see ImageStrategy} instance for proving the context.
     */
    public function strategy(FileContext $context): FileStrategyInterface
    {
        return new ImageStrategy();
    }

    /**
     * Return the list of filters to apply for the given image file context.
     *
     * No filters are applied by default, but this method may be extended
     * or overridden to apply context-aware filtering via options.
     *
     * @param  FileContext $context The file context to evaluate.
     * @return array<empty>         An empty array — no filters are applied by default.
     */
    public function filters(FileContext $context): array
    {
        return [];
    }

    /**
     * Return the list of transformers to apply for the given image file context.
     *
     * No transformers are applied by default, but this method may be extended
     * or overridden to apply context-aware transformations via options.
     *
     * @param  FileContext $context The file context to evaluate.
     * @return array<empty>         An empty array — no transformers are applied by default.
     */
    public function transformers(FileContext $context): array
    {
        return [];
    }

    /**
     * Return the URI scheme identifier for this policy.
     *
     * @return string The scheme identifier for image ingress (e.g., 'images').
     */
    public function scheme(): string
    {
        return 'images';
    }

    /**
     * Return the destination storage path for uploaded image files.
     *
     * @param  FileContext $context The file context to evaluate.
     * @return string               The base storage path for incoming image uploads.
     */
    public function path(FileContext $context): string
    {
        return '/uploads/';
    }
}
