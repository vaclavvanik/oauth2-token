<?php

declare(strict_types=1);

namespace VaclavVanikTest\Oauth2Token;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use VaclavVanik\Oauth2Token\AccessToken;
use VaclavVanik\Oauth2Token\Exception\Runtime;
use VaclavVanik\Oauth2Token\ExpirationValidator;
use VaclavVanikTest\Oauth2Token\Support\JwtFactory;

final class ExpirationValidatorTest extends TestCase
{
    private const NOW = '2026-01-01T12:00:00+00:00';

    public function testOpaqueTokenIsNotExpiredWellBeforeItsLifetimeEnds(): void
    {
        $token = new AccessToken('Bearer', 3600, 'opaque');
        $requestedAt = $this->now()->getTimestamp();

        $this->assertFalse((new ExpirationValidator(60))->isExpired($token, $requestedAt, $this->now()));
    }

    public function testOpaqueTokenIsExpiredAfterItsLifetimeEnds(): void
    {
        $token = new AccessToken('Bearer', 3600, 'opaque');
        $requestedAt = $this->now()->modify('-2 hours')->getTimestamp();

        $this->assertTrue((new ExpirationValidator(60))->isExpired($token, $requestedAt, $this->now()));
    }

    public function testTokenIsExpiredOnceItIsWithinTheRefreshDelta(): void
    {
        $token = new AccessToken('Bearer', 120, 'opaque');

        // Lifetime ends 60 s from now, which is inside the 120 s refresh delta.
        $requestedAt = $this->now()->modify('-60 seconds')->getTimestamp();

        $this->assertTrue((new ExpirationValidator(120))->isExpired($token, $requestedAt, $this->now()));
    }

    public function testJwtExpiresOnItsExpClaimNotOnExpiresIn(): void
    {
        $validator = new ExpirationValidator(60);

        $future = JwtFactory::create(['typ' => 'JWT'], ['exp' => $this->now()->modify('+1 hour')->getTimestamp()]);
        $past = JwtFactory::create(['typ' => 'JWT'], ['exp' => $this->now()->modify('-1 hour')->getTimestamp()]);

        // expires_in says "fresh", but the JWT exp claim wins.
        $this->assertFalse($validator->isExpired(new AccessToken('Bearer', 3600, $future), 0, $this->now()));
        $this->assertTrue($validator->isExpired(new AccessToken('Bearer', 3600, $past), 0, $this->now()));
    }

    public function testJwtWithoutExpFallsBackToExpiresIn(): void
    {
        $validator = new ExpirationValidator(60);

        $jwt = JwtFactory::create(['typ' => 'JWT'], ['sub' => 'no-exp']);
        $token = new AccessToken('Bearer', 3600, $jwt);

        $freshRequestedAt = $this->now()->getTimestamp();
        $staleRequestedAt = $this->now()->modify('-2 hours')->getTimestamp();

        $this->assertFalse($validator->isExpired($token, $freshRequestedAt, $this->now()));
        $this->assertTrue($validator->isExpired($token, $staleRequestedAt, $this->now()));
    }

    public function testAJwtShapedTokenThatDoesNotDecodeIsReportedAsRuntime(): void
    {
        // Three segments, so isJwt() is true, but the parts are not valid base64url JSON.
        $token = new AccessToken('Bearer', 3600, 'not.a.jwt');

        $this->expectException(Runtime::class);

        (new ExpirationValidator())->isExpired($token, $this->now()->getTimestamp(), $this->now());
    }

    private function now(): DateTimeImmutable
    {
        return new DateTimeImmutable(self::NOW);
    }
}
