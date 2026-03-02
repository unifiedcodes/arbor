<?php

namespace Arbor\view\presets;

use Arbor\view\Document;

/**
 * Contract for document presets.
 *
 * A preset encapsulates reusable document configuration logic.
 * It receives a Document instance and mutates it
 * (e.g., adding meta tags, styles, scripts, attributes, etc.).
 *
 * Presets allow consistent document configuration
 * across multiple views.
 */
interface PresetInterface
{
    /**
     * Apply preset configuration to the given document.
     *
     * Implementations should modify the document
     * by adding or configuring required elements.
     *
     * @param Document $document
     * @return void
     */
    public function apply(Document $document): void;
}
