<?php

namespace Arbor\auth;


use Arbor\auth\authentication\TokenIssuerInterface;
use Arbor\auth\authentication\Registry;
use Arbor\auth\authentication\Token;
use Arbor\auth\authentication\Policy;
use Arbor\auth\AuthContext;
use Arbor\auth\authorization\ActionInterface;
use Arbor\auth\authorization\ResourceInterface;
use Arbor\auth\Authorizer;
use Arbor\facades\Scope;
use RuntimeException;


/**
 * Auth class Orchestrator for authentication and authorization.
 *
 * Manages authentication operations including token issuance, resolution, and validation.
 * This class coordinates between token issuers, storage, and validation policies.
 * 
 * @package Arbor/auth
 * 
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
        private ?Authorizer $authorizer = null,
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

        // gathering abilities of user.
        $abilities = $this->registry->getAbilities($token);

        $authContext = new AuthContext(
            $token,
            $this->registry,
            $abilities
        );

        // setting into scope
        Scope::set(AuthContext::class, $authContext);

        // build auth context.
        return $authContext;
    }


    /**
     * Revoke an authentication token
     *
     * Revokes the provided token, effectively invalidating it for future use.
     * Accepts either a Token or AuthContext instance.
     *
     * @param Token|AuthContext $token The token or auth context to revoke
     *
     * @return void
     */
    public function revoke(Token|AuthContext $token): void
    {
        if ($token instanceof AuthContext) {
            $token = $token->token();
        }

        $this->registry->revoke($token);
    }


    /**
     * Register an ability
     *
     * Defines a new ability that maps a resource and action to an ability identifier.
     * The authorizer must be configured, otherwise a RuntimeException is thrown.
     *
     * @param string $id The unique identifier for this ability
     * @param ResourceInterface|string $resource The resource the ability applies to
     * @param ActionInterface $action The action the ability allows
     *
     * @throws RuntimeException If authorizer is not configured
     *
     * @return void
     */
    public function ability(string $id, ResourceInterface|string $resource, ActionInterface $action): void
    {
        $this->haveAuthorizer();
        $this->authorizer->addAbility($id, $resource, $action);
    }


    /**
     * Load abilities from a file
     *
     * Includes and executes the specified file in the context of this Auth instance,
     * allowing the file to define abilities using the ability() method.
     *
     * @param string $filePath The path to the abilities definition file
     *
     * @return void
     */
    public function abilityFile(string $filePath): void
    {
        $auth = $this;
        require_once $filePath;
    }


    /**
     * Check if the current user can perform an action on a resource
     *
     * Verifies that the authenticated user in the current scope has the ability
     * to perform the specified action on the given resource. The authorizer must
     * be configured, otherwise a RuntimeException is thrown.
     *
     * @param ResourceInterface|string $resource The resource to check access for
     * @param ActionInterface $action The action to check authorization for
     *
     * @throws RuntimeException If authorizer is not configured
     *
     * @return void
     */
    public function can(ResourceInterface|string $resource, ActionInterface $action): void
    {
        $this->haveAuthorizer();

        $authContext = Scope::get(AuthContext::class);

        $this->authorizer->hasAbility($authContext, $resource, $action);
    }


    /**
     * Ensure an authorizer is configured
     *
     * Validates that an Authorizer instance has been set. Throws a RuntimeException
     * if the authorizer is not available.
     *
     * @throws RuntimeException If authorizer is not configured
     *
     * @return void
     */
    public function haveAuthorizer(): void
    {
        if (!$this->authorizer) {
            throw new RuntimeException("'Authorizer' is not configured");
        }
    }


    /**
     * Get all defined ability IDs
     *
     * Returns a list of all ability identifiers that have been defined in the authorizer.
     *
     * @return array An array of ability ID strings
     */
    public function definedAbilities(): array
    {
        return $this->authorizer->abilityIds();
    }
}
