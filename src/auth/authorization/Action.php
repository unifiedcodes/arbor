<?php

namespace Arbor\auth\authorization;

enum Action: string implements ActionInterface
{
    use AuthEnumTrait;

        // CRUD
    case CREATE = 'create';
    case READ   = 'read';
    case UPDATE = 'update';
    case DELETE = 'delete';

        // Collection / listing
    case LIST   = 'list';

        // State changes
    case ENABLE  = 'enable';
    case DISABLE = 'disable';
    case ARCHIVE = 'archive';
    case RESTORE = 'restore';

        // Ownership / relationship
    case ATTACH = 'attach';
    case DETACH = 'detach';
    case ASSIGN = 'assign';
    case REVOKE = 'revoke';

        // Execution / side effects
    case EXECUTE = 'execute';
    case APPROVE = 'approve';
    case REJECT  = 'reject';

        // Visibility / access
    case VIEW   = 'view';
    case EXPORT = 'export';
    case IMPORT = 'import';
}
