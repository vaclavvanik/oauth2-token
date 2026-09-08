<?php

declare(strict_types=1);

namespace VaclavVanikTest\Oauth2Token\Exception;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use VaclavVanik\Oauth2Token\Exception\Exception;
use VaclavVanik\Oauth2Token\Exception\Runtime;

final class RuntimeTest extends TestCase
{
    public function testIsRuntimeExceptionAndPackageException(): void
    {
        $exception = new Runtime('message');

        $this->assertInstanceOf(RuntimeException::class, $exception);
        $this->assertInstanceOf(Exception::class, $exception);
    }

    public function testFromThrowableKeepsMessageAndPrevious(): void
    {
        $previous = new RuntimeException('boom', 7);

        $exception = Runtime::fromThrowable($previous);

        $this->assertSame('boom', $exception->getMessage());
        $this->assertSame(7, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
