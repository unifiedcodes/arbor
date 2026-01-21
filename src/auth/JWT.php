<?php

namespace Arbor\auth;

use Arbor\auth\Token;
use Arbor\auth\Keys;
use RuntimeException;


class JWT
{
    public function __construct(
        protected Keys $keys
    ) {}


    protected static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }


    public function create(array $payload, int $ttl = 3600): string
    {
        // Step 1: prepare header
        $kid = $this->keys->getActiveKid();

        $header = [
            'alg' => 'EdDSA',
            'typ' => 'JWT',
            'kid' => $kid,
        ];

        // Step 2: apply standard claims
        $now = time();
        $payload['iat'] = $payload['iat'] ?? $now;
        $payload['exp'] = $payload['exp'] ?? ($now + $ttl);

        // Step 3: encode header & payload
        $h = self::base64UrlEncode(json_encode($header));
        $p = self::base64UrlEncode(json_encode($payload));

        // Step 4: create signing input
        $signingInput = $h . '.' . $p;

        // Step 5: sign using active private key
        $privateKey = $this->keys->getActivePrivate();
        $signature  = sodium_crypto_sign_detached($signingInput, $privateKey);

        // Step 6: attach signature
        $s = self::base64UrlEncode($signature);

        // Step 7: final JWT
        return $signingInput . '.' . $s;
    }


    public function verify(string $jwt): Token
    {
        // Step 1: parse into Token object
        $token = Token::fromString($jwt);

        // Step 2: validate algorithm
        $header = $token->getHeader();

        if (($header['alg'] ?? null) !== 'EdDSA') {
            throw new RuntimeException("Invalid JWT alg, expected EdDSA.");
        }

        // Step 3: validate KID
        $kid = $token->getKid();

        if (!$kid) {
            throw new RuntimeException("Missing 'kid' in JWT header.");
        }

        // Step 4: load correct public key
        $publicKey = $this->keys->getPublicByKid($kid);

        // Step 5: verify signature
        $signature = $token->getSignature();
        $signingInput = $token->getRawSigningInput();

        $valid = sodium_crypto_sign_verify_detached(
            $signature,
            $signingInput,
            $publicKey
        );

        if (!$valid) {
            throw new RuntimeException("Invalid JWT signature.");
        }

        // Step 6: check expiration
        if ($token->isExpired()) {
            throw new RuntimeException("JWT has expired.");
        }

        // If we reach here, token is trusted & valid
        return $token;
    }
}
