<?php

namespace Arbor\auth;

interface TokenStoreInterface
{
    public function requireClaims(): array;

    public function save(Token $token): void;

    public function retrieve(Token $token): Token;

    public function validate(Token $token): void;

    public function revoke(string $tokenId): void;
}
