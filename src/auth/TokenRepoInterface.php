<?php

namespace Arbor\auth;


interface TokenRepoInterface
{
    public function store(string $userId, string $refreshToken, int $expiresAt): void;

    public function find(string $refreshToken): ?array;

    public function rotate(string $oldToken, string $newToken, int $newExpiresAt): void;

    public function revoke(string $refreshToken): void;

    public function revokeUser(string $userId): void;
}
