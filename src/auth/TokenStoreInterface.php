<?php

namespace Arbor\auth;

/**
 * Interface TokenStoreInterface
 *
 * Defines the contract for token storage and management operations.
 * Implementations of this interface handle storing, retrieving, validating,
 * and revoking tokens with support for required claims verification.
 *
 * @package Arbor\auth
 */
interface TokenStoreInterface
{
    /**
     * Retrieves the list of claims required for token validation.
     *
     * @return array An associative array of required claim names and their specifications.
     */
    public function requireClaims(): array;

    /**
     * Saves a token to the store.
     *
     * @param Token $token The token object to be saved.
     *
     * @return void
     */
    public function save(Token $token): void;

    /**
     * Retrieves a stored token from the store.
     *
     * @param Token $token The token object to retrieve from the store.
     *
     * @return Token The retrieved token object.
     */
    public function retrieve(Token $token): Token;

    /**
     * Validates a token against stored rules and required claims.
     *
     * @param Token $token The token object to validate.
     *
     * @return void
     */
    public function validate(Token $token): void;

    /**
     * Revokes a token by its identifier, preventing further use.
     *
     * @param string $tokenId The unique identifier of the token to revoke.
     *
     * @return void
     */
    public function revoke(string $tokenId): void;
}
