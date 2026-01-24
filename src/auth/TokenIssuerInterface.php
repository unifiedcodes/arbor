<?php

namespace Arbor\auth;


use Arbor\auth\Token;


interface TokenIssuerInterface
{
    public function issue(array $claims = [], array $options = []): Token;
    public function parse(string $raw): Token;
}
