<?php

namespace Arbor\auth\authentication;

use Arbor\auth\authentication\TokenStoreInterface;

/**
 * NullTokenStore
 *
 * A null implementation of the TokenStoreInterface that provides no-op implementations
 * for all token storage and management operations. This is useful for scenarios where
 * token persistence and validation are not required or as a default fallback implementation.
 *
 * @package Arbor\auth\authentication
 */
class NullTokenStore implements TokenStoreInterface
{
    /**
     * Get required claims for tokens
     *
     * Returns an empty array, indicating no claims are required.
     *
     * @return array An empty array of required claims
     */
    public function requireClaims(): array
    {
        return [];
    }

    /**
     * Save a token
     *
     * This is a no-op implementation that performs no action.
     *
     * @param Token $token The token to save
     * @return void
     */
    public function save(Token $token): void {}

    /**
     * Retrieve a token
     *
     * Always returns null, indicating that no tokens are stored or retrievable.
     *
     * @param Token $token The token to retrieve
     * @return ?Token Always returns null
     */
    public function retrieve(Token $token): ?Token
    {
        return null;
    }

    /**
     * Validate a token
     *
     * This is a no-op implementation that performs no validation.
     *
     * @param Token $token The token to validate
     * @return void
     */
    public function validate(Token $token): void
    {
        // no-op
    }

    /**
     * Revoke a token
     *
     * This is a no-op implementation that performs no revocation.
     *
     * @param string|int $tokenId The ID of the token to revoke
     * @return void
     */
    public function revoke(string|int $tokenId): void
    {
        // no-op
    }
}
