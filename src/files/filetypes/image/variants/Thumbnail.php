<?php

namespace Arbor\files\filetypes\image\variants;

use Arbor\files\contracts\VariantProfileInterface;
use Arbor\files\filetypes\image\transformers\ImageWebp;
use Arbor\files\state\FileContext;
use Arbor\files\filetypes\image\transformers\ResizeImage;


final class Thumbnail implements VariantProfileInterface
{
    public function nameSuffix(): string
    {
        return 'thumb';
    }


    public function filters(FileContext $context): array
    {
        return [];
    }


    public function transformers(FileContext $context): array
    {
        return [
            new ResizeImage(
                width: 300,
                height: 300,
                preserveAspectRatio: true,
            ),
            new ImageWebp(),
        ];
    }


    public function path(): string
    {
        return 'thumbnail';
    }
}
