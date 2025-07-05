<?php

namespace Arbor\auth;

class JWT
{
    private $privateKey;
    private $publicKey;
    private $algorithm = 'RS256';

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
     */
    public function setPrivateKey($privateKey)
    {
        if (is_string($privateKey)) {
            $this->privateKey = openssl_pkey_get_private($privateKey);
        } else {
            $this->privateKey = $privateKey;
        }

        if (!$this->privateKey) {
            throw new \InvalidArgumentException('Invalid private key');
        }
    }

    /**
     * Set the public key for verifying tokens
     */
    public function setPublicKey($publicKey)
    {
        if (is_string($publicKey)) {
            $this->publicKey = openssl_pkey_get_public($publicKey);
        } else {
            $this->publicKey = $publicKey;
        }

        if (!$this->publicKey) {
            throw new \InvalidArgumentException('Invalid public key');
        }
    }

    /**
     * Create a JWT token
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
     */
    public function validate($token)
    {
        if (!$this->publicKey) {
            throw new \RuntimeException('Public key not set');
        }

        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new \InvalidArgumentException('Invalid token format');
        }

        list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;

        // Decode header
        $header = json_decode($this->base64UrlDecode($headerEncoded), true);
        if (!$header || $header['alg'] !== $this->algorithm) {
            throw new \InvalidArgumentException('Invalid algorithm or header');
        }

        // Verify signature
        $signatureInput = $headerEncoded . '.' . $payloadEncoded;
        $signature = $this->base64UrlDecode($signatureEncoded);

        if (!openssl_verify($signatureInput, $signature, $this->publicKey, OPENSSL_ALGO_SHA256)) {
            throw new \InvalidArgumentException('Invalid signature');
        }

        // Decode payload
        $payload = json_decode($this->base64UrlDecode($payloadEncoded), true);
        if (!$payload) {
            throw new \InvalidArgumentException('Invalid payload');
        }

        // Check expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            throw new \InvalidArgumentException('Token has expired');
        }

        // Check not before
        if (isset($payload['nbf']) && $payload['nbf'] > time()) {
            throw new \InvalidArgumentException('Token not yet valid');
        }

        return $payload;
    }

    /**
     * Decode a token without validation (useful for debugging)
     */
    public function decode($token)
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new \InvalidArgumentException('Invalid token format');
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
     */
    public function isExpired($token)
    {
        try {
            $decoded = $this->decode($token);
            if (isset($decoded['payload']['exp'])) {
                return $decoded['payload']['exp'] < time();
            }
            return false; // No expiration claim
        } catch (\Exception $e) {
            return true; // Invalid token is considered expired
        }
    }

    /**
     * Get the remaining time before token expires (in seconds)
     */
    public function getTimeToExpiration($token)
    {
        try {
            $decoded = $this->decode($token);
            if (isset($decoded['payload']['exp'])) {
                return max(0, $decoded['payload']['exp'] - time());
            }
            return null; // No expiration claim
        } catch (\Exception $e) {
            return 0; // Invalid token
        }
    }

    /**
     * Base64 URL encode
     */
    private function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64 URL decode
     */
    private function base64UrlDecode($data)
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
}
