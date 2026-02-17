<?php

namespace Arbor\files\policies;

use Arbor\facades\Config;
use Arbor\files\ingress\FileContext;
use Arbor\files\strategies\FileStrategyInterface;
use Arbor\files\strategies\ImageWithGD;
use Arbor\storage\Uri;


final class Image extends FilePolicy implements FilePolicyInterface
{
    /**
     * Default options for image uploads.
     */
    protected function defaults(): array
    {
        return [
            'mimes' => [
                'image/jpeg',
                'image/png',
                'image/webp',
            ],
        ];
    }

    /**
     * Supported claimed mimes for this policy.
     */
    public function mimes(): array
    {
        return $this->option('mimes', []);
    }

    /**
     * Decide which strategy to use for this file.
     */
    public function strategy(FileContext $context): FileStrategyInterface
    {
        return new ImageWithGD();
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

    /**
     * Storage target for images.
     */
    public function uri(FileContext $context): Uri
    {
        return Uri::fromString('local://uploads/');
    }

    /**
     * Logical namespace for policy.
     */
    public function namespace(): string
    {
        return '';
    }


    public function variations(): array
    {
        return [];
    }
}
