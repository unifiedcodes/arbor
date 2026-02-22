<?php

namespace Arbor\files;


use Arbor\files\contracts\FilePolicyInterface;
use RuntimeException;
use LogicException;


/**
 * Registry for file policy classes, providing registration and resolution of
 * {@see FilePolicyInterface} implementations by interface, scheme, and MIME type.
 *
 * Policies are keyed internally by a compound "{scheme}/{mime}" string scoped
 * under each concrete policy interface (e.g. IngressPolicyInterface). This allows
 * multiple policy interfaces to share the same scheme/MIME space without collision,
 * while duplicate registrations within a single interface are detected and rejected
 * eagerly at registration time.
 *
 * Resolution validates the requested interface, confirms at least one policy is
 * registered for it, and locates the specific policy matching the given scheme and
 * MIME type before returning a configured instance.
 *
 * @package Arbor\files
 */
final class PolicyCatalog
{
    /** @var array<string, FilePolicyInterface> Map of policy FQN to instantiated policy object. */
    private array $policies = [];

    /** @var array<string, array<string, string>> Map of interface FQN to compound key to policy FQN. */
    private array $keys = [];


    /**
     * Registers an ordered list of policy class FQNs with the catalog.
     *
     * Each entry is instantiated, validated, and indexed by its declared scheme,
     * MIME types, and implemented policy interfaces. Registration fails fast on
     * the first invalid or duplicate entry.
     *
     * @param array<string> $policies Fully-qualified class names of policy classes to register.
     *
     * @return void
     *
     * @throws RuntimeException If any class does not exist or does not implement {@see FilePolicyInterface}.
     * @throws RuntimeException If any policy FQN is registered more than once.
     * @throws LogicException   If any policy declares an invalid scheme or MIME value.
     * @throws LogicException   If a duplicate scheme/MIME key is detected for the same interface.
     */
    public function registerPolicies(array $policies): void
    {
        foreach ($policies as $fqn) {
            $this->registerPolicy($fqn);
            $this->registerKeys($fqn);
        }
    }


    /**
     * Returns whether a policy with the given FQN has been registered.
     *
     * @param string $fqn Fully-qualified class name of the policy to check.
     *
     * @return bool True if the policy is registered, false otherwise.
     */
    public function hasPolicy(string $fqn): bool
    {
        return isset($this->policies[$fqn]);
    }


    /**
     * Resolves and returns the policy instance matching the given interface, scheme,
     * and MIME type, with the provided options applied.
     *
     * Resolution proceeds as follows:
     *  1. The interface is validated to exist and extend {@see FilePolicyInterface}.
     *  2. At least one policy must be registered under the interface.
     *  3. A compound key of "{scheme}/{mime}" is looked up within that interface's index.
     *  4. The matching policy instance is retrieved and returned with options applied.
     *
     * @param string $interfaceFqn Fully-qualified name of the policy interface to resolve against.
     * @param string $scheme       The storage scheme identifier (e.g. "avatars", "documents").
     * @param string $mime         The MIME type of the file being processed (e.g. "image/png").
     * @param array  $options      Optional overrides to apply to the resolved policy instance.
     *
     * @return FilePolicyInterface The resolved and configured policy instance.
     *
     * @throws RuntimeException If the interface does not exist or does not extend {@see FilePolicyInterface}.
     * @throws RuntimeException If no policies are registered for the given interface.
     * @throws RuntimeException If no policy matches the given scheme and MIME combination.
     * @throws RuntimeException If the matched policy FQN is not present in the policy registry.
     */
    public function resolvePolicy(string $interfaceFqn, string $scheme, string $mime, array $options = []): FilePolicyInterface
    {
        if (!interface_exists($interfaceFqn)) {
            throw new RuntimeException(
                "{$interfaceFqn} does not exist."
            );
        }

        if (!is_subclass_of($interfaceFqn, FilePolicyInterface::class)) {
            throw new RuntimeException(
                "{$interfaceFqn} must extend FilePolicyInterface."
            );
        }

        if (!isset($this->keys[$interfaceFqn])) {
            throw new RuntimeException(
                "No policies registered for {$interfaceFqn}."
            );
        }

        // prepare a key
        $key = "{$scheme}/{$mime}";

        // check if we have the key
        if (!isset($this->keys[$interfaceFqn][$key])) {
            throw new RuntimeException(
                "No policy resolves scheme '{$scheme}', mime '{$key}' for {$interfaceFqn}."
            );
        }

        $policyFqn = $this->keys[$interfaceFqn][$key];

        if (!isset($this->policies[$policyFqn])) {
            throw new RuntimeException(
                "Policy {$policyFqn} is not registered."
            );
        }

        $policy = $this->policies[$policyFqn];

        $policy->withOptions($options);

        return $policy;
    }


    /**
     * Instantiates and registers a single policy class by its FQN.
     *
     * Validates that the class exists, implements {@see FilePolicyInterface}, and
     * has not already been registered before storing the new instance.
     *
     * @param string $fqn Fully-qualified class name of the policy to register.
     *
     * @return void
     *
     * @throws RuntimeException If the class does not exist.
     * @throws RuntimeException If the class does not implement {@see FilePolicyInterface}.
     * @throws RuntimeException If the policy has already been registered.
     */
    protected function registerPolicy(string $fqn): void
    {
        if (!class_exists($fqn)) {
            throw new RuntimeException(
                "Policy class {$fqn} does not exist"
            );
        }

        if (!is_subclass_of($fqn, FilePolicyInterface::class)) {
            throw new RuntimeException(
                "Policy {$fqn} must implement FilePolicyInterface"
            );
        }

        if (isset($this->policies[$fqn])) {
            throw new RuntimeException("Policy already registered");
        }

        $policy = new $fqn();
        $this->policies[$fqn] = $policy;
    }


    /**
     * Indexes a registered policy under each of its declared scheme/MIME keys.
     *
     * Reads the policy's scheme and MIME list, validates both, then delegates to
     * {@see self::registerKey()} for each resulting compound key. The policy must
     * already be present in the policy registry before this method is called.
     *
     * @param string $fqn Fully-qualified class name of an already-registered policy.
     *
     * @return void
     *
     * @throws RuntimeException If the policy is not yet registered.
     * @throws LogicException   If the policy's declared scheme is empty or contains a slash.
     * @throws LogicException   If any declared MIME value is an empty string.
     */
    protected function registerKeys(string $fqn): void
    {
        if (!$this->hasPolicy($fqn)) {
            throw new RuntimeException("Policy {$fqn} does not exists.");
        }

        $policy = $this->policies[$fqn];
        $scheme = $policy->scheme();

        if ($scheme === '' || str_contains($scheme, '/')) {
            throw new LogicException(
                "invalid scheme declared by policy {$fqn}"
            );
        }

        foreach ($policy->mimes() as $mime) {

            if ($mime === '') {
                throw new LogicException(
                    "Invalid mime declared by policy " . $policy::class
                );
            }

            $key = "{$scheme}/{$mime}";

            $this->registerKey($key, $fqn);
        }
    }


    /**
     * Registers a single compound key under every concrete policy sub-interface
     * implemented by the given policy class.
     *
     * {@see FilePolicyInterface} itself is excluded from indexing; only interfaces
     * that extend it (e.g. IngressPolicyInterface, VariantsPolicyInterface) are
     * used as index buckets. Duplicate keys within the same interface bucket are
     * rejected immediately.
     *
     * @param string $key The compound "{scheme}/{mime}" key to register.
     * @param string $fqn The fully-qualified class name of the policy to associate with the key.
     *
     * @return void
     *
     * @throws LogicException If the key is already registered for any of the policy's interfaces.
     */
    protected function registerKey(string $key, string $fqn): void
    {
        $interfaces = class_implements($fqn);

        foreach ($interfaces as $interface) {

            if (!is_subclass_of($interface, FilePolicyInterface::class)) {
                continue;
            }

            if ($interface === FilePolicyInterface::class) {
                continue;
            }

            if (isset($this->keys[$interface][$key])) {
                throw new LogicException(
                    "Duplicate policy registration for {$interface} with key {$key}."
                );
            }

            $this->keys[$interface][$key] = $fqn;
        }
    }
}
