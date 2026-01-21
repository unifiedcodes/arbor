<?php

namespace Arbor\auth;

use InvalidArgumentException;

class Token
{
    protected array $header = [];
    protected array $payload = [];
    protected string $signature = '';
    protected string $rawHeader = '';
    protected string $rawPayload = '';

    public static function fromString(string $jwt): self
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new InvalidArgumentException("Invalid JWT format, must contain 3 segments.");
        }

        [$h, $p, $s] = $parts;

        $obj = new self();
        $obj->rawHeader  = $h;
        $obj->rawPayload = $p;
        $obj->signature  = self::base64UrlDecode($s);

        $header = json_decode(self::base64UrlDecode($h), true);
        $payload = json_decode(self::base64UrlDecode($p), true);

        if (!is_array($header)) {
            throw new InvalidArgumentException("Invalid JWT header JSON.");
        }

        if (!is_array($payload)) {
            throw new InvalidArgumentException("Invalid JWT payload JSON.");
        }

        $obj->header = $header;
        $obj->payload = $payload;

        return $obj;
    }

    public function getHeader(): array
    {
        return $this->header;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getSignature(): string
    {
        return $this->signature;
    }

    public function getKid(): ?string
    {
        return $this->header['kid'] ?? null;
    }

    public function getRawSigningInput(): string
    {
        return $this->rawHeader . '.' . $this->rawPayload;
    }

    public function isExpired(): bool
    {
        return isset($this->payload['exp']) && time() >= $this->payload['exp'];
    }

    public function getSubject(): mixed
    {
        return $this->payload['sub'] ?? null;
    }

    protected static function base64UrlDecode(string $data): string
    {
        $replaced = strtr($data, '-_', '+/');
        $pad = strlen($replaced) % 4;
        if ($pad > 0) {
            $replaced .= str_repeat('=', 4 - $pad);
        }
        return base64_decode($replaced);
    }
}
