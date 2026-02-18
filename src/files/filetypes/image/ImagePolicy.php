<?php

namespace Arbor\files\filetypes\image;


use Arbor\facades\Config;
use Arbor\files\contracts\FileStrategyInterface;
use Arbor\files\ingress\FileContext;
use Arbor\files\filetypes\image\ImageStrategyGD;
use Arbor\files\utilities\AbstractFilePolicy;
use Arbor\storage\namespace\DefaultNamespace;
use Arbor\storage\namespace\NamespaceInterface;
use Arbor\storage\Uri;


final class ImagePolicy extends AbstractFilePolicy
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
    public function namespace(): NamespaceInterface
    {
        return DefaultNamespace::DEFAULT;
    }
}
