<?php

namespace Arbor\storage\namespace;


enum DefaultNamespace: string implements NamespaceInterface
{
    use NamespaceTrait;

    case DEFAULT = '';
}
