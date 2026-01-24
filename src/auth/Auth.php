<?php

namespace Arbor\auth;


use Arbor\auth\TokenStoreInterface;
use Arbor\auth\TokenIssuerInterface;
use Arbor\auth\Token;
use Arbor\auth\AuthContext;
use RuntimeException;


final class Auth
{
    public function __construct(
        private TokenIssuerInterface $issuer,
        private ?TokenStoreInterface $store = null
    ) {}


    public function issueToken(
        array $claims = [],
        array $options = []
    ): Token {
        $token = $this->issuer->issue($claims, $options);

        // optionally ask store to persist.
        if ($this->store instanceof TokenStoreInterface) {
            $this->store->save($token);
        }

        return $token;
    }


    public function resolve(string $rawToken): AuthContext
    {
        $token = $this->issuer->parse($rawToken);


        if ($this->store instanceof TokenStoreInterface) {
            // find from store.
            $token = $this->store->find($token->id());

            // check if acceptable.
            $this->store->isAcceptable($token);
        }

        // check for expiry.
        if (
            $this->issuer->getExpiry() !== null
            && time() > $token->expiresAt()
        ) {
            throw new RuntimeException("Token is expired");
        }

        // build auth context.
        return new AuthContext($token, $this->store);
    }
}
