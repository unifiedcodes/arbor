<?php

namespace Arbor\files\filetypes\image;


use Arbor\files\contracts\FileStrategyInterface;
use Arbor\files\ingress\FileContext;
use Arbor\files\filetypes\image\ImageStrategyGD;
use Arbor\files\utilities\BaseIngressPolicy;


final class ImageIngressPolicy extends BaseIngressPolicy
{
    /**
     * Default options for image uploads.
     */
    protected function defaultOptions(): array
    {
        return [];
    }

    /**
     * Supported claimed mimes for this policy.
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
     * Decide which strategy to use for this file.
     */
    public function strategy(FileContext $context): FileStrategyInterface
    {
        return new ImageStrategyGD();
    }

    /**
     * Filters for image uploads.
     * Empty by default, but extendable via options.
     */
    public function filters(FileContext $context): array
    {
        return [];
    }

    /**
     * Transformers for image uploads.
     * Empty by default, but extendable via options.
     */
    public function transformers(FileContext $context): array
    {
        return [];
    }


    public function scheme(): string
    {
        return 'images';
    }

    /**
     * Storage target for images.
     */
    public function path(FileContext $context): string
    {
        return '/uploads/';
    }
}
