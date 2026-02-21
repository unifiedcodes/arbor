<?php

namespace Arbor\files\filetypes\image;


use Arbor\files\state\FileContext;
use Arbor\files\utilities\BaseVariantPolicy;


final class ImageVariantPolicy extends BaseVariantPolicy
{
    public function defaultOptions(): array
    {
        return [];
    }

    public function variants(FileContext $context): array
    {
        return [];
    }

    public function scheme(): string
    {
        return 'images';
    }

    public function mimes(): array
    {
        return [
            'image/jpeg',
            'image/png',
            'image/webp',
        ];
    }

    public function path(FileContext $context): string
    {
        return '';
    }
}
