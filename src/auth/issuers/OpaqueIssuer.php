<?php

namespace Arbor\Auth\issuers;

use Arbor\Auth\Token;
use Arbor\Auth\TokenIssuerInterface;
use InvalidArgumentException;

/**
 * OpaqueIssuer
 *
 * Issues and parses opaque tokens for authentication. Opaque tokens are
 * random, non-self-contained tokens that require backend storage for validation.
 * This implementation generates cryptographically secure random tokens and
 * optionally assigns expiration times to them.
 *
 * @final
 */
final class OpaqueIssuer implements TokenIssuerInterface
{
    /**
     * Constructor
     *
     * @param int|null $ttl The default time-to-live in seconds for issued tokens.
     *                       If null, tokens do not expire by default.
     * @param int $tokenByteLength The number of random bytes to generate for tokens.
     *                             Defaults to 32 bytes (256 bits).
     * @param string $hashAlgo The hashing algorithm to use for generating token IDs.
     *                         Defaults to 'sha256'.
     */
    public function __construct(
        private ?int $ttl = null,
        private int $tokenByteLength = 32,
        private string $hashAlgo = 'sha256'
    ) {}

    /**
     * Issues a new opaque token with the given claims
     *
     * Generates a new random opaque token and wraps it in a Token object with
     * the provided claims. The issued-at timestamp ('iat') is automatically added.
     * If a TTL is specified, an expiration timestamp ('exp') is also included.
     *
     * @param array $claims Additional claims to include in the token payload.
     *                       Defaults to an empty array.
     * @param array $options Configuration options for this token issuance.
     *                       Supports 'ttl' to override the default TTL for this token.
     *                       Defaults to an empty array.
     *
     * @return Token The issued opaque token with type 'opaque', generated value,
     *               computed token ID, claims payload, and expiration timestamp.
     */
    public function issue(array $claims = [], array $options = []): Token
    {
        $issuedAt = time();

        $payload = array_merge($claims, [
            'iat' => $issuedAt,
        ]);

        $ttl = $options['ttl'] ?? $this->ttl;

        if ($ttl !== null) {
            $payload['exp'] = $issuedAt + (int) $ttl;
        }

        $token = $this->createToken();
        $tokenId = $this->tokenId($token);

        return new Token(
            type: 'opaque',
            value: $token,
            id: $tokenId,
            claims: $payload,
            expiresAt: $payload['exp'] ?? null
        );
    }

    /**
     * Parses and validates a raw opaque token string
     *
     * Converts a raw token string into a Token object. Validates that the token
     * is not empty and meets the minimum length requirement (32 characters).
     * Does not verify the token against stored data (validation is caller's responsibility).
     *
     * @param string $rawToken The raw token string to parse.
     *
     * @return Token The parsed opaque token with type 'opaque', original value,
     *               computed token ID, empty claims array, and null expiration.
     *
     * @throws InvalidArgumentException If the token is empty or shorter than 32 characters.
     */
    public function parse(string $rawToken): Token
    {
        if ($rawToken === '' || strlen($rawToken) < 32) {
            throw new InvalidArgumentException('Invalid opaque token.');
        }

        return new Token(
            type: 'opaque',
            value: $rawToken,
            id: $this->tokenId($rawToken),
            claims: [],
            expiresAt: null
        );
    }

    /**
     * Gets the default expiration time-to-live for issued tokens
     *
     * Returns the TTL in seconds that was set during initialization.
     * A null return value indicates tokens do not expire by default.
     *
     * @return int|null The default TTL in seconds, or null if tokens don't expire.
     */
    public function getExpiry(): ?int
    {
        return $this->ttl;
    }

    /**
     * Creates a new random opaque token
     *
     * Generates a cryptographically secure random byte string and encodes it
     * using URL-safe base64 encoding for safe transmission and storage.
     *
     * @return string The newly created opaque token string.
     *
     * @access private
     */
    private function createToken()
    {
        $randomBytes = random_bytes($this->tokenByteLength);
        $token = $this->base64UrlEncode($randomBytes);

        return $token;
    }

    /**
     * Generates a token ID by hashing the token
     *
     * Hashes the provided token using the configured algorithm and encodes
     * the hash with URL-safe base64 encoding. This ID is used for indexing
     * and lookup purposes without exposing the token itself.
     *
     * @param string $token The token string to hash.
     *
     * @return string The hashed and encoded token ID.
     *
     * @access private
     */
    private function tokenId(string $token): string
    {
        $hash = hash($this->hashAlgo, $token, true);
        return $this->base64UrlEncode($hash);
    }

    /**
     * Encodes data using URL-safe base64 encoding
     *
     * Converts data to base64 and replaces URL-unsafe characters (+/ become -_).
     * Removes padding characters (=) to create a compact URL-safe string.
     *
     * @param string $data The data to encode.
     *
     * @return string The URL-safe base64 encoded string.
     *
     * @access private
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(
            strtr(base64_encode($data), '+/', '-_'),
            '='
        );
    }
}
