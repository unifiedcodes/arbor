<?php

namespace Arbor\auth;

use Arbor\auth\TokenStoreInterface;
use Arbor\auth\Token;
use InvalidArgumentException;

class Registry
{
    public function __construct(private ?TokenStoreInterface $store = null) {}

    public function save(Token $token): void
    {
        if ($this->store) {
            $this->validateClaims($token);
            $this->store->save($token);
        }
    }


    private function validateClaims(Token $token): void
    {
        $require = $this->store->requireClaims();
        $claims = $token->claims();
        $storeClassName = get_class($this->store);

        foreach ($require as $claimKey) {
            if (!isset($claims[$claimKey])) {
                throw new InvalidArgumentException("Claim shape is mismatched, {$storeClassName} require key: {$claimKey} in token claims");
            }
        }
    }


    public function get(Token $token): Token
    {
        return $this->store->retrieve($token);
    }
}
