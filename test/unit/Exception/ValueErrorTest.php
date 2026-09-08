<?php

declare(strict_types=1);

namespace VaclavVanikTest\Oauth2Token\Exception;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use VaclavVanik\Oauth2Token\Exception\Exception as PackageException;
use VaclavVanik\Oauth2Token\Exception\ValueError;

final class ValueErrorTest extends TestCase
{
    public function testIsAnInvalidArgumentExceptionForNow(): void
    {
        // Until the package requires PHP >= 8.0 this extends \InvalidArgumentException; afterwards it becomes
        // the native \ValueError. Either way the FQCN stays the same - callers must not catch it by type.
        $exception = new ValueError('bad value');

        $this->assertInstanceOf(InvalidArgumentException::class, $exception);
        $this->assertSame('bad value', $exception->getMessage());
    }

    public function testIsNotPartOfThePackageExceptionMarker(): void
    {
        // A ValueError signals a bug in the caller, not a runtime condition - it stays out of the catchable
        // Exception\Exception hierarchy.
        $this->assertNotInstanceOf(PackageException::class, new ValueError('bad value'));
    }
}
