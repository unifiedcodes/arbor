<?php

namespace Arbor\auth\authentication\issuers;

use Arbor\auth\authentication\Token;
use Arbor\auth\authentication\TokenIssuerInterface;
use InvalidArgumentException;

/**
 * JWTIssuer
 *
 * Issues and parses JSON Web Tokens (JWT) using EdDSA (Ed25519) cryptographic signing.
 * This class implements the TokenIssuerInterface and provides secure token generation
 * and verification capabilities.
 */
final class JWTIssuer implements TokenIssuerInterface
{

    /**
     * Constructor
     *
     * @param string $signingKey The Ed25519 private key used to sign tokens
     * @param string|null $kid Optional Key ID to include in the JWT header
     * @param int|null $ttl Optional Time-to-live in seconds for issued tokens
     *
     * @throws InvalidArgumentException If the signing key is not a valid Ed25519 private key
     */
    public function __construct(
        private string $signingKey,
        private ?string $kid = null,
        private ?int $ttl = null
    ) {
        if (strlen($this->signingKey) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
            throw new InvalidArgumentException('Invalid Ed25519 private key.');
        }
    }


    /**
     * Issues a new JWT token
     *
     * Generates a new JSON Web Token with the provided claims and options. The token
     * includes standard claims like issued-at time (iat) and JWT ID (jti), along with
     * an optional expiration time (exp) if a TTL is specified.
     *
     * @param array $claims Additional claims to include in the token payload
     * @param array $options Options for token generation. Supports 'ttl' to override instance TTL
     *
     * @return Token The generated token object
     */
    public function issue(array $claims = [], array $options = []): Token
    {
        $issuedAt = time();

        $payload = array_merge($claims, [
            'iat' => $issuedAt,
            'jti' => $this->base64UrlEncode(random_bytes(16)),
        ]);

        $ttl = $options['ttl'] ?? $this->ttl;

        if ($ttl !== null) {
            $payload['exp'] = $issuedAt + (int) $ttl;
        }

        $header = [
            'typ' => 'JWT',
            'alg' => 'EdDSA',
        ];

        if ($this->kid !== null) {
            $header['kid'] = $this->kid;
        }

        $jwt = $this->sign($header, $payload);

        return new Token(
            type: 'jwt',
            value: $jwt,
            id: $payload['jti'],
            claims: $payload,
            metadata: [
                'header' => $header,
            ],
            expiresAt: $payload['exp'] ?? null
        );
    }


    /**
     * Parses and verifies a JWT token
     *
     * Decodes and validates a raw JWT string using the provided verification key.
     * Ensures the token is properly formatted and has a valid signature.
     *
     * @param string $rawToken The raw JWT string to parse
     * @param string|null $verficationKey The Ed25519 public key used to verify the token signature
     *
     * @return Token The parsed and verified token object
     *
     * @throws InvalidArgumentException If verification key is missing, token is malformed, or signature is invalid
     */
    public function parse(string $rawToken, ?string $verficationKey = null): Token
    {
        if (!$verficationKey) {
            throw new InvalidArgumentException("Verification Key is required to parse JWT");
        }

        [$header, $payload] = $this->verify($verficationKey, $rawToken);

        if (!isset($payload['jti'])) {
            throw new InvalidArgumentException('JWT missing jti.');
        }

        return new Token(
            type: 'jwt',
            value: $rawToken,
            id: $payload['jti'],
            claims: $payload,
            metadata: [
                'header' => $header,
            ],
            expiresAt: $payload['exp'] ?? null
        );
    }


    /**
     * Gets the default TTL (Time-To-Live) for tokens
     *
     * @return int|null The configured TTL in seconds, or null if not set
     */
    public function getExpiry(): ?int
    {
        return $this->ttl;
    }


    /**
     * Signs a JWT header and payload
     *
     * Creates a complete JWT by encoding the header and payload, signing the combined
     * input with the private key, and constructing the final token string.
     *
     * @param array $header The JWT header containing algorithm and optional key ID
     * @param array $payload The JWT payload containing claims
     *
     * @return string The complete signed JWT token
     */
    private function sign(array $header, array $payload): string
    {
        $encodedHeader  = $this->base64UrlEncode(
            json_encode($header, JSON_THROW_ON_ERROR)
        );

        $encodedPayload = $this->base64UrlEncode(
            json_encode($payload, JSON_THROW_ON_ERROR)
        );

        $signingInput = $encodedHeader . '.' . $encodedPayload;

        $signature = sodium_crypto_sign_detached(
            $signingInput,
            $this->signingKey
        );

        $encodedSignature = $this->base64UrlEncode($signature);

        return $signingInput . '.' . $encodedSignature;
    }


    /**
     * Verifies a JWT signature and decodes its contents
     *
     * Validates the structure of the JWT, verifies the EdDSA signature using the
     * provided public key, and decodes the header and payload.
     *
     * @param string $verficationKey The Ed25519 public key for signature verification
     * @param string $jwt The complete JWT token string to verify
     *
     * @return array An array containing [header, payload] decoded from the JWT
     *
     * @throws InvalidArgumentException If JWT is malformed, algorithm is unsupported, or signature is invalid
     */
    private function verify($verficationKey, string $jwt): array
    {
        $parts = explode('.', $jwt);

        if (count($parts) !== 3) {
            throw new InvalidArgumentException('Malformed JWT.');
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;

        $header = json_decode(
            $this->base64UrlDecode($encodedHeader),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $payload = json_decode(
            $this->base64UrlDecode($encodedPayload),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        if (($header['alg'] ?? null) !== 'EdDSA') {
            throw new InvalidArgumentException('Unsupported JWT alg.');
        }

        $signature = $this->base64UrlDecode($encodedSignature);

        $signingInput = $encodedHeader . '.' . $encodedPayload;

        if (!sodium_crypto_sign_verify_detached(
            $signature,
            $signingInput,
            $verficationKey
        )) {
            throw new InvalidArgumentException('Invalid JWT signature.');
        }

        return [$header, $payload];
    }


    /**
     * Encodes data using base64url encoding
     *
     * Converts binary data to base64url format as per RFC 4648, which replaces
     * '+' and '/' with '-' and '_', and strips padding characters.
     *
     * @param string $data The data to encode
     *
     * @return string The base64url-encoded string
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(
            strtr(base64_encode($data), '+/', '-_'),
            '='
        );
    }


    /**
     * Decodes a base64url-encoded string
     *
     * Converts base64url-encoded data back to binary form by replacing '-' and '_'
     * with '+' and '/', and adding back padding if necessary.
     *
     * @param string $data The base64url-encoded string to decode
     *
     * @return string The decoded binary data
     */
    private function base64UrlDecode(string $data): string
    {
        return base64_decode(
            strtr($data, '-_', '+/'),
            true
        );
    }
}
