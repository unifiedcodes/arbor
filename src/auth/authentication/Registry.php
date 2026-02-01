<?php

namespace Arbor\auth\authentication;

use Arbor\auth\authentication\TokenStoreInterface;
use Arbor\auth\authentication\NullTokenStore;
use Arbor\auth\authentication\Token;
use InvalidArgumentException;

/**
 * Registry class for managing token storage and validation.
 * 
 * This class acts as a registry for storing and retrieving authentication tokens.
 * It provides token persistence through a TokenStoreInterface implementation and
 * validates token claims against store requirements.
 */
class Registry
{
    /**
     * Constructor.
     * 
     * @param TokenStoreInterface|null $store Optional token store implementation. 
     *                                         If null, save operations will be skipped.
     */
    public function __construct(
        private ?TokenStoreInterface $store = null,
        private ?AuthorityStoreInterface $authstore = null
    ) {
        $this->store = $store ?? new NullTokenStore();
    }


    /**
     * Save a token to the store.
     * 
     * Validates the token's claims against store requirements before persisting.
     * If no store is configured, the operation is silently skipped.
     * 
     * @param Token $token The token to save
     * 
     * @throws InvalidArgumentException If token claims do not match store requirements
     * 
     * @return void
     */
    public function save(Token $token): void
    {
        $this->validateClaims($token);
        $this->store->save($token);
    }


    /**
     * Validate that a token contains all required claims.
     * 
     * Checks that the token's claims include all keys required by the token store.
     * Throws an exception if any required claim is missing.
     * 
     * @param Token $token The token whose claims should be validated
     * 
     * @throws InvalidArgumentException If a required claim is missing from the token
     * 
     * @return void
     */
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


    /**
     * Retrieve a token from the store.
     * 
     * @param Token $token The token to retrieve
     * 
     * @return Token The retrieved token
     */
    public function get(Token $token): ?Token
    {
        return $this->store->retrieve($token);
    }


    public function validate(Token $token): void
    {
        $this->store->validate($token);
    }


    public function revoke(Token $token): void
    {
        $tokenId = $token->id();
        $this->store->revoke($tokenId);
    }


    public function getAbilities(Token $token): array
    {
        return $this->authstore->abilities($token);
    }
}
