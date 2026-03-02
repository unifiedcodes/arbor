<?php

namespace Arbor\auth;

use Arbor\auth\authentication\TokenIssuerInterface;
use Arbor\auth\authentication\Registry;
use Arbor\auth\authentication\Policy;
use Arbor\auth\authentication\TokenStoreInterface;
use Arbor\auth\authentication\AuthorityStoreInterface;
use Arbor\auth\authentication\issuers\JWTIssuer;
use Arbor\auth\authentication\issuers\OpaqueIssuer;
use InvalidArgumentException;

/**
 * Class AuthBuilder
 *
 * Fluent builder for constructing an Auth instance.
 *
 * Responsibilities:
 * - Configure token issuer (JWT or Opaque)
 * - Configure key material (for JWT)
 * - Configure token and authority stores
 * - Configure registry and policy
 * - Optionally configure authorizer
 *
 * Ensures required dependencies are present before building Auth.
 */
final class AuthBuilder
{
    /**
     * Token issuer implementation (JWT or Opaque).
     */
    private ?TokenIssuerInterface $issuer = null;

    /**
     * Cryptographic key container (used for JWT).
     */
    private ?Keys $keys = null;

    /**
     * Authentication registry responsible for token lookup and authority resolution.
     */
    private ?Registry $registry = null;

    /**
     * Token persistence store.
     */
    private ?TokenStoreInterface $store = null;

    /**
     * Authority/role persistence store.
     */
    private ?AuthorityStoreInterface $authstore = null;

    /**
     * Authentication policy (expiry + registry validation rules).
     */
    private ?Policy $policy = null;

    /**
     * Whether tokens are configured to expire.
     */
    private bool $hasExpiry = false;

    /**
     * Optional authorization component.
     */
    private ?Authorizer $authorizer = null;

    /**
     * Create a new builder instance.
     *
     * @return static
     */
    public static function create(): static
    {
        return new static();
    }

    /**
     * Configure cryptographic keys for JWT issuance.
     *
     * @param string $keysPath Path to key storage.
     * @return static
     */
    public function useKeys(string $keysPath): static
    {
        $this->keys = new Keys($keysPath);
        return $this;
    }

    /**
     * Configure JWT token issuer.
     *
     * Requires keys to be configured first.
     *
     * @param int|null $ttl Token time-to-live in seconds.
     * @return static
     *
     * @throws InvalidArgumentException If keys are not configured.
     */
    public function useJwtIssuer(?int $ttl = null): static
    {
        if (!$this->keys) {
            throw new InvalidArgumentException("Required Keys configured for JwtIssuer");
        }

        $this->hasExpiry($ttl);

        $this->issuer = new JWTIssuer(
            $this->keys->getActivePrivate(),
            $this->keys->getActiveKid(),
            $ttl
        );

        return $this;
    }

    /**
     * Configure opaque token issuer.
     *
     * @param int|null $ttl Token time-to-live in seconds.
     * @param int $tokenByteLength Length of generated random token bytes.
     * @param string $hashAlgo Hash algorithm for token storage.
     * @return static
     */
    public function useOpaqueIssuer(
        ?int $ttl = null,
        int $tokenByteLength = 32,
        string $hashAlgo = 'sha256'
    ): static {

        $this->hasExpiry($ttl);

        $this->issuer = new OpaqueIssuer($ttl, $tokenByteLength, $hashAlgo);
        return $this;
    }

    /**
     * Use a custom token issuer implementation.
     *
     * @param TokenIssuerInterface $issuer
     * @return static
     */
    public function withIssuer(TokenIssuerInterface $issuer): static
    {
        $this->hasExpiry($issuer->getExpiry());

        $this->issuer = $issuer;
        return $this;
    }

    /**
     * Define whether issued tokens should expire.
     *
     * Rules:
     * - true → expiry enabled
     * - positive integer → expiry enabled
     * - false, null, 0, negative → expiry disabled
     *
     * @param bool|int|null $ttl
     * @return static
     */
    public function hasExpiry(bool|int|null $ttl): static
    {
        if ($ttl === true) {
            $this->hasExpiry = true;
            return $this;
        }

        if (is_int($ttl) && $ttl > 0) {
            $this->hasExpiry = true;
            return $this;
        }

        $this->hasExpiry = false;

        return $this;
    }

    /**
     * Configure token storage backend.
     *
     * @param TokenStoreInterface $store
     * @return static
     */
    public function withTokenStore(TokenStoreInterface $store): static
    {
        $this->store = $store;
        return $this;
    }

    /**
     * Configure authority storage backend.
     *
     * @param AuthorityStoreInterface $authStore
     * @return static
     */
    public function withAuthorityStore(AuthorityStoreInterface $authStore): static
    {
        $this->authstore = $authStore;
        return $this;
    }

    /**
     * Create and configure default authentication registry.
     *
     * Uses previously configured token and authority stores.
     *
     * @return static
     */
    public function useRegistry(): static
    {
        $this->registry = new Registry(
            $this->store,
            $this->authstore
        );
        return $this;
    }

    /**
     * Use a custom authentication registry.
     *
     * @param Registry $registry
     * @return static
     */
    public function withRegistry(Registry $registry): static
    {
        $this->registry = $registry;
        return $this;
    }

    /**
     * Create default authentication policy.
     *
     * Requires registry to be configured.
     *
     * @return static
     *
     * @throws InvalidArgumentException If registry is not set.
     */
    public function usePolicy(): static
    {
        if (!$this->registry) {
            throw new InvalidArgumentException("Authentication Registry not set");
        }

        $this->policy = new Policy($this->hasExpiry, $this->registry);
        return $this;
    }

    /**
     * Enable default authorizer.
     *
     * @return static
     */
    public function useAuthorizer(): static
    {
        $this->authorizer = new Authorizer();
        return $this;
    }

    /**
     * Build and return configured Auth instance.
     *
     * Automatically creates registry and policy if not already set.
     *
     * @return Auth
     *
     * @throws InvalidArgumentException If issuer is not configured.
     */
    public function build()
    {
        if (!$this->issuer) {
            throw new InvalidArgumentException("issuer is not set");
        }

        if (!$this->registry) {
            $this->useRegistry();
        }

        if (!$this->policy) {
            $this->usePolicy();
        }

        return new Auth(
            $this->issuer,
            $this->registry,
            $this->policy,
            $this->authorizer
        );
    }
}