<?php

namespace Arbor\files\policies;

use Arbor\facades\Config;
use Arbor\files\ingress\FileContext;
use Arbor\files\stores\FileStoreInterface;
use Arbor\files\strategies\FileStrategyInterface;
use Arbor\files\strategies\ImageWithGD;
use Arbor\files\recordStores\FileRecordStoreInterface;
use Arbor\files\stores\LocalStore;


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
        return new LocalStore(
            Config::get('root.uri'),
            Config::get('root.dir'),
        );
    }

    public function storePath(FileContext $context): string
    {
        return Config::get('root.uploads_path');
    }

    /**
     * Logical namespace for policy.
     */
    public function namespace(): string
    {
        return '';
    }
}
