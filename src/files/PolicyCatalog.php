<?php

namespace Arbor\files;

use Arbor\files\policy\FilePolicyInterface;
use RuntimeException;
use LogicException;


final class PolicyCatalog
{
    /**
     * All registered policy instances
     *
     * @var FilePolicyInterface[]
     */
    private array $policies = [];

    /**
     * Namespace → policy
     *
     * @var array<string, FilePolicyInterface>
     */
    private array $byNamespace = [];

    /**
     * Mime pattern → list of policies
     *
     * @var array<string, FilePolicyInterface[]>
     */
    private array $byMime = [];

    /**
     * Register multiple policy FQNs.
     *
     * @param array<class-string<FilePolicyInterface>> $policies
     */
    public function registerPolicies(array $policies): void
    {
        foreach ($policies as $policyFqn) {
            $this->registerPolicy($policyFqn);
        }
    }

    /**
     * Register a single policy FQN.
     *
     * @param class-string<FilePolicyInterface> $policyFqn
     */
    public function registerPolicy(string $policyFqn): void
    {
        if (!class_exists($policyFqn)) {
            throw new RuntimeException(
                "Policy class {$policyFqn} does not exist"
            );
        }

        if (!is_subclass_of($policyFqn, FilePolicyInterface::class)) {
            throw new RuntimeException(
                "Policy {$policyFqn} must implement FilePolicyInterface"
            );
        }

        // Instantiate once — policies are descriptors
        $policy = new $policyFqn();

        // Prevent duplicate registration
        if (in_array($policy, $this->policies, true)) {
            return;
        }

        // Register namespace
        $namespace = $policy->namespace();

        if ($namespace !== '') {
            if (isset($this->byNamespace[$namespace])) {
                throw new LogicException(
                    "Duplicate policy namespace: {$namespace}"
                );
            }

            $this->byNamespace[$namespace] = $policy;
        }

        // Register mimes
        foreach ($policy->mimes() as $mime) {
            if (!is_string($mime) || $mime === '') {
                throw new LogicException(
                    "Invalid mime declared by policy {$policyFqn}"
                );
            }

            $this->byMime[$mime][] = $policy;
        }

        $this->policies[] = $policy;
    }

    /**
     * Resolve policy by claimed mime.
     *
     * First matching policy wins.
     */
    public function resolvePolicy(string $claimedMime): FilePolicyInterface
    {
        foreach ($this->byMime as $pattern => $policies) {
            if ($this->mimeMatches($pattern, $claimedMime)) {
                return $policies[0];
            }
        }

        throw new RuntimeException(
            "No policy supports file type {$claimedMime}"
        );
    }

    /**
     * Resolve policy by namespace.
     */
    public function policyByNamespace(string $namespace): FilePolicyInterface
    {
        if (!isset($this->byNamespace[$namespace])) {
            throw new RuntimeException(
                "No policy registered for namespace {$namespace}"
            );
        }

        return $this->byNamespace[$namespace];
    }

    /**
     * Mime pattern matcher.
     */
    private function mimeMatches(string $pattern, string $mime): bool
    {
        if ($pattern === $mime) {
            return true;
        }

        if (str_ends_with($pattern, '/*')) {
            return str_starts_with(
                $mime,
                rtrim($pattern, '*')
            );
        }

        return false;
    }
}
