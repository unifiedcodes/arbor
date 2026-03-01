<?php

namespace Arbor\view\presets;


use Arbor\view\Document;
use InvalidArgumentException;
use Closure;


final class ClosurePreset implements PresetInterface
{
    /**
     * @var Closure(Document): void
     */
    private $callback;

    public function __construct(Closure $callback)
    {
        $this->callback = $callback;
    }

    public function apply(Document $document): void
    {
        $result = ($this->callback)($document);

        if ($result !== null) {
            throw new InvalidArgumentException(
                'Preset callable must not return a value.'
            );
        }
    }
}
