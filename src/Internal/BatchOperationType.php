<?php

declare(strict_types=1);

namespace Porkbun\Internal;

/**
 * @internal
 */
enum BatchOperationType: string
{
    case CREATE = 'create';
    case UPDATE = 'update';
    case DELETE = 'delete';
    case DELETE_BY_NAME_TYPE = 'deleteByNameType';
}
