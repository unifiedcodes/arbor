<?php

namespace Arbor\auth;

use Arbor\config\ConfigValue;
use RuntimeException;


class Keys
{
    public function __construct(
        #[ConfigValue('root.keys_dir')]
        protected string $keysPath
    ) {
        $this->ensureSodium();
        ensureDir($this->keysPath);
    }


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
