<?php

declare(strict_types=1);

namespace Porkbun\Exception;

use Exception;

abstract class PorkbunApiException extends Exception implements ExceptionInterface
{
}
