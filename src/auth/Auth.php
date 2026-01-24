<?php

namespace Arbor\auth;


/*
auth/
├── Auth.php
│── Token.php
│── AuthPolicy.php
│── TokenContext.php
│── TokenStoreInterface.php
│── TokenIssuerInterface.php
│
├── Issuer/
│   ├── JwtIssuer.php
│   └── OpaqueIssuer.php
*/


final class Auth
{
    public function __construct(
        private TokenIssuerInterface $issuer
    ) {}

    public function issueToken(
        array $claims = [],
        array $options = []
    ): Token {
        return $this->issuer->issue($claims, $options);
    }


    public function resolve(string $rawToken)
    {
        $token = $this->issuer->parse($rawToken);

        return $token;
    }
}
