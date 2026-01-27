<?php

namespace Arbor\auth\authentication;

use Arbor\auth\authentication\TokenStoreInterface;

class NullTokenStore implements TokenStoreInterface
{
    public function requireClaims(): array
    {
        return [];
    }

    public function save(Token $token): void {}

    public function retrieve(Token $token): ?Token
    {
        return null;
    }

    public function validate(Token $token): void
    {
        // no-op
    }

    public function revoke(string|int $tokenId): void
    {
        // no-op
    }
}
