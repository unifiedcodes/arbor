<?php

namespace Arbor\files\entries;

use Arbor\files\entries\FileEntryInterface;
use Arbor\files\Payload;


final class HttpEntry implements FileEntryInterface
{
    public function __construct(
        private ?array $file = null
    ) {}


    public function withInput(mixed $input): static
    {
        $clone = clone $this;
        $clone->file = $input;

        return $clone;
    }


    public function toPayload(): Payload
    {
        return new Payload(
            originalName: $this->file['name'],
            mime: $this->file['type'] ?? 'application/octet-stream',
            size: (int) $this->file['size'],
            source: $this->file['tmp_name'],
            meta: [
                'error' => $this->file['error'] ?? null,
            ]
        );
    }
}
