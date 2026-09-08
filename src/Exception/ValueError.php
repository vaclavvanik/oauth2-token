<?php

declare(strict_types=1);

namespace VaclavVanik\Oauth2Token\Exception;

use InvalidArgumentException;

/**
 * Thrown when an argument is the right type but not an acceptable value (an empty string, a number out of
 * range). It signals a bug in the calling code, not a runtime condition to recover from.
 *
 * @internal Do not catch this by type - it will become the native \ValueError once the package requires
 *           PHP >= 8.0.
 */
final class ValueError extends InvalidArgumentException
{
}
