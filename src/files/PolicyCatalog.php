<?php

namespace Arbor\files;


use Arbor\files\contracts\FilePolicyInterface;
use RuntimeException;
use LogicException;


final class PolicyCatalog
{
    private array $policies = [];
    private array $schemes = [];
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

        $this->registerScheme($policy);
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

        if (isset($this->policies[$policyFqn])) {
            throw new RuntimeException("Policy already registered");
        }

        $policy = new $policyFqn();
        $this->policies[$policyFqn] = $policy;

        return $policy;
    }


    protected function registerScheme(FilePolicyInterface $policy): void
    {
        $scheme = $policy->scheme();

        if ($scheme === '') {
            return;
        }

        if (isset($this->schemes[$scheme])) {
            throw new LogicException(
                "Duplicate policy scheme: {$scheme}"
            );
        }

        $this->schemes[$scheme] = $policy::class;
    }


    protected function registerMimes(FilePolicyInterface $policy): void
    {
        $scheme = $policy->scheme();

        if (str_contains($scheme, '/')) {
            throw new LogicException(
                "Policy scheme must not contain '/'"
            );
        }

        foreach ($policy->mimes() as $mime) {

            if (!is_string($mime) || $mime === '') {
                throw new LogicException(
                    "Invalid mime declared by policy " . $policy::class
                );
            }

            $key = "{$scheme}/{$mime}";

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


    public function resolve(string $scheme, string $mime, array $options = []): FilePolicyInterface
    {
        if ($scheme === '') {
            throw new LogicException('Scheme must not be empty');
        }

        if ($mime === '' || !str_contains($mime, '/')) {
            throw new LogicException("Invalid mime '{$mime}'");
        }

        $key = "{$scheme}/{$mime}";

        if (!isset($this->mimes[$key])) {
            throw new RuntimeException(
                "No policy resolves '{$scheme}/{$mime}'"
            );
        }

        $policyFqn = $this->mimes[$key];
        $policy = $this->policies[$policyFqn];

        if ($options !== []) {
            $policy = $policy->withOptions($options);
        }

        return $policy;
    }
}
