<?php

namespace Arbor\validation;

use Exception;
use Throwable;

class ValidationException extends Exception
{
    /**
     * @var array Detailed validation errors
     */
    protected array $errors = [];

    /**
     * Create a new ValidationException instance.
     *
     * @param string $message
     * @param array $errors
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(
        string $message = "Validation failed",
        array $errors = [],
        int $code = 0,
        Throwable|null $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }

    /**
     * Get all validation errors.
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get validation errors for a specific field.
     *
     * @param string $field
     * @return array
     */
    public function getErrorsFor(string $field): array
    {
        return $this->errors[$field] ?? [];
    }

    /**
     * Check if errors exist for a specific field.
     *
     * @param string $field
     * @return bool
     */
    public function hasErrorsFor(string $field): bool
    {
        return !empty($this->errors[$field]);
    }
}
