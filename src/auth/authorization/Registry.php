<?php

namespace Arbor\auth\authorization;

use Arbor\auth\authorization\ActionInterface;
use InvalidArgumentException;
use LogicException;

/**
 * Registry
 *
 * A registry for managing and querying authorization abilities.
 * 
 * This class maintains a bidirectional mapping of ability identifiers to
 * resource-action pairs, ensuring unique registrations and consistent enum usage.
 * It provides methods to register new abilities and retrieve ability IDs based on
 * resource and action combinations.
 *
 * @package Arbor\auth\authorization
 */
class Registry
{
    /** @var array<string, array<string, string>> Mapping of ability IDs to [resource, action] pairs */
    private array $abilities = [];

    /** @var array<string, array<string, string>> Index mapping resources and actions to ability IDs */
    private array $index = [];

    /** @var ?string The fully qualified name of the resource enum class */
    private ?string $resourceFqn = null;

    /** @var ?string The fully qualified name of the action enum class */
    private ?string $actionFqn = null;


    /**
     * Register a new ability.
     *
     * Creates a mapping between an ability ID and a resource-action pair.
     * Enforces uniqueness of ability IDs and resource-action combinations.
     * All enums must be of consistent types (same FQN) throughout the registry.
     *
     * @param string $id The unique identifier for this ability
     * @param ResourceInterface|string $resource The resource enum or class name
     * @param ActionInterface $action The action enum
     *
     * @throws LogicException If the ability ID is already registered
     * @throws LogicException If the resource-action combination is already registered
     * @throws LogicException If enum FQN does not match previously registered enums
     * @throws InvalidArgumentException If resource is invalid
     *
     * @return void
     */
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


    /**
     * Get the ability ID for a resource-action combination.
     *
     * Retrieves the ability ID associated with the given resource and action pair.
     * Returns null if no ability is registered for the combination.
     *
     * @param ResourceInterface|string $resource The resource enum or class name
     * @param ActionInterface $action The action enum
     *
     * @return ?string The ability ID if registered, null otherwise
     */
    public function getAbilityId(ResourceInterface|string $resource, ActionInterface $action): ?string
    {
        $resourceValue = $this->resolveResource($resource);
        $actionValue = $this->resolveAction($action);

        return $this->index[$resourceValue][$actionValue] ?? null;
    }


    /**
     * Resolve a resource to its string representation.
     *
     * Converts a resource parameter to its string value, either by extracting
     * the key from a ResourceInterface enum or validating a class name string.
     *
     * @param string|ResourceInterface $resource The resource enum or class name
     *
     * @throws InvalidArgumentException If resource is not a valid class name or ResourceInterface enum
     * @throws LogicException If enum FQN does not match previously registered resource enum
     *
     * @return string The resolved resource value
     */
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


    /**
     * Resolve an action to its string representation.
     *
     * Extracts the key value from an ActionInterface enum.
     *
     * @param ActionInterface $action The action enum
     *
     * @throws LogicException If enum FQN does not match previously registered action enum
     *
     * @return string The resolved action value
     */
    protected function resolveAction(ActionInterface $action): string
    {
        // guard
        $this->assertEnumFqn($action);
        return $action->key();
    }


    /**
     * Assert that an enum's fully qualified name matches expected FQN.
     *
     * Validates that all ResourceInterface and ActionInterface enums used
     * with this registry are of consistent types. Stores the FQN of the first
     * enum encountered and enforces that all subsequent enums match.
     *
     * @param ResourceInterface|ActionInterface $enum The enum to validate
     *
     * @throws LogicException If resource enum FQN does not match the first registered resource enum
     * @throws LogicException If action enum FQN does not match the first registered action enum
     * @throws LogicException If enum type is unsupported (defensive, should not occur)
     *
     * @return void
     */
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


    /**
     * Get all registered ability IDs.
     *
     * Returns a list of all ability identifiers that have been registered
     * with this registry.
     *
     * @return array An array of ability ID strings
     */
    public function abilityIds(): array
    {
        return array_keys($this->abilities);
    }
}
