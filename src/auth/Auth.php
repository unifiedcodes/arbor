<?php

namespace Arbor\auth;


use Arbor\auth\TokenStoreInterface;
use Arbor\auth\TokenIssuerInterface;
use Arbor\auth\Token;
use Arbor\auth\AuthContext;
use Arbor\auth\Registry;


/**
 * Auth class
 *
 * Manages authentication operations including token issuance, resolution, and validation.
 * This class coordinates between token issuers, storage, and validation policies.
 */
final class Auth
{
    /**
     * @var Registry Registry instance for managing token persistence
     */
    private Registry $registry;


    /**
     * Constructor
     *
     * Initializes the Auth instance with required dependencies and optional configurations.
     * Sets up the registry for token persistence and initializes the auth policy if not provided.
     *
     * @param TokenIssuerInterface $issuer The token issuer implementation
     * @param TokenStoreInterface|null $store Optional token store for persistence
     * @param AuthPolicy|null $policy Optional custom authentication policy
     * @param array $options Configuration options (e.g., 'hasExpiry' for token expiration)
     */
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


    /**
     * Issues a new authentication token
     *
     * Creates a token with the provided claims and options, then persists it to storage
     * via the registry if storage is available.
     *
     * @param array $claims Token claims/payload (default: empty array)
     * @param array $options Token generation options (default: empty array)
     *
     * @return Token The newly issued token
     */
    public function issueToken(array $claims = [], array $options = []): Token
    {
        $token = $this->issuer->issue($claims, $options);

        // optionally ask registry to persist.
        $this->registry->save($token);

        return $token;
    }


    /**
     * Resolves a raw token string into an authenticated context
     *
     * Parses the raw token, retrieves enriched token data from persistence,
     * validates it against the configured policy, and returns an AuthContext.
     *
     * @param string $rawToken The raw token string to resolve
     *
     * @return AuthContext An authenticated context containing the validated token
     *
     * @throws Exception if token validation fails or token is invalid
     */
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
