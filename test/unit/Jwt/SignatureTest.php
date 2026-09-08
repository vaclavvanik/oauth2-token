<?php

declare(strict_types=1);

namespace VaclavVanikTest\Oauth2Token\Jwt;

use PHPUnit\Framework\TestCase;
use VaclavVanik\Oauth2Token\Jwt\Signature;

final class SignatureTest extends TestCase
{
    public function testExposesValue(): void
    {
        $this->assertSame('abc', (new Signature('abc'))->getValue());
        $this->assertSame('abc', Signature::fromString('abc')->getValue());
    }
}
