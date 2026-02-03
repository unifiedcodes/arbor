<?php

namespace Arbor\auth;

use Arbor\auth\authorization\ActionInterface;
use Arbor\auth\authorization\ResourceInterface;
use Arbor\auth\authorization\Registry;
use Arbor\auth\AuthContext;
use RuntimeException;

/**
 * Authorizer
 *
 * Manages authorization by registering abilities (resource-action combinations)
 * and checking if authenticated users have permission to perform specific actions
 * on resources.
 *
 * @package Arbor\auth
 */
class Authorizer
{
    /** @var Registry The registry managing ability definitions */
    private Registry $registry;

    /**
     * Constructor
     *
     * Initializes the Authorizer with a new Registry instance for managing abilities.
     */
    public function __construct()
    {
        $this->registry = new Registry();
    }


    /**
     * Register a new ability
     *
     * Defines a new ability by mapping a resource and action to a unique ability identifier.
     * This ability can then be checked against authenticated users.
     *
     * @param string $id The unique identifier for this ability
     * @param ResourceInterface|string $resource The resource the ability applies to
     * @param ActionInterface $action The action the ability allows
     *
     * @throws LogicException If the ability ID is already registered
     * @throws LogicException If the resource-action combination is already registered
     *
     * @return void
     */
    public function addAbility(
        string $id,
        ResourceInterface|string $resource,
        ActionInterface $action
    ) {
        $this->registry->register($id, $resource, $action);
    }


    /**
     * Check if a user has permission for an action
     *
     * Verifies that the authenticated user in the provided AuthContext has the ability
     * to perform the specified action on the given resource. Throws an exception if the
     * ability is not registered or if the user does not have permission.
     *
     * @param AuthContext $authContext The authenticated user context
     * @param ResourceInterface|string $resource The resource to check access for
     * @param ActionInterface $action The action to check authorization for
     *
     * @throws RuntimeException If the ability is not registered
     * @throws RuntimeException If the user does not have permission for this action
     *
     * @return void
     */
    public function hasAbility(
        AuthContext $authContext,
        ResourceInterface|string $resource,
        ActionInterface $action
    ): void {
        $abilityId = $this->registry->getAbilityId($resource, $action);


        if ($abilityId === null) {
            throw new RuntimeException("ability not registered");
        }

        if (!$authContext->hasAbility($abilityId)) {
            throw new RuntimeException("no permission for this action");
        }
    }


    /**
     * Get all registered ability IDs
     *
     * Returns a list of all ability identifiers that have been registered
     * with this authorizer.
     *
     * @return array An array of ability ID strings
     */
    public function abilityIds(): array
    {
        return $this->registry->abilityIds();
    }
}
