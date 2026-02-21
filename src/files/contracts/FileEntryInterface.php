<?php

namespace Arbor\files\contracts;

use Arbor\files\state\Payload;

interface FileEntryInterface
{
    /**
     * Convert entry source into a normalized payload.
     */
    public function toPayload(): Payload;

    public function withInput(mixed $input): static;
}
