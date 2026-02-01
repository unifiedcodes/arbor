<?php

namespace Arbor\auth\authorization;

use Arbor\auth\AuthContext;
use RuntimeException;

class Evaluator
{
    public function resolve(AuthContext $authContext, $abilityId)
    {
        if (!$authContext->hasAbility($abilityId)) {
            throw new RuntimeException("no permission for this action");
        }
    }
}
