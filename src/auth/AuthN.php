<?php

namespace Arbor\auth;

use Arbor\auth\UserRepoInterface;
use Arbor\auth\RefreshToken;
use RuntimeException;

class AuthNService
{
    public function __construct(
        protected UserRepoInterface $users,
        protected RefreshToken $refresh,
        protected JWT $jwt,
        protected int $accessTtl = 3600 // 1h default
    ) {}

    /**
     * Login: verify credentials, issue access+refresh
     */
    public function login(string $identifier, string $password): array
    {
        $user = $this->users->findByIdentifier($identifier);
        if (!$user) {
            throw new RuntimeException("Invalid credentials.");
        }

        $userId = $user['id'] ?? null;

        if (!$userId) {
            throw new RuntimeException("User record missing id.");
        }

        $hash = $this->users->getPasswordHash($userId);

        if (!$hash || !password_verify($password, $hash)) {
            throw new RuntimeException("Invalid credentials.");
        }

        $refresh = $this->refresh->issue($userId);

        return [
            'user' => $user,
            'refresh_token' => $refresh['refresh_token'],
            'expires_at' => time() + $this->accessTtl,
        ];
    }

    /**
     * Refresh access token using refresh token
     */
    public function refresh(string $refreshToken): array
    {
        $result = $this->refresh->rotate($refreshToken);

        $access = $this->jwt->create(['sub' => $result['user_id']], $this->accessTtl);

        return [
            'access_token' => $access,
            'refresh_token' => $result['refresh_token'],
            'expires_at' => time() + $this->accessTtl,
        ];
    }

    /**
     * Logout: revoke refresh token
     */
    public function logout(string $refreshToken): void
    {
        $this->refresh->revoke($refreshToken);
    }
}
