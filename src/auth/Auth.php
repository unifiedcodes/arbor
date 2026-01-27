<?php

namespace Arbor\auth;


use Arbor\auth\authentication\TokenIssuerInterface;
use Arbor\auth\authentication\Registry;
use Arbor\auth\authentication\Token;
use Arbor\auth\authentication\Policy;
use Arbor\auth\AuthContext;


/**
 * Auth class
 *
 * Manages authentication operations including token issuance, resolution, and validation.
 * This class coordinates between token issuers, storage, and validation policies.
 */
final class Auth
{
    /**
     * Constructor
     *
     * Initializes the Auth instance with required dependencies and optional configurations.
     * Sets up the registry for token persistence and initializes the auth policy if not provided.
     *
     * @param TokenIssuerInterface $issuer The token issuer implementation
     * @param Policy $policy Optional custom authentication policy
     * @param Registry $registry token registry for persistence
     * @param array $options Configuration options (e.g., 'hasExpiry' for token expiration)
     */
    public function __construct(
        private TokenIssuerInterface $issuer,
        private Registry $registry,
        private Policy $policy,
        private array $options = []
    ) {}


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
    public function resolve(string $rawToken, ?string $verificationKey = null): AuthContext
    {
        $token = $this->issuer->parse($rawToken, $verificationKey);

        // get enriched token from persistance.
        $storedToken = $this->registry->get($token);

        if ($storedToken) {
            $token = $storedToken;
        }

        // checking Policy
        $this->policy->validate($token);

        // build auth context.
        return new AuthContext($token, $this->registry);
    }


    public function revoke(Token|AuthContext $token): void
    {
        if ($token instanceof AuthContext) {
            $token = $token->token();
        }

        $this->registry->revoke($token);
    }
}
