<?php

namespace Arbor\auth;


use Arbor\auth\Token;
use RuntimeException;


class AuthContext
{
    public function __construct(
        private Token $token,
        private readonly ?TokenStoreInterface $store = null,
        private readonly array $attributes = []
    ) {}

    public function token(): Token
    {
        return $this->token;
    }

    public function tokenId(): string
    {
        return $this->token->id();
    }

    public function tokenType(): string
    {
        return $this->token->type();
    }

    public function claims(): array
    {
        return $this->token->claims();
    }

    public function expiresAt(): ?int
    {
        return $this->token->expiresAt();
    }

    public function isExpired(): bool
    {
        return $this->token->expiresAt() !== null
            && time() > $this->token->expiresAt();
    }

    public function revoke(): void
    {
        if (!$this->store) {
            throw new RuntimeException('Token store not available.');
        }

        $this->store->revoke($this->tokenId());
    }


    public function withAttribute(string $key, mixed $value): self
    {
        return new self(
            token: $this->token,
            store: $this->store,
            attributes: array_merge($this->attributes, [$key => $value])
        );
    }


    public function attribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }


    public function attributes(): array
    {
        return $this->attributes;
    }
}
