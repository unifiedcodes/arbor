<?php

namespace Arbor\auth;

use Arbor\auth\TokenRepoInterface;
use RuntimeException;

class RefreshToken
{
    public function __construct(
        protected TokenRepoInterface $repo,
        protected int $ttl = 1209600 // default 14 days
    ) {}

    public function issue(string $userId): array
    {
        $raw = $this->generateToken();
        $hash = $this->hash($raw);
        $exp = time() + $this->ttl;

        $this->repo->store($userId, $hash, $exp);

        return [
            'refresh_token' => $raw,
            'expires_at' => $exp,
        ];
    }

    public function rotate(string $oldRaw): array
    {
        $oldHash = $this->hash($oldRaw);
        $record = $this->repo->find($oldHash);

        if (!$record || $record['revoked'] || $record['expires_at'] < time()) {
            throw new RuntimeException("Invalid refresh token");
        }

        $newRaw = $this->generateToken();
        $newHash = $this->hash($newRaw);
        $newExp = time() + $this->ttl;

        $this->repo->rotate($oldHash, $newHash, $newExp);

        return [
            'refresh_token' => $newRaw,
            'expires_at' => $newExp,
            'user_id' => $record['user_id'],
        ];
    }

    public function revoke(string $raw): void
    {
        $this->repo->revoke($this->hash($raw));
    }

    public function revokeUser(string $userId): void
    {
        $this->repo->revokeUser($userId);
    }

    protected function generateToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    protected function hash(string $raw): string
    {
        return hash('sha256', $raw);
    }
}
