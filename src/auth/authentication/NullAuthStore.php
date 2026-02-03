<?php

namespace Arbor\auth\authentication;

use Arbor\auth\authentication\AuthorityStoreInterface;

class NullAuthStore implements AuthorityStoreInterface
{
    public function abilities(Token $token): array
    {
        return [];
    }
}
