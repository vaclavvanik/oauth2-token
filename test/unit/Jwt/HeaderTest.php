<?php

declare(strict_types=1);

namespace VaclavVanikTest\Oauth2Token\Jwt;

use PHPUnit\Framework\TestCase;
use VaclavVanik\Oauth2Token\Jwt\Header;

final class HeaderTest extends TestCase
{
    public function testExposesConstructorValues(): void
    {
        $header = new Header('JWT', 'HS256');

        $this->assertSame('JWT', $header->getTyp());
        $this->assertSame('HS256', $header->getAlg());
    }

    public function testFromArrayMapsKeysAndDefaults(): void
    {
        $header = Header::fromArray([Header::ALG => 'RS256']);

        $this->assertSame('', $header->getTyp());
        $this->assertSame('RS256', $header->getAlg());
    }
}
