<?php

namespace Arbor\files\filetypes\image\variants;

use Arbor\files\contracts\VariantProfileInterface;
use Arbor\files\state\FileContext;
use Arbor\files\filetypes\image\transformers\ImageWebp;


final class Webp implements VariantProfileInterface
{
    public function nameSuffix(): string
    {
        return 'webp';
    }


    public function filters(FileContext $context): array
    {
        return [];
    }


    public function transformers(FileContext $context): array
    {
        return [
            new ImageWebp(),
        ];
    }


    public function path(): string
    {
        return 'webp';
    }
}
