<?php

namespace Arbor\view\presets;

use Arbor\view\Document;

interface PresetInterface
{
    public function apply(Document $document): void;
}
