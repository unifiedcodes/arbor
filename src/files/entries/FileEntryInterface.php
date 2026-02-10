<?php

namespace Arbor\files\entries;

use Arbor\files\ingress\Payload;

interface FileEntryInterface
{
    /**
     * Convert entry source into a normalized payload.
     */
    public function toPayload(): Payload;

    public function withInput(mixed $input): static;
}
