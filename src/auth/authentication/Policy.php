<?php

namespace Arbor\auth\authentication;

use Arbor\auth\authentication\Registry;
use Arbor\auth\authentication\Token;
use RuntimeException;

/**
 * AuthPolicy
 *
 * Handles token validation according to configured policies.
 * Supports both store-level validation and expiry policy enforcement.
 */
class Policy
{
    /**
     * Constructor
     *
     * @param bool $hasExpiryPolicy Whether token expiry validation should be enforced
     * @param Registry $registry token registry for persistance related validation
     */
    public function __construct(
        private bool $hasExpiryPolicy,
        private Registry $registry,
    ) {}

    /**
     * Validate a token against configured policies
     *
     *
     * @param Token $token The token to validate
     * @return void
     * @throws RuntimeException If the token fails validation (e.g., expired token)
     */
    public function validate(Token $token): void
    {
        $this->registry->validate($token);

        if (
            $this->hasExpiryPolicy !== null
            && $token->isExpired()
        ) {
            throw new RuntimeException("Token is expired");
        }
    }
}
