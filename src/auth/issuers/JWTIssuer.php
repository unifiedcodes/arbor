<?php

namespace Arbor\auth\issuers;

use Arbor\auth\Token;
use Arbor\auth\TokenIssuerInterface;
use InvalidArgumentException;

final class JWTIssuer implements TokenIssuerInterface
{

    public function __construct(
        private string $signingKey,
        private ?string $kid = null,
        private ?int $ttl = null
    ) {
        if (strlen($this->signingKey) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
            throw new InvalidArgumentException('Invalid Ed25519 private key.');
        }
    }


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


    public function getExpiry(): ?int
    {
        return $this->ttl;
    }


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


    private function base64UrlEncode(string $data): string
    {
        return rtrim(
            strtr(base64_encode($data), '+/', '-_'),
            '='
        );
    }


    private function base64UrlDecode(string $data): string
    {
        return base64_decode(
            strtr($data, '-_', '+/'),
            true
        );
    }
}
