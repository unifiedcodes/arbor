<?php

namespace Arbor\auth;


use JsonSerializable;


/**
 * Represents an authentication token with claims, metadata, and expiration information.
 *
 * This class provides a immutable-style interface for managing authentication tokens,
 * including token validation, claim/metadata retrieval, and fluent mutation methods.
 *
 * @final
 */
final class Token implements JsonSerializable
{
    /**
     * Creates a new Token instance.
     *
     * @param string $type The type of token (e.g., 'bearer', 'jwt')
     * @param string $value The token value/string
     * @param string $id The unique identifier for the token
     * @param array $claims Token claims data (default: empty array)
     * @param array $metadata Additional metadata associated with the token (default: empty array)
     * @param int|null $expiresAt Unix timestamp when the token expires, or null if never expires
     */
    public function __construct(
        private string $type,
        private string $value,
        private string $id,
        private array $claims = [],
        private array $metadata = [],
        private ?int $expiresAt = null,
    ) {}


    /**
     * Returns the token value/string.
     *
     * @return string The token value
     */
    public function value(): string
    {
        return $this->value;
    }


    /**
     * Returns the unique identifier of the token.
     *
     * @return string The token ID
     */
    public function id(): string
    {
        return $this->id;
    }


    /**
     * Retrieves token claims or a specific claim by key.
     *
     * @param string|null $key Optional key to retrieve a specific claim value
     * @return mixed The claims array, a specific claim value, or null if key not found
     */
    public function claims(?string $key = null): mixed
    {
        return value_at($this->claims, $key);
    }


    /**
     * Returns the Unix timestamp when the token expires.
     *
     * @return int|null The expiration timestamp, or null if the token never expires
     */
    public function expiresAt(): ?int
    {
        return $this->expiresAt;
    }


    /**
     * Returns the token type.
     *
     * @return string The type of token (e.g., 'bearer', 'jwt')
     */
    public function type(): string
    {
        return $this->type;
    }


    /**
     * Retrieves metadata or a specific metadata value by path.
     *
     * @param string|null $path Optional path to retrieve a specific metadata value
     * @return mixed The metadata array, a specific value, or null if path not found
     */
    public function meta(?string $path = null): mixed
    {
        return value_at($this->metadata, $path);
    }


    /**
     * Determines whether the token has expired.
     *
     * @param int|null $now Optional Unix timestamp to check expiration against.
     *                       If not provided, the current time is used.
     * @return bool True if the token has expired, false otherwise.
     *              Returns false if the token has no expiration date.
     */
    public function isExpired(?int $now = null): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }

        $now = $now ?? time();

        return $now >= $this->expiresAt;
    }


    /**
     * Returns data suitable for JSON serialization.
     *
     * @return array Associative array containing id, claims, expires_at, and type
     */
    public function jsonSerialize(): array
    {
        return [
            'id'         => $this->id,
            'claims'     => $this->claims,
            'expires_at' => $this->expiresAt,
            'type'       => $this->type,
        ];
    }


    /**
     * Returns a new Token instance with the metadata replaced.
     *
     * @param array $meta The new metadata array
     * @return self A cloned Token with updated metadata
     */
    public function withMeta(array $meta): self
    {
        $clone = clone $this;
        $clone->metadata = $meta;
        return $clone;
    }


    /**
     * Returns a new Token instance with metadata recursively merged.
     *
     * @param array $meta The metadata to merge into existing metadata
     * @return self A cloned Token with merged metadata
     */
    public function withMergedMeta(array $meta): self
    {
        $clone = clone $this;
        $clone->metadata = array_replace_recursive(
            $this->metadata,
            $meta
        );
        return $clone;
    }

    /**
     * Returns a new Token instance with the claims replaced.
     *
     * @param array $claims The new claims array
     * @return self A cloned Token with updated claims
     */
    public function withClaims(array $claims): self
    {
        $clone = clone $this;
        $clone->claims = $claims;
        return $clone;
    }


    /**
     * Returns a new Token instance with claims recursively merged.
     *
     * @param array $claims The claims to merge into existing claims
     * @return self A cloned Token with merged claims
     */
    public function withMergedClaims(array $claims): self
    {
        $clone = clone $this;
        $clone->claims = array_replace_recursive(
            $this->claims,
            $claims
        );
        return $clone;
    }

    /**
     * Returns a new Token instance with an updated expiration timestamp.
     *
     * @param int|null $expiresAt Unix timestamp for new expiration, or null for no expiration
     * @return self A cloned Token with updated expiration
     */
    public function withExpiry(?int $expiresAt): self
    {
        $clone = clone $this;
        $clone->expiresAt = $expiresAt;
        return $clone;
    }

    /**
     * Returns the token value as a string.
     *
     * Allows the Token instance to be cast to or used as a string,
     * returning the underlying token value. This enables convenient
     * string interpolation and type juggling.
     *
     * @return string The token value
     */
    public function __toString(): string
    {
        return $this->value;
    }
}
