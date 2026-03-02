<?php

namespace Arbor\view\presets;

use Arbor\view\Document;
use InvalidArgumentException;

/**
 * A preset that groups multiple presets together.
 *
 * This allows multiple PresetInterface implementations
 * to be applied sequentially as a single unit.
 *
 * Presets are executed in the order they are provided.
 */
final class CompositePreset implements PresetInterface
{
    /**
     * @var PresetInterface[]
     */
    private array $presets;

    /**
     * @param PresetInterface ...$presets
     *
     * @throws InvalidArgumentException
     */
    public function __construct(PresetInterface ...$presets)
    {
        foreach ($presets as $preset) {
            if (!$preset instanceof PresetInterface) {
                throw new InvalidArgumentException(
                    'All presets must implement PresetInterface.'
                );
            }
        }

        $this->presets = $presets;
    }

    /**
     * Apply all contained presets to the document.
     *
     * Presets are executed sequentially
     * in the order they were provided.
     *
     * @param Document $document
     * @return void
     */
    public function apply(Document $document): void
    {
        foreach ($this->presets as $preset) {
            $preset->apply($document);
        }
    }

    /**
     * Create a new CompositePreset with an additional preset appended.
     *
     * This method is immutable — it does not modify the current instance.
     *
     * @param PresetInterface $preset
     * @return self
     */
    public function with(PresetInterface $preset): self
    {
        return new self(...[...$this->presets, $preset]);
    }
}
