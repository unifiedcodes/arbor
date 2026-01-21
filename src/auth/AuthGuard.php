<?php

namespace Arbor\auth;

use Arbor\auth\UserRepoInterface;
use Arbor\auth\JWT;
use RuntimeException;

class AuthGuard
{
    public function __construct(
        protected JWT $jwt,
        protected UserRepoInterface $users
    ) {}

    /**
     * Authenticate request and return user array.
     */
    public function authenticate(object $request): array
    {
        $tokenStr = $this->extractToken($request);
        if (!$tokenStr) {
            throw new RuntimeException("Missing Authorization Bearer token.");
        }

        $token = $this->jwt->verify($tokenStr);

        $userId = $token->getSubject();
        if (!$userId) {
            throw new RuntimeException("Token missing subject (sub).");
        }

        $user = $this->users->findById($userId) ?? null;
        if (!$user) {
            throw new RuntimeException("User not found.");
        }

        return $user;
    }

    protected function extractToken(object $request): ?string
    {
        // Your app will adapt this to its Request object
        $auth = $request->getHeader('Authorization') ?? '';
        if (str_starts_with($auth, 'Bearer ')) {
            return substr($auth, 7);
        }
        return null;
    }
}
