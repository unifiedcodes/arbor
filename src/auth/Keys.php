<?php

namespace Arbor\auth;

use Arbor\config\ConfigValue;
use RuntimeException;


/**
 * Manages cryptographic key generation, rotation, and retrieval.
 *
 * This class handles Ed25519 signing keys using libsodium for secure
 * cryptographic operations. It supports key rotation with timestamp-based
 * key IDs (KIDs) and provides methods to access both active and archived keys.
 * 
 * @package Arbor/auth
 * 
 */
class Keys
{
    /**
     * Constructor.
     *
     * @param string $keysPath The directory path where keys are stored, injected via ConfigValue.
     *
     * @throws RuntimeException If libsodium extension is not available or incomplete.
     */
    public function __construct(
        #[ConfigValue('root.keys_dir')]
        protected string $keysPath
    ) {
        $this->ensureSodium();
        ensureDir($this->keysPath);
    }


    /**
     * Validates that the libsodium extension is loaded with required Ed25519 functions.
     *
     * @throws RuntimeException If sodium extension is missing or Ed25519 functions are unavailable.
     */
    protected function ensureSodium(): void
    {
        if (!extension_loaded('sodium')) {
            throw new RuntimeException(
                "libsodium extension is not available. Please enable/ install sodium before using CryptoKeys."
            );
        }

        if (!function_exists('sodium_crypto_sign_keypair')) {
            throw new RuntimeException(
                "libsodium is loaded but Ed25519 signing functions are missing. " .
                    "Check that your PHP build includes sodium_crypto_sign_* functions."
            );
        }
    }


    /**
     * Generates a new Ed25519 keypair.
     *
     * @return array An associative array with 'public' and 'private' keys containing the raw binary key data.
     */
    public function generate(): array
    {
        $keypair    = sodium_crypto_sign_keypair();
        $publicKey  = sodium_crypto_sign_publickey($keypair);
        $privateKey = sodium_crypto_sign_secretkey($keypair);

        return [
            'public'  => $publicKey,
            'private' => $privateKey,
        ];
    }


    /**
     * Rotates to a new keypair and updates the active key pointer.
     *
     * Generates a new keypair, stores both keys as base64-encoded files,
     * and updates the active_kid file to point to the new key ID.
     * The KID is a timestamp in YmdHis format for traceability.
     *
     * @return string The key ID (KID) of the newly rotated keypair.
     */
    public function rotate(): string
    {
        $keys = $this->generate();

        // Use timestamp-based KID for traceability
        $kid = date('YmdHis');

        // Prepare file paths
        $privateFile = "{$this->keysPath}/{$kid}.private";
        $publicFile  = "{$this->keysPath}/{$kid}.public";
        $activeFile  = "{$this->keysPath}/active_kid";

        // Save keys as base64 so they are portable and readable
        file_put_contents($privateFile, base64_encode($keys['private']));
        file_put_contents($publicFile, base64_encode($keys['public']));

        // Update the active KID pointer (important for signing)
        file_put_contents($activeFile, $kid);

        return $kid;
    }


    /**
     * Retrieves the currently active private key.
     *
     * Reads the active_kid file to determine which private key is currently in use,
     * then loads and decodes the corresponding private key file from base64.
     *
     * @return string The raw binary private key data.
     *
     * @throws RuntimeException If no active key is found, the active_kid file is empty,
     *                          the private key file doesn't exist, or base64 decoding fails.
     */
    public function getActivePrivate(): string
    {
        $activeFile = "{$this->keysPath}/active_kid";

        if (!file_exists($activeFile)) {
            throw new RuntimeException("No active key found. You need to run rotate() first.");
        }

        $kid = trim(file_get_contents($activeFile));
        if ($kid === '') {
            throw new RuntimeException("Active key ID file is empty. Rotation may have failed.");
        }

        $privateFile = "{$this->keysPath}/{$kid}.private";

        if (!file_exists($privateFile)) {
            throw new RuntimeException("Active private key file not found for kid: {$kid}");
        }

        $base64 = file_get_contents($privateFile);
        if ($base64 === false) {
            throw new RuntimeException("Failed to read private key file for kid: {$kid}");
        }

        $private = base64_decode($base64, true);
        if ($private === false) {
            throw new RuntimeException("Private key for kid {$kid} is not valid base64.");
        }

        return $private;
    }


    /**
     * Retrieves the currently active key ID (KID).
     *
     * Reads and returns the key ID stored in the active_kid file.
     *
     * @return string The active key ID.
     *
     * @throws RuntimeException If the active_kid file doesn't exist or is empty.
     */
    public function getActiveKid(): string
    {
        $activeFile = "{$this->keysPath}/active_kid";

        if (!file_exists($activeFile)) {
            throw new RuntimeException("No active key found. Run rotate() first.");
        }

        $kid = trim(file_get_contents($activeFile));
        if ($kid === '') {
            throw new RuntimeException("Active key ID is empty. Rotation may have failed.");
        }

        return $kid;
    }


    /**
     * Retrieves a public key by its key ID (KID).
     *
     * Loads and decodes a base64-encoded public key file for the specified KID.
     *
     * @param string $kid The key ID identifying which public key to retrieve.
     *
     * @return string The raw binary public key data.
     *
     * @throws RuntimeException If the public key file doesn't exist for the given KID,
     *                          the file cannot be read, or base64 decoding fails.
     */
    public function getPublicByKid(string $kid): string
    {
        $publicFile = "{$this->keysPath}/{$kid}.public";

        if (!file_exists($publicFile)) {
            throw new RuntimeException("Public key file not found for kid: {$kid}");
        }

        $base64 = file_get_contents($publicFile);
        if ($base64 === false) {
            throw new RuntimeException("Failed to read public key file for kid: {$kid}");
        }

        $public = base64_decode($base64, true);
        if ($public === false) {
            throw new RuntimeException("Public key for kid {$kid} is not valid base64.");
        }

        return $public;
    }


    /**
     * Converts a public key to JWK (JSON Web Key) format.
     *
     * Retrieves the public key for the given KID and converts it to RFC 8037
     * JWK format for use with JWT or other standards-based operations.
     * The key material is base64url-encoded without padding.
     *
     * @param string $kid The key ID identifying which public key to convert.
     *
     * @return array The JWK representation with keys: kty (OKP), crv (Ed25519), x (base64url), kid.
     *
     * @throws RuntimeException If the public key file doesn't exist or cannot be decoded.
     */
    public function toJWK(string $kid): array
    {
        $raw = $this->getPublicByKid($kid);

        // base64url without padding
        $x = rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');

        return [
            'kty' => 'OKP',
            'crv' => 'Ed25519',
            'x'   => $x,
            'kid' => $kid,
        ];
    }
}
