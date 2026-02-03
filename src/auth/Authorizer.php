<?php

namespace Arbor\auth;

use Arbor\auth\authorization\ActionInterface;
use Arbor\auth\authorization\ResourceInterface;
use Arbor\auth\authorization\Registry;
use Arbor\auth\AuthContext;
use RuntimeException;


class Authorizer
{
    private Registry $registry;

    public function __construct()
    {
        $this->registry = new Registry();
    }


    public function addAbility(
        string $id,
        ResourceInterface|string $resource,
        ActionInterface $action
    ) {
        $this->registry->register($id, $resource, $action);
    }


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


    public function abilityIds(): array
    {
        return $this->registry->abilityIds();
    }
}
