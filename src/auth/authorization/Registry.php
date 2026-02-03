<?php

namespace Arbor\auth\authorization;

use Arbor\auth\authorization\ActionInterface;
use InvalidArgumentException;
use LogicException;


class Registry
{
    private array $abilities = [];
    private array $index = [];

    private ?string $resourceFqn = null;
    private ?string $actionFqn = null;


    public function register(string $id, ResourceInterface|string $resource, ActionInterface $action)
    {
        $resourceValue = $this->resolveResource($resource);
        $actionValue = $this->resolveAction($action);

        if (isset($this->abilities[$id])) {
            throw new LogicException("Ability id [$id] already registered.");
        }

        if (isset($this->index[$resourceValue][$actionValue])) {
            throw new LogicException(
                "Ability already registered for [$resourceValue::$actionValue]."
            );
        }

        $this->abilities[$id] = [$resourceValue, $actionValue];
        $this->index[$resourceValue][$actionValue] = $id;
    }


    public function getAbilityId(ResourceInterface|string $resource, ActionInterface $action): ?string
    {
        $resourceValue = $this->resolveResource($resource);
        $actionValue = $this->resolveAction($action);

        return $this->index[$resourceValue][$actionValue] ?? null;
    }


    protected function resolveResource(string|ResourceInterface $resource): string
    {
        if ($resource instanceof ResourceInterface) {
            // guard
            $this->assertEnumFqn($resource);
            return $resource->key();
        }

        if (is_string($resource) && class_exists($resource)) {
            return $resource;
        }

        throw new InvalidArgumentException("Resource must be a valid class name or ResourceInterface Enum");
    }


    protected function resolveAction(ActionInterface $action): string
    {
        // guard
        $this->assertEnumFqn($action);
        return $action->key();
    }


    protected function assertEnumFqn(ResourceInterface|ActionInterface $enum): void
    {
        $fqn = $enum::class;

        if ($enum instanceof ResourceInterface) {
            if ($this->resourceFqn !== null && $this->resourceFqn !== $fqn) {
                throw new LogicException(
                    "Resource enum FQN mismatch. Expected [{$this->resourceFqn}], got [$fqn]."
                );
            }

            $this->resourceFqn ??= $fqn;
            return;
        }

        if ($enum instanceof ActionInterface) {
            if ($this->actionFqn !== null && $this->actionFqn !== $fqn) {
                throw new LogicException(
                    "Action enum FQN mismatch. Expected [{$this->actionFqn}], got [$fqn]."
                );
            }

            $this->actionFqn ??= $fqn;
            return;
        }

        // defensive — should never hit due to union type
        throw new LogicException("Unsupported enum type for FQN assertion.");
    }


    public function abilityIds(): array
    {
        return array_keys($this->abilities);
    }
}
