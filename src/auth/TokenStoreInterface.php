<?php

namespace Arbor\auth;

interface TokenStoreInterface
{
    public function save(Token $token): void;

    public function find(string $tokenId): ?Token;

    public function isAcceptable(Token $token): void;

    public function revoke(string $tokenId): void;
}
