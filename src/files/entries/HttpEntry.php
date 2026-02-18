<?php

namespace Arbor\files\entries;

use Arbor\files\contracts\FileEntryInterface;
use Arbor\files\ingress\Payload;
use Arbor\http\components\UploadedFile;
use RuntimeException;


final class HttpEntry implements FileEntryInterface
{
    public function __construct(
        private UploadedFile|array|null $file = null
    ) {}


    public function withInput(mixed $input): static
    {
        $clone = clone $this;
        $clone->file = $input;

        return $clone;
    }


    public function toPayload(): Payload
    {
        if (is_array($this->file)) {
            return $this->fromRawFile($this->file);
        }

        if ($this->file instanceof UploadedFile) {
            return $this->fromUploadedFile();
        }

        throw new RuntimeException('No uploaded file provided');
    }


    protected function fromRawFile(array $file): Payload
    {
        return new Payload(
            name: $file['name'],
            mime: $file['type'] ?? 'application/octet-stream',
            size: (int) $file['size'],
            path: $file['tmp_name'],
            error: $file['error'] ?? null,
            moved: false
        );
    }


    protected function fromUploadedFile(): Payload
    {
        return new Payload(
            name: $this->file->clientFilename,
            mime: $this->file->clientMediaType ?? 'application/octet-stream',
            size: $this->file->size,
            path: $this->file->tmpPath,
            error: $this->file->error,
            extension: $this->file->clientExtension(),
            moved: false
        );
    }
}
