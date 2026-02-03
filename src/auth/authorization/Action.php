<?php

namespace Arbor\auth\authorization;

/**
 * Action
 *
 * An enumeration of standard authorization actions that can be performed on resources.
 * This enum defines a comprehensive set of common operations across CRUD, state management,
 * relationships, execution, and access control use cases.
 *
 * @package Arbor\auth\authorization
 */
enum Action: string implements ActionInterface
{
    use AuthEnumTrait;

    // CRUD
    /** Create a new resource */
    case CREATE = 'create';
    /** Read or retrieve a resource */
    case READ   = 'read';
    /** Update an existing resource */
    case UPDATE = 'update';
    /** Delete a resource */
    case DELETE = 'delete';

    // Collection / listing
    /** List or retrieve multiple resources */
    case LIST   = 'list';

    // State changes
    /** Enable a resource */
    case ENABLE  = 'enable';
    /** Disable a resource */
    case DISABLE = 'disable';
    /** Archive a resource */
    case ARCHIVE = 'archive';
    /** Restore a previously archived resource */
    case RESTORE = 'restore';

    // Ownership / relationship
    /** Attach a resource to another resource */
    case ATTACH = 'attach';
    /** Detach a resource from another resource */
    case DETACH = 'detach';
    /** Assign a resource to someone or something */
    case ASSIGN = 'assign';
    /** Revoke access or assignment */
    case REVOKE = 'revoke';

    // Execution / side effects
    /** Execute a resource or action */
    case EXECUTE = 'execute';
    /** Approve a resource or action */
    case APPROVE = 'approve';
    /** Reject a resource or action */
    case REJECT  = 'reject';

    // Visibility / access
    /** View or access a resource */
    case VIEW   = 'view';
    /** Export a resource */
    case EXPORT = 'export';
    /** Import a resource */
    case IMPORT = 'import';
}
