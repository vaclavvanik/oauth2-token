<?php

declare(strict_types=1);

namespace VaclavVanik\Oauth2Token\Cache\Exception;

use RuntimeException;
use VaclavVanik\Oauth2Token\Exception\FromThrowable;

class Runtime extends RuntimeException implements Exception
{
    use FromThrowable;
}
