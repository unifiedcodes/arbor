<?php

namespace Arbor\auth;

use Arbor\auth\TokenStoreInterface;
use Arbor\auth\TokenIssuerInterface;
use Arbor\auth\Token;
use RuntimeException;


class AuthPolicy
{
    public function __construct(
        private bool $hasExpiryPolicy,
        private ?TokenStoreInterface $store = null,
    ) {}

    public function validate(Token $token): void
    {
        if ($this->store) {
            // check store level validation
            $this->store->validate($token);
        }

        if (
            $this->hasExpiryPolicy !== null
            && $token->isExpired()
        ) {
            throw new RuntimeException("Token is expired");
        }
    }
}
