<?php

declare(strict_types=1);

namespace Porkbun\Internal;

/**
 * @internal
 */
enum BatchOperationType: string
{
    case CREATE = 'create';
    case EDIT = 'edit';
    case DELETE = 'delete';
    case DELETE_BY_NAME_TYPE = 'deleteByNameType';
}
