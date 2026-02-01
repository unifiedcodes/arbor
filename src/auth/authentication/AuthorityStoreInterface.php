<?php

namespace Arbor\auth\authentication;

use Arbor\auth\authentication\Token;

/**
 * Interface AuthorizationStoreInterface
 *
 * Defines the contract for resolving authorization data
 * (roles, abilities, and future policy hints) for an authenticated identity.
 *
 * Implementations are storage-backed
 * and MUST NOT mutate AuthContext directly.
 */
interface AuthorityStoreInterface
{
    public function abilities(Token $token): array;
}
