<?php

namespace Arbor\auth;


use JsonSerializable;


final class Token implements JsonSerializable
{
    public function __construct(
        private string $type,
        private string $value,
        private string $id,
        private array $claims = [],
        private array $metadata = [],
        private ?int $expiresAt = null,
    ) {}


    public function value(): string
    {
        return $this->value;
    }


    public function id(): string
    {
        return $this->id;
    }


    public function claims(?string $key = null): mixed
    {
        return value_at($this->claims, $key);
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

    public function withClaims(array $claims): self
    {
        $clone = clone $this;
        $clone->claims = $claims;
        return $clone;
    }


    public function withMergedClaims(array $claims): self
    {
        $clone = clone $this;
        $clone->claims = array_replace_recursive(
            $this->claims,
            $claims
        );
        return $clone;
    }

    public function withExpiry(?int $expiresAt): self
    {
        $clone = clone $this;
        $clone->expiresAt = $expiresAt;
        return $clone;
    }
}
