<?php

namespace Arbor\auth;


use JsonSerializable;


final class Token implements JsonSerializable
{
    public function __construct(
        private readonly string $type,
        private readonly string $value,
        private readonly string $id,
        private readonly array $claims = [],
        private readonly array $metadata = [],
        private readonly ?int $expiresAt = null,
    ) {
        $this->value     = $value;
        $this->id        = $id;
        $this->claims    = $claims;
        $this->expiresAt = $expiresAt;
        $this->type      = $type;
    }


    public function value(): string
    {
        return $this->value;
    }


    public function id(): string
    {
        return $this->id;
    }


    public function claims(): array
    {
        return $this->claims;
    }


    public function expiresAt(): ?int
    {
        return $this->expiresAt;
    }


    public function type(): string
    {
        return $this->type;
    }


    public function meta(?string $path = null): mixed
    {
        if ($path === null) {
            return $this->metadata;
        }

        return value_at($this->metadata, $path);
    }


    public function isExpired(?int $now = null): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }

        $now = $now ?? time();

        return $now >= $this->expiresAt;
    }


    public function jsonSerialize(): array
    {
        return [
            'id'         => $this->id,
            'claims'     => $this->claims,
            'expires_at' => $this->expiresAt,
            'type'       => $this->type,
        ];
    }


    public function withMeta(array $meta): self
    {
        $clone = clone $this;
        $clone->metadata = $meta;
        return $clone;
    }


    public function withMergedMeta(array $meta): self
    {
        $clone = clone $this;
        $clone->metadata = array_replace_recursive(
            $this->metadata,
            $meta
        );
        return $clone;
    }
}
