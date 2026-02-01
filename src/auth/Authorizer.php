<?php

namespace Arbor\auth;

use Arbor\auth\authorization\ActionInterface;
use Arbor\auth\authorization\Registry;
use Arbor\auth\authorization\Evaluator;
use Arbor\auth\AuthContext;
use RuntimeException;


class Authorizer
{
    private Registry $registry;
    private Evaluator $evaluator;


    public function __construct()
    {
        $this->registry = new Registry();
        $this->evaluator = new Evaluator();
    }


    public function addAbility(string $id, string $resource, ActionInterface $action)
    {
        $this->registry->register($id, $resource, $action);
    }


    public function hasAbility(AuthContext $authContext, string $resource, ActionInterface $action): void
    {
        $abilityId = $this->registry->getAbilityId($resource, $action);

        if ($abilityId === null) {
            throw new RuntimeException("ability not registered");
        }

        $this->evaluator->resolve($authContext, $abilityId);
    }


    public function abilityIds(): array
    {
        return $this->registry->abilityIds();
    }
}
