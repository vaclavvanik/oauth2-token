<?php

declare(strict_types=1);

namespace VaclavVanik\Oauth2Token\Exception;

use Throwable;

trait FromThrowable
{
    /** Wrap another throwable, keeping its message and code and chaining it as the previous exception. */
    public static function fromThrowable(Throwable $e): self
    {
        return new static($e->getMessage(), $e->getCode(), $e);
    }
}
