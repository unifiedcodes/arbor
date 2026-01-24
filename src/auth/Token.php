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
}
