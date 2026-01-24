<?php

namespace Arbor\Auth\issuers;

use Arbor\Auth\Token;
use Arbor\Auth\TokenIssuerInterface;
use InvalidArgumentException;


final class OpaqueIssuer implements TokenIssuerInterface
{
    private const TOKEN_BYTE_LENGTH = 32;
    private const HASH_ALGO = 'sha256';

    public function issue(array $claims = [], array $options = []): Token
    {
        $randomBytes = random_bytes(self::TOKEN_BYTE_LENGTH);

        $tokenValue = $this->base64UrlEncode($randomBytes);

        $tokenId = hash(self::HASH_ALGO, $tokenValue);

        $expiresAt = $options['expires_at'] ?? null;

        return new Token(
            value: $tokenValue,
            id: $tokenId,
            claims: [],
            expiresAt: $expiresAt,
            type: 'opaque'
        );
    }

    public function parse(string $rawToken): Token
    {
        if ($rawToken === '' || strlen($rawToken) < 32) {
            throw new InvalidArgumentException('Invalid opaque token.');
        }

        return new Token(
            value: $rawToken,
            id: hash('sha256', $rawToken),
            claims: [],
            expiresAt: null,
            type: 'opaque'
        );
    }


    private function base64UrlEncode(string $data): string
    {
        return rtrim(
            strtr(base64_encode($data), '+/', '-_'),
            '='
        );
    }
}
