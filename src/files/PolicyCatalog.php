<?php

namespace Arbor\files;


use Arbor\files\contracts\FilePolicyInterface;
use RuntimeException;
use LogicException;


final class PolicyCatalog
{
    private array $policies = [];
    private array $keys = [];


    public function registerPolicies(array $policies): void
    {
        foreach ($policies as $fqn) {
            $this->registerPolicy($fqn);
            $this->registerKeys($fqn);
        }
    }

    public function hasPolicy(string $fqn): bool
    {
        return isset($this->policies[$fqn]);
    }


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

        if (!empty($options)) {
            $policy = $policy->withOptions($options);
        }

        return $policy;
    }


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
