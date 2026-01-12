<?php

namespace Arbor\exception;

final class ExceptionContext
{
    public function __construct(
        private readonly array $exceptions,
        private readonly array $request,
        private readonly int $timestamp,
    ) {}

    public function exceptions(): array
    {
        return $this->exceptions;
    }

    public function request(): array
    {
        return $this->request;
    }

    public function timestamp(): int
    {
        return $this->timestamp;
    }

    public function code(): int
    {
        if (!isset($this->exceptions[0]['code'])) {
            return 500;
        }

        return (int) $this->exceptions[0]['code'];
    }

    public function message(): string
    {
        if (!isset($this->exceptions[0]['message'])) {
            return 'An unknown error occurred.';
        }

        return (string) $this->exceptions[0]['message'];
    }

    /**
     * Immutable modifier
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
