<?php

namespace Arbor\files;

use Arbor\files\policies\FilePolicyInterface;
use RuntimeException;
use LogicException;


final class PolicyCatalog
{
    private array $policies = [];
    private array $namespaces = [];
    private array $mimes = [];


    public function registerPolicies(array $policies): void
    {
        foreach ($policies as $policyFqn) {
            $this->registerPolicy($policyFqn);
        }
    }


    public function registerPolicy(string $policyFqn): void
    {
        $policy = $this->registerPolicyInstance($policyFqn);

        // Register namespace → policyFqn
        $this->registerNamespace($policy);

        // Register mimes -> policyFqn
        $this->registerMimes($policy);
    }


    protected function registerPolicyInstance(string $policyFqn): FilePolicyInterface
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

        // Prevent duplicate registration
        if (isset($this->policies[$policyFqn])) {
            throw new RuntimeException("policy already registered");
        }


        // registering policy.
        $policy = new $policyFqn();
        $this->policies[$policyFqn] = $policy;

        return $policy;
    }


    protected function registerNamespace(FilePolicyInterface $policy)
    {
        $namespace = $policy->namespace();

        if ($namespace !== '') {
            if (isset($this->namespaces[$namespace])) {
                throw new LogicException(
                    "Duplicate policy namespace: {$namespace}"
                );
            }

            $this->namespaces[$namespace] = $policy::class;
        }
    }


    protected function registerMimes(FilePolicyInterface $policy)
    {
        $namespace = $policy->namespace() ?: '*';

        if (str_contains($namespace, '/')) {
            throw new LogicException(
                "Policy namespace must not contain '/'"
            );
        }

        foreach ($policy->mimes() as $mime) {

            if (!is_string($mime) || $mime === '') {
                throw new LogicException(
                    "Invalid mime declared by policy " . $policy::class
                );
            }

            $key = "{$namespace}/{$mime}";

            if (isset($this->mimes[$key])) {
                throw new LogicException(
                    "Duplicate policy match {$key}"
                );
            }

            $this->mimes[$key] = $policy::class;
        }
    }


    public function hasPolicy(string $policyFqn): bool
    {
        return isset($this->policies[$policyFqn]);
    }


    public function resolve(string $selector, array $options = []): FilePolicyInterface
    {
        $key = $this->normalizeSelector($selector);

        if (!isset($this->mimes[$key])) {
            throw new RuntimeException(
                "No policy resolves '{$selector}'"
            );
        }

        $policyFqn = $this->mimes[$key];
        $policy = $this->policies[$policyFqn];

        if ($options !== []) {
            $policy = $policy->withOptions($options);
        }

        return $policy;
    }


    private function normalizeSelector(string $selector): string
    {
        $parts = explode('/', $selector);
        $partsCount = count($parts);

        if ($partsCount === 2) {
            [$type, $sub] = $parts;
            return "*/{$type}/{$sub}";
        }

        if ($partsCount === 3) {
            [$namespace, $type, $sub] = $parts;
            return "{$namespace}/{$type}/{$sub}";
        }

        throw new LogicException("Invalid policy selector '{$selector}'");
    }
}
