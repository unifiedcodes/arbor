<?php

namespace Arbor\Auth\issuers;

use Arbor\Auth\Token;
use Arbor\Auth\TokenIssuerInterface;
use InvalidArgumentException;


final class OpaqueIssuer implements TokenIssuerInterface
{
    public function __construct(
        private ?int $ttl = null,
        private int $tokenByteLength = 32,
        private string $hashAlgo = 'sha256'
    ) {}


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
            expiresAt: $payload['exp']
        );
    }

    public function parse(string $rawToken): Token
    {
        if ($rawToken === '' || strlen($rawToken) < 32) {
            throw new InvalidArgumentException('Invalid opaque token.');
        }

        return new Token(
            type: 'opaque',
            value: $rawToken,
            id: hash('sha256', $rawToken),
            claims: [],
            expiresAt: null
        );
    }


    public function getExpiry(): ?int
    {
        return $this->ttl;
    }


    private function createToken()
    {
        $randomBytes = random_bytes($this->tokenByteLength);
        $token = $this->base64UrlEncode($randomBytes);

        return $token;
    }


    private function tokenId(string $token)
    {
        return hash($this->hashAlgo, $token);
    }


    private function base64UrlEncode(string $data): string
    {
        return rtrim(
            strtr(base64_encode($data), '+/', '-_'),
            '='
        );
    }
}
