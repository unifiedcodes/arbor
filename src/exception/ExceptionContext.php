<?php

namespace Arbor\exception;

/**
 * ExceptionContext
 * 
 * Immutable value object that encapsulates exception information along with
 * request details and timing data. This class provides a structured way to
 * capture and transport exception context throughout the application.
 * 
 * The class maintains three core pieces of information:
 * - exceptions: An array of exception data (stack of exceptions)
 * - request: Request-related information at the time of the exception
 * - timestamp: Unix timestamp when the exception occurred
 * 
 * This is an immutable object - all modifications return a new instance.
 */
final class ExceptionContext
{
    /**
     * Creates a new ExceptionContext instance.
     * 
     * @param array $exceptions Array of exception data, where the first element
     *                          (index 0) represents the primary exception
     * @param array $request    Request context data (e.g., URL, method, headers)
     * @param int   $timestamp  Unix timestamp when the exception was captured
     */
    public function __construct(
        private readonly array $exceptions,
        private readonly array $request,
        private readonly int $timestamp,
    ) {}

    /**
     * Returns the full array of exceptions.
     * 
     * @return array The exceptions array, with index 0 being the primary exception
     */
    public function exceptions(): array
    {
        return $this->exceptions;
    }

    /**
     * Returns the request context data.
     * 
     * @return array The request information captured at exception time
     */
    public function request(): array
    {
        return $this->request;
    }

    /**
     * Returns the timestamp when the exception occurred.
     * 
     * @return int Unix timestamp
     */
    public function timestamp(): int
    {
        return $this->timestamp;
    }

    /**
     * Returns the HTTP status code from the primary exception.
     * 
     * Extracts the 'code' value from the first exception in the exceptions array.
     * Defaults to 500 (Internal Server Error) if no code is present.
     * 
     * @return int The exception code, or 500 if not set
     */
    public function code(): int
    {
        if (!isset($this->exceptions[0]['code'])) {
            return 500;
        }

        return (int) $this->exceptions[0]['code'];
    }

    /**
     * Returns the error message from the primary exception.
     * 
     * Extracts the 'message' value from the first exception in the exceptions array.
     * Provides a generic fallback message if no message is present.
     * 
     * @return string The exception message, or a default message if not set
     */
    public function message(): string
    {
        if (!isset($this->exceptions[0]['message'])) {
            return 'An unknown error occurred.';
        }

        return (string) $this->exceptions[0]['message'];
    }

    /**
     * Creates a new instance with modified values.
     * 
     * This method implements the immutable modifier pattern, allowing you to
     * create a new ExceptionContext with some properties changed while keeping
     * others intact. Only the properties specified in $overrides are changed.
     * 
     * Example:
     * ```php
     * $newContext = $context->with(['timestamp' => time()]);
     * ```
     * 
     * @param array $overrides Associative array with keys 'exceptions', 'request',
     *                         and/or 'timestamp' to override existing values
     * 
     * @return self A new ExceptionContext instance with the specified overrides
     */
    public function with(array $overrides): self
    {
        return new self(
            $overrides['exceptions'] ?? $this->exceptions,
            $overrides['request']    ?? $this->request,
            $overrides['timestamp']  ?? $this->timestamp,
        );
    }
}
