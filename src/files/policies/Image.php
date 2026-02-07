<?php

namespace Arbor\files\policies;

use Arbor\files\policy\FilePolicyInterface;
use Arbor\files\FileContext;
use Arbor\files\stores\FileStoreInterface;
use Arbor\files\strategies\FileStrategyInterface;
use Arbor\files\strategies\ImageWithGD;
use LogicException;


final class Image implements FilePolicyInterface
{
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
        return new ImageWithGD();
    }

    /**
     * No-op filters.
     */
    public function filters(FileContext $context): array
    {
        return [];
    }

    /**
     * No-op transformers.
     */
    public function transformers(FileContext $context): array
    {
        return [];
    }

    /**
     * Storage target for images.
     *
     * NOTE:
     * If you already have a default image store, return it here.
     * Throwing for now is acceptable if storage is not wired yet.
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
        return 'images';
    }
}
