<?php

namespace Arbor\files;

use Arbor\files\entries\FileEntryInterface;
use Arbor\files\entries\HttpEntry;

final class Filer
{
    public function __construct(
        private FileEntryInterface $fileEntry
    ) {}


    public function upload(mixed $input)
    {
        $fileEntry = $this->fileEntry->withInput($input);

        
    }
}
