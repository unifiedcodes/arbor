<?php

namespace Arbor\auth;

use Arbor\auth\TokenStoreInterface;
use Arbor\auth\Token;
use RuntimeException;

/**
 * AuthPolicy
 *
 * Handles token validation according to configured policies.
 * Supports both store-level validation and expiry policy enforcement.
 */
class AuthPolicy
{
    /**
     * Constructor
     *
     * @param bool $hasExpiryPolicy Whether token expiry validation should be enforced
     * @param TokenStoreInterface|null $store Optional token store for additional validation
     */
    public function __construct(
        private bool $hasExpiryPolicy,
        private ?TokenStoreInterface $store = null,
    ) {}

    /**
     * Validate a token against configured policies
     *
     * Performs validation in the following order:
     * 1. Store-level validation (if a store is configured)
     * 2. Expiry policy validation (if expiry policy is enabled)
     *
     * @param Token $token The token to validate
     * @return void
     * @throws RuntimeException If the token fails validation (e.g., expired token)
     */
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
