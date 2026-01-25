<?php

namespace Arbor\auth;


use Arbor\auth\TokenStoreInterface;
use Arbor\auth\TokenIssuerInterface;
use Arbor\auth\Token;
use Arbor\auth\AuthContext;
use Arbor\auth\Registry;


final class Auth
{
    private Registry $registry;


    public function __construct(
        private TokenIssuerInterface $issuer,
        private ?TokenStoreInterface $store = null,
        private ?AuthPolicy $policy = null,
        private array $options = []
    ) {
        $this->registry = new Registry($this->store);

        if (!$this->policy) {
            // constructing auth policy with options and defaults
            $this->policy = new AuthPolicy(
                $options['hasExpiry'] ?? $this->issuer->getExpiry(),
                $this->store,
            );
        }
    }


    public function issueToken(array $claims = [], array $options = []): Token
    {
        $token = $this->issuer->issue($claims, $options);

        // optionally ask registry to persist.
        $this->registry->save($token);

        return $token;
    }


    public function resolve(string $rawToken): AuthContext
    {
        $token = $this->issuer->parse($rawToken);

        // get enriched token from persistance.
        $token = $this->registry->get($token);

        // checking Policy
        $this->policy->validate($token);

        // build auth context.
        return new AuthContext($token, $this->store);
    }
}
