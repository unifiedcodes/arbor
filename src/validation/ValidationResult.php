<?php

namespace Arbor\validation;

class ValidationResult
{
    protected $errFormatter;

    public function __construct(
        protected $isValid = false,
        protected $errors = [],
        protected $isBatch = false,
        protected ?string $fieldName = null
    ) {
        $this->errFormatter = new ErrorsFormatter();
    }

    public function isValid(): bool
    {
        return $this->isValid;
    }

    public function fieldName(): ?string
    {
        return $this->fieldName;
    }

    public function errorsRaw(): array
    {
        return $this->errors;
    }

    public function errors(): string|array
    {
        if ($this->isBatch) {
            return $this->errFormatter->formatBatch(
                $this->errors
            );
        }

        return $this->errFormatter->format(
            $this->errors,
            $this->fieldName
        );
    }
}
