<?php

declare(strict_types=1);

namespace VaclavVanikTest\Oauth2Token;

use PHPUnit\Framework\TestCase;
use VaclavVanik\Oauth2Token\AccessToken;
use VaclavVanik\Oauth2Token\Exception\Runtime;
use VaclavVanik\Oauth2Token\Exception\ValueError;
use VaclavVanikTest\Oauth2Token\Support\JwtFactory;

use function json_decode;
use function json_encode;

final class AccessTokenTest extends TestCase
{
    public function testExposesConstructorValues(): void
    {
        $token = new AccessToken('Bearer', 3600, 'abc123');

        $this->assertSame('Bearer', $token->getTokenType());
        $this->assertSame(3600, $token->getExpiresIn());
        $this->assertSame('abc123', $token->getValue());
    }

    public function testHeaderValueJoinsTypeAndToken(): void
    {
        $token = new AccessToken('Bearer', 3600, 'abc123');

        $this->assertSame('Bearer abc123', $token->headerValue());
    }

    public function testRejectsEmptyTokenType(): void
    {
        $this->expectException(ValueError::class);

        new AccessToken('', 3600, 'abc123');
    }

    public function testRejectsNonPositiveExpiresIn(): void
    {
        $this->expectException(ValueError::class);

        new AccessToken('Bearer', 0, 'abc123');
    }

    public function testRejectsEmptyAccessToken(): void
    {
        $this->expectException(ValueError::class);

        new AccessToken('Bearer', 3600, '');
    }

    public function testIsJwtDetectsThreeSegmentToken(): void
    {
        $jwt = JwtFactory::create(['alg' => 'HS256', 'typ' => 'JWT'], ['exp' => 1893456000]);

        $this->assertTrue((new AccessToken('Bearer', 3600, $jwt))->isJwt());
        $this->assertFalse((new AccessToken('Bearer', 3600, 'opaque-token'))->isJwt());
    }

    public function testFromArrayMapsKeysAndDefaults(): void
    {
        $token = AccessToken::fromArray([
            AccessToken::TOKEN_TYPE => 'Bearer',
            AccessToken::EXPIRES_IN => 120,
            AccessToken::VALUE => 'abc123',
        ]);

        $this->assertSame('Bearer', $token->getTokenType());
        $this->assertSame(120, $token->getExpiresIn());
        $this->assertSame('abc123', $token->getValue());
    }

    public function testFromValidatedArrayReturnsToken(): void
    {
        $token = AccessToken::fromValidatedArray([
            AccessToken::TOKEN_TYPE => 'Bearer',
            AccessToken::EXPIRES_IN => 120,
            AccessToken::VALUE => 'abc123',
        ]);

        $this->assertSame('abc123', $token->getValue());
    }

    public function testFromValidatedArrayThrowsWhenAccessTokenMissing(): void
    {
        $this->expectException(Runtime::class);

        AccessToken::fromValidatedArray([AccessToken::TOKEN_TYPE => 'Bearer']);
    }

    public function testFromValidatedArrayThrowsWhenTokenTypeMissing(): void
    {
        $this->expectException(Runtime::class);

        AccessToken::fromValidatedArray([AccessToken::VALUE => 'abc123']);
    }

    public function testFromValidatedArrayThrowsWhenTokenLifetimeCannotBeDetermined(): void
    {
        // Opaque token, no expires_in - the library cannot know how long to cache it.
        $this->expectException(Runtime::class);

        AccessToken::fromValidatedArray([
            AccessToken::TOKEN_TYPE => 'Bearer',
            AccessToken::VALUE => 'opaque',
        ]);
    }

    public function testFromValidatedArrayAcceptsAJwtWithExpAndNoExpiresIn(): void
    {
        $jwt = JwtFactory::create(['typ' => 'JWT'], ['exp' => 1893456000]);

        $token = AccessToken::fromValidatedArray([
            AccessToken::TOKEN_TYPE => 'Bearer',
            AccessToken::VALUE => $jwt,
        ]);

        $this->assertSame($jwt, $token->getValue());
    }

    public function testFromValidatedArrayThrowsForAJwtWithoutExpAndNoExpiresIn(): void
    {
        $jwt = JwtFactory::create(['typ' => 'JWT'], ['sub' => 'no-exp']);

        $this->expectException(Runtime::class);

        AccessToken::fromValidatedArray([
            AccessToken::TOKEN_TYPE => 'Bearer',
            AccessToken::VALUE => $jwt,
        ]);
    }

    public function testFromValidatedArrayThrowsForAJwtShapedTokenThatDoesNotDecodeWhenNoExpiresIn(): void
    {
        $this->expectException(Runtime::class);

        AccessToken::fromValidatedArray([
            AccessToken::TOKEN_TYPE => 'Bearer',
            AccessToken::VALUE => 'not.a.jwt',
        ]);
    }

    public function testFromValidatedArrayReportsAnEmptyButPresentValueAsRuntimeNotValueError(): void
    {
        // Every required key is present, so the key checks pass - but the value is junk. The caller asked us
        // to validate a server response, so this must surface as Exception\Runtime, not a raw ValueError.
        $this->expectException(Runtime::class);

        AccessToken::fromValidatedArray([
            AccessToken::TOKEN_TYPE => '',
            AccessToken::EXPIRES_IN => 3600,
            AccessToken::VALUE => 'abc123',
        ]);
    }

    public function testJsonSerializeRoundTrips(): void
    {
        $token = new AccessToken('Bearer', 3600, 'abc123');

        $decoded = json_decode((string) json_encode($token), true);

        $this->assertSame([
            AccessToken::TOKEN_TYPE => 'Bearer',
            AccessToken::EXPIRES_IN => 3600,
            AccessToken::VALUE => 'abc123',
        ], $decoded);

        $this->assertEquals($token, AccessToken::fromArray($decoded));
    }
}
