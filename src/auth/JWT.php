<?php

namespace Arbor\auth;


use Exception;
use InvalidArgumentException;

/**
 * JSON Web Token (JWT) implementation for PHP
 * 
 * This class provides functionality to create, validate, and decode JWT tokens
 * using RS256 (RSA Signature with SHA-256) algorithm. It supports standard
 * JWT claims and provides utilities for token expiration checking.
 * 
 * @package Arbor\auth
 */
class JWT
{
    /**
     * Private key resource for signing tokens
     */
    private $privateKey;

    /**
     * Public key resource for verifying tokens
     */
    private $publicKey;

    /**
     * Algorithm used for signing (currently supports RS256)
     * @var string
     */
    private $algorithm = 'RS256';

    /**
     * Constructor - Initialize JWT with optional key file paths
     * 
     * @param string|null $privateKeyPath Path to private key file for signing tokens
     * @param string|null $publicKeyPath Path to public key file for verifying tokens
     * 
     * @throws Exception If key files exist but cannot be read or are invalid
     */
    public function __construct($privateKeyPath = null, $publicKeyPath = null)
    {
        if ($privateKeyPath && file_exists($privateKeyPath)) {
            $this->privateKey = openssl_pkey_get_private(file_get_contents($privateKeyPath));
        }

        if ($publicKeyPath && file_exists($publicKeyPath)) {
            $this->publicKey = openssl_pkey_get_public(file_get_contents($publicKeyPath));
        }
    }

    /**
     * Set the private key for signing tokens
     * 
     * @param string $privateKey Private key as string or OpenSSL key resource
     * 
     * @throws InvalidArgumentException If the provided key is invalid
     */
    public function setPrivateKey($privateKey)
    {
        if (is_string($privateKey)) {
            $this->privateKey = openssl_pkey_get_private($privateKey);
        } else {
            $this->privateKey = $privateKey;
        }

        if (!$this->privateKey) {
            throw new InvalidArgumentException('Invalid private key');
        }
    }

    /**
     * Set the public key for verifying tokens
     * 
     * @param string $publicKey Public key as string or OpenSSL key resource
     * 
     * @throws InvalidArgumentException If the provided key is invalid
     */
    public function setPublicKey($publicKey)
    {
        if (is_string($publicKey)) {
            $this->publicKey = openssl_pkey_get_public($publicKey);
        } else {
            $this->publicKey = $publicKey;
        }

        if (!$this->publicKey) {
            throw new InvalidArgumentException('Invalid public key');
        }
    }

    /**
     * Create a JWT token
     * 
     * Generates a signed JWT token with the provided payload. Automatically adds
     * standard claims (iat, exp, nbf) if not present in the payload.
     * 
     * @param array $payload The claims to include in the token
     * @param int $expiresIn Token expiration time in seconds (default: 3600 = 1 hour)
     *                       Set to 0 or negative to create non-expiring token
     * 
     * @return string The complete JWT token (header.payload.signature)
     * 
     * @throws \RuntimeException If private key is not set or signing fails
     * 
     * Standard claims that are automatically added:
     * - iat (issued at): Current timestamp
     * - exp (expires): Current timestamp + expiresIn (if expiresIn > 0)
     * - nbf (not before): Current timestamp
     */
    public function create(array $payload, $expiresIn = 3600)
    {
        if (!$this->privateKey) {
            throw new \RuntimeException('Private key not set');
        }

        $header = [
            'typ' => 'JWT',
            'alg' => $this->algorithm
        ];

        // Add standard claims if not present
        $now = time();
        if (!isset($payload['iat'])) {
            $payload['iat'] = $now;
        }
        if (!isset($payload['exp']) && $expiresIn > 0) {
            $payload['exp'] = $now + $expiresIn;
        }
        if (!isset($payload['nbf'])) {
            $payload['nbf'] = $now;
        }

        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));

        $signatureInput = $headerEncoded . '.' . $payloadEncoded;
        $signature = '';

        if (!openssl_sign($signatureInput, $signature, $this->privateKey, OPENSSL_ALGO_SHA256)) {
            throw new \RuntimeException('Unable to sign token');
        }

        $signatureEncoded = $this->base64UrlEncode($signature);

        return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
    }

    /**
     * Validate and decode a JWT token
     * 
     * Performs complete validation including signature verification and
     * claim validation (exp, nbf). Returns the decoded payload if valid.
     * 
     * @param string $token The JWT token to validate
     * 
     * @return array The decoded payload claims
     * 
     * @throws \RuntimeException If public key is not set
     * @throws InvalidArgumentException If token format is invalid, signature is invalid,
     *                                   algorithm doesn't match, token is expired,
     *                                   or token is not yet valid
     */
    public function validate($token)
    {
        if (!$this->publicKey) {
            throw new \RuntimeException('Public key not set');
        }

        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new InvalidArgumentException('Invalid token format');
        }

        list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;

        // Decode header
        $header = json_decode($this->base64UrlDecode($headerEncoded), true);
        if (!$header || $header['alg'] !== $this->algorithm) {
            throw new InvalidArgumentException('Invalid algorithm or header');
        }

        // Verify signature
        $signatureInput = $headerEncoded . '.' . $payloadEncoded;
        $signature = $this->base64UrlDecode($signatureEncoded);

        if (!openssl_verify($signatureInput, $signature, $this->publicKey, OPENSSL_ALGO_SHA256)) {
            throw new InvalidArgumentException('Invalid signature');
        }

        // Decode payload
        $payload = json_decode($this->base64UrlDecode($payloadEncoded), true);
        if (!$payload) {
            throw new InvalidArgumentException('Invalid payload');
        }

        // Check expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            throw new InvalidArgumentException('Token has expired');
        }

        // Check not before
        if (isset($payload['nbf']) && $payload['nbf'] > time()) {
            throw new InvalidArgumentException('Token not yet valid');
        }

        return $payload;
    }

    /**
     * Decode a token without validation (useful for debugging)
     * 
     * Decodes the JWT token structure without performing signature verification
     * or claim validation. Use this method only for debugging purposes or when
     * you need to inspect token contents without validation.
     * 
     * @param string $token The JWT token to decode
     * 
     * @return array Associative array with 'header' and 'payload' keys
     * 
     * @throws InvalidArgumentException If token format is invalid
     */
    public function decode($token)
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new InvalidArgumentException('Invalid token format');
        }

        $header = json_decode($this->base64UrlDecode($parts[0]), true);
        $payload = json_decode($this->base64UrlDecode($parts[1]), true);

        return [
            'header' => $header,
            'payload' => $payload
        ];
    }

    /**
     * Check if a token is expired
     * 
     * Determines if a token has expired based on the 'exp' claim.
     * Does not perform signature validation.
     * 
     * @param string $token The JWT token to check
     * 
     * @return bool True if token is expired or invalid, false if still valid
     *              Returns false if token has no expiration claim
     */
    public function isExpired($token)
    {
        try {
            $decoded = $this->decode($token);
            if (isset($decoded['payload']['exp'])) {
                return $decoded['payload']['exp'] < time();
            }
            return false; // No expiration claim
        } catch (Exception $e) {
            return true; // Invalid token is considered expired
        }
    }

    /**
     * Get the remaining time before token expires (in seconds)
     * 
     * Calculates how many seconds remain before the token expires.
     * Does not perform signature validation.
     * 
     * @param string $token The JWT token to check
     * 
     * @return int|null Seconds until expiration (0 if expired),
     *                  null if token has no expiration claim,
     *                  0 if token is invalid
     */
    public function getTimeToExpiration($token)
    {
        try {
            $decoded = $this->decode($token);
            if (isset($decoded['payload']['exp'])) {
                return max(0, $decoded['payload']['exp'] - time());
            }
            return null; // No expiration claim
        } catch (Exception $e) {
            return 0; // Invalid token
        }
    }

    /**
     * Base64 URL encode
     * 
     * Encodes data using base64url encoding as specified in RFC 4648.
     * This is URL-safe base64 encoding that replaces '+' with '-',
     * '/' with '_', and removes padding '=' characters.
     * 
     * @param string $data Data to encode
     * 
     * @return string Base64url encoded string
     */
    private function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64 URL decode
     * 
     * Decodes base64url encoded data as specified in RFC 4648.
     * Converts URL-safe characters back to standard base64 and
     * adds necessary padding before decoding.
     * 
     * @param string $data Base64url encoded string to decode
     * 
     * @return string Decoded data
     */
    private function base64UrlDecode($data)
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
}
