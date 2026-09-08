<?php

declare(strict_types=1);

namespace VaclavVanikTest\Oauth2Token\Jwt;

use PHPUnit\Framework\TestCase;
use VaclavVanik\Oauth2Token\Jwt\Header;
use VaclavVanik\Oauth2Token\Jwt\Jwt;
use VaclavVanik\Oauth2Token\Jwt\Payload;
use VaclavVanik\Oauth2Token\Jwt\Signature;

final class JwtTest extends TestCase
{
    public function testExposesParts(): void
    {
        $header = new Header('JWT', 'HS256');
        $payload = Payload::fromArray([Payload::EXPIRATION_TIME => 1893456000]);
        $signature = new Signature('sig');

        $jwt = new Jwt($header, $payload, $signature);

        $this->assertSame($header, $jwt->getHeader());
        $this->assertSame($payload, $jwt->getPayload());
        $this->assertSame($signature, $jwt->getSignature());
    }
}
