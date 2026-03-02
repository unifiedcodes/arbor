<?php

namespace Arbor\auth\authentication;

use Arbor\auth\authentication\Keeper;
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
     * @param Keeper $keeper token Keeper for persistance related validation
     */
    public function __construct(
        private bool $hasExpiryPolicy,
        private Keeper $keeper,
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
        $this->keeper->validate($token);

        if (
            $this->hasExpiryPolicy
            && $token->isExpired()
        ) {
            throw new RuntimeException("Token is expired");
        }
    }
}
