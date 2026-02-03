<?php

namespace Arbor\auth\authentication;

use Arbor\auth\authentication\AuthorityStoreInterface;

/**
 * NullAuthStore
 *
 * A null implementation of the AuthorityStoreInterface that always returns
 * an empty array of abilities. This is useful for scenarios where no authority
 * checking is required or as a default fallback implementation.
 *
 * @package Arbor\auth\authentication
 */
class NullAuthStore implements AuthorityStoreInterface
{
    /**
     * Get abilities for a given token
     *
     * Returns an empty array, indicating that the token has no abilities.
     * This null implementation effectively denies all ability checks.
     *
     * @param Token $token The authentication token
     * @return array An empty array of abilities
     */
    public function abilities(Token $token): array
    {
        return [];
    }
}
