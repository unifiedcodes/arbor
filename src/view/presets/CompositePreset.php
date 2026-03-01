<?php

namespace Arbor\view\presets;


use Arbor\view\Document;
use InvalidArgumentException;


final class CompositePreset implements PresetInterface
{
    /**
     * @var PresetInterface[]
     */
    private array $presets;

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

    public function apply(Document $document): void
    {
        foreach ($this->presets as $preset) {
            $preset->apply($document);
        }
    }

    public function with(PresetInterface $preset): self
    {
        return new self(...[...$this->presets, $preset]);
    }
}
