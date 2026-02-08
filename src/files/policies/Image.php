<?php

namespace Arbor\files\policies;


use Arbor\files\FileContext;
use Arbor\files\stores\FileStoreInterface;
use Arbor\files\strategies\FileStrategyInterface;
use Arbor\files\strategies\ImageWithGD;
use LogicException;


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
    public function store(FileContext $context): FileStoreInterface
    {
        throw new LogicException(
            'Image policy does not define a store yet'
        );
    }

    /**
     * Logical namespace for storage paths.
     */
    public function namespace(): string
    {
        return '';
    }
}
