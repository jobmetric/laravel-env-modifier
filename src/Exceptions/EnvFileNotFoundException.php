<?php

namespace JobMetric\EnvModifier\Exceptions;

use Exception;
use Throwable;

class EnvFileNotFoundException extends Exception
{
    public function __construct(string $path, int $code = 400, ?Throwable $previous = null)
    {
        parent::__construct("Env file path: $path not found!", $code, $previous);
    }
}
