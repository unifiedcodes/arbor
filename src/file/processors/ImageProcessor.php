<?php

namespace Arbor\files\Processors;

use Arbor\file\FileProcessorInterface;

class ImageProcessor implements FileProcessorInterface
{
    public function process(array $meta = [])
    {
        // read the image file and create thumbnails.
    }
}
