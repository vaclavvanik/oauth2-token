<?php

declare(strict_types=1);

namespace VaclavVanikTest\Oauth2Token\Jwt;

use PHPUnit\Framework\TestCase;
use VaclavVanik\Oauth2Token\Jwt\Payload;

final class PayloadTest extends TestCase
{
    public function testFromArrayMapsRegisteredClaims(): void
    {
        $payload = Payload::fromArray([
            Payload::ISSUER => 'issuer',
            Payload::SUBJECT => 'subject',
            Payload::AUDIENCE => 'audience',
            Payload::EXPIRATION_TIME => 1893456000,
            Payload::NOT_BEFORE => 1893452400,
            Payload::ISSUED_AT => 1893452401,
            Payload::JWT_ID => 'token-id',
        ]);

        $this->assertSame('issuer', $payload->getIssuer());
        $this->assertSame('subject', $payload->getSubject());
        $this->assertSame('audience', $payload->getAudience());
        $this->assertTrue($payload->hasExpirationTime());
        $this->assertSame(1893456000, $payload->getExpirationTime()->getTimestamp());
        $this->assertSame(1893452400, $payload->getNotBefore()->getTimestamp());
        $this->assertSame(1893452401, $payload->getIssuedAt()->getTimestamp());
        $this->assertSame('token-id', $payload->getJwtId());
    }

    public function testTimeClaimsAreNullWhenAbsent(): void
    {
        $payload = Payload::fromArray([]);

        $this->assertSame('', $payload->getIssuer());
        $this->assertSame('', $payload->getJwtId());

        $this->assertFalse($payload->hasExpirationTime());
        $this->assertNull($payload->getExpirationTime());
        $this->assertFalse($payload->hasNotBefore());
        $this->assertNull($payload->getNotBefore());
        $this->assertFalse($payload->hasIssuedAt());
        $this->assertNull($payload->getIssuedAt());
    }

    public function testFromArrayCoercesFloatingPointNumericDate(): void
    {
        $payload = Payload::fromArray([Payload::EXPIRATION_TIME => 1893456000.9]);

        $this->assertSame(1893456000, $payload->getExpirationTime()->getTimestamp());
    }
}
