<?php

namespace Arbor\files;


use Arbor\files\Filer;
use Arbor\files\FilesKeeper;


class Files
{
    // primary orchestrator for Files module.
    // delegates to Filer and FileKeeper
    public function __construct(
        private Filer $filer,
        private FilesKeeper $filesKeeper,
    ) {}
}
