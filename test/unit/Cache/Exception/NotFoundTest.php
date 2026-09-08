<?php

declare(strict_types=1);

namespace VaclavVanikTest\Oauth2Token\Cache\Exception;

use DomainException;
use PHPUnit\Framework\TestCase;
use VaclavVanik\Oauth2Token\Cache\Exception\Exception;
use VaclavVanik\Oauth2Token\Cache\Exception\NotFound;
use VaclavVanik\Oauth2Token\Cache\Key;
use VaclavVanik\Oauth2Token\Exception\Exception as PackageException;

final class NotFoundTest extends TestCase
{
    public function testIsDomainExceptionAndPackageException(): void
    {
        $exception = new NotFound('client-id');

        $this->assertInstanceOf(DomainException::class, $exception);
        $this->assertInstanceOf(Exception::class, $exception);
        $this->assertInstanceOf(PackageException::class, $exception);
    }

    public function testFromKeyExposesTheClientId(): void
    {
        $exception = NotFound::fromKey(new Key('client-id', 'client-secret', null));

        $this->assertSame('client-id', $exception->getClientId());
    }

    public function testFromKeyMessageNamesTheClientIdButNeverTheSecret(): void
    {
        $exception = NotFound::fromKey(new Key('client-id', 'top-secret', 'a-scope'));

        $this->assertStringContainsString('client-id', $exception->getMessage());
        $this->assertStringNotContainsString('top-secret', $exception->getMessage());
    }
}
