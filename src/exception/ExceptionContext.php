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
