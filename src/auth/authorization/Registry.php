<?php

namespace Arbor\auth\authorization;

use Arbor\auth\authorization\ActionInterface;
use LogicException;


class Registry
{
    private array $abilities = [];
    private array $index = [];


    public function register(string $id, string $resource, ActionInterface $action)
    {
        $actionKey = $action->key();

        if (isset($this->abilities[$id])) {
            throw new LogicException("Ability id [$id] already registered.");
        }

        if (isset($this->index[$resource][$actionKey])) {
            throw new LogicException(
                "Ability already registered for [$resource::$actionKey]."
            );
        }

        $this->abilities[$id] = [$resource, $actionKey];
        $this->index[$resource][$actionKey] = $id;
    }

    public function getAbilityId(string $resource, ActionInterface $action): ?string
    {
        return $this->index[$resource][$action->key()] ?? null;
    }


    public function abilityIds(): array
    {
        return array_keys($this->abilities);
    }
}
