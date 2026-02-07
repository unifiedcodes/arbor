<?php

namespace Arbor\files;

use Arbor\files\entries\FileEntryInterface;
use Arbor\files\FileContext;


final class Filer
{
    public function __construct(
        private FileEntryInterface $entryPrototype
    ) {}


    public function upload(mixed $input)
    {
        $fileEntry = $this->entryPrototype->withInput($input);

        $fileContext = FileContext::fromPayload(
            $fileEntry->toPayload()
        );

        print_r($fileContext);

        //choose strategy

        //orchestrate strategy

        //use registry to keep
    }
}
