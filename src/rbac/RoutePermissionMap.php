<?php

namespace Arbor\rbac;


use Arbor\router\Router;


class RoutePermissionMap
{
    public function __construct(Router $router)
    {
        $tree = $router->getRouteTree();
        $id = $tree->children['login']->groupId;

        print_r($router->getGroupById($id));
        print_r($tree);
    }
}
