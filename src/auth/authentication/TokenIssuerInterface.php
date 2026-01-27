<?php

namespace Arbor\auth\authentication;


use Arbor\auth\authentication\Token;


/**
 * Interface TokenIssuerInterface
 *
 * Defines the contract for token issuance and parsing operations.
 * Implementations of this interface handle the creation and validation of tokens
 * with customizable claims and options.
 *
 * @package Arbor\auth
 */
interface TokenIssuerInterface
{
    /**
     * Issues a new token with the given claims and options.
     *
     * @param array $claims An associative array of claims to include in the token.
     *                       Defaults to an empty array if not provided.
     * @param array $options An associative array of options to customize token generation.
     *                        Defaults to an empty array if not provided.
     *
     * @return Token The newly issued token object.
     */
    public function issue(array $claims = [], array $options = []): Token;

    /**
     * Parses and validates a raw token string.
     *
     * @param string $raw The raw token string to parse and validate.
     *
     * @return Token The parsed and validated token object.
     */
    public function parse(string $raw): Token;

    /**
     * Retrieves the token expiry time.
     *
     * @return int|null The token expiry time in seconds, or null if the token does not expire.
     */
    public function getExpiry(): ?int;
}
