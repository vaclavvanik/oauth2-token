<?php

declare(strict_types=1);

namespace VaclavVanik\Oauth2Token\Exception;

use RuntimeException;

class Runtime extends RuntimeException implements Exception
{
    use FromThrowable;
}
