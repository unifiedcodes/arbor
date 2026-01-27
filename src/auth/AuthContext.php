<?php

namespace Arbor\auth;

use Arbor\auth\authentication\Registry;
use Arbor\auth\authentication\Token;
use RuntimeException;


/**
 * Encapsulates authentication context information including token details and attributes.
 * 
 * This immutable class provides access to token data, token validation, revocation capabilities,
 * and a mechanism for storing arbitrary attributes associated with the authentication session.
 */
final class AuthContext
{
    /**
     * Initializes a new AuthContext instance.
     * 
     * @param Token $token The authentication token
     * @param Registry $store Optional token store for revocation operations
     * @param array $attributes Optional key-value pairs of custom attributes
     */
    public function __construct(
        private Token $token,
        private readonly Registry $registry,
        private readonly array $attributes = []
    ) {}

    /**
     * Retrieves the authentication token.
     * 
     * @return Token The token object
     */
    public function token(): Token
    {
        return $this->token;
    }

    /**
     * Retrieves the unique identifier of the token.
     * 
     * @return string The token ID
     */
    public function tokenId(): string
    {
        return $this->token->id();
    }

    /**
     * Retrieves the type of the token.
     * 
     * @return string The token type
     */
    public function tokenType(): string
    {
        return $this->token->type();
    }

    /**
     * Retrieves all claims associated with the token.
     * 
     * @return array The token claims as key-value pairs
     */
    public function claims(): array
    {
        return $this->token->claims();
    }

    /**
     * Retrieves the expiration timestamp of the token.
     * 
     * @return int|null The Unix timestamp when the token expires, or null if it never expires
     */
    public function expiresAt(): ?int
    {
        return $this->token->expiresAt();
    }

    /**
     * Determines whether the token has expired.
     * 
     * @return bool True if the token has an expiration time and the current time has passed it, false otherwise
     */
    public function isExpired(): bool
    {
        return $this->token->expiresAt() !== null
            && time() > $this->token->expiresAt();
    }

    /**
     * Revokes the token using the configured token store.
     * 
     * @return void
     * @throws RuntimeException If no token store is configured
     */
    public function revoke(): void
    {
        $this->registry->revoke($this->token());
    }

    /**
     * Returns a new AuthContext instance with an additional attribute.
     * 
     * This method implements immutability by creating a new instance with the merged attributes
     * while preserving the original instance unchanged.
     * 
     * @param string $key The attribute key
     * @param mixed $value The attribute value
     * @return self A new AuthContext instance with the added attribute
     */
    public function withAttribute(string $key, mixed $value): self
    {
        return new self(
            token: $this->token,
            registry: $this->registry,
            attributes: array_merge($this->attributes, [$key => $value])
        );
    }

    /**
     * Retrieves a specific attribute by key.
     * 
     * @param string $key The attribute key
     * @param mixed $default The default value to return if the key does not exist
     * @return mixed The attribute value, or the default if not found
     */
    public function attribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    /**
     * Retrieves all attributes.
     * 
     * @return array The complete array of attributes
     */
    public function attributes(): array
    {
        return $this->attributes;
    }
}
