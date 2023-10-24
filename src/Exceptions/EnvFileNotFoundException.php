<?php

namespace JobMetric\EnvModifier\Exceptions;

use Exception;
use Throwable;

class EnvFileNotFoundException extends Exception
{
    public function __construct(string $path, int $code = 400, ?Throwable $previous = null)
    {
        $message = 'Env file path: "'.$path.'" not found!';

        parent::__construct($message, $code, $previous);
    }
}
