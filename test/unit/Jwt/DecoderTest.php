<?php

declare(strict_types=1);

namespace VaclavVanikTest\Oauth2Token\Jwt;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use VaclavVanik\Oauth2Token\Jwt\Decoder;
use VaclavVanikTest\Oauth2Token\Support\JwtFactory;

final class DecoderTest extends TestCase
{
    public function testIsJwtIsTrueForThreeSegmentValue(): void
    {
        $this->assertTrue(Decoder::isJwt('a.b.c'));
    }

    public function testIsJwtIsFalseForOtherValues(): void
    {
        $this->assertFalse(Decoder::isJwt('a.b'));
        $this->assertFalse(Decoder::isJwt('opaque-token'));
    }

    public function testDecodeReadsHeaderPayloadAndSignature(): void
    {
        $token = JwtFactory::create(
            ['typ' => 'JWT', 'alg' => 'HS256'],
            [
                'iss' => 'issuer',
                'sub' => 'subject',
                'aud' => 'audience',
                'exp' => 1893456000,
                'nbf' => 1893452400,
                'iat' => 1893452400,
                'jti' => 'token-id',
            ],
            'the-signature',
        );

        $jwt = Decoder::decode($token);

        $this->assertSame('JWT', $jwt->getHeader()->getTyp());
        $this->assertSame('HS256', $jwt->getHeader()->getAlg());
        $this->assertSame('issuer', $jwt->getPayload()->getIssuer());
        $this->assertSame('token-id', $jwt->getPayload()->getJwtId());
        $this->assertSame(1893456000, $jwt->getPayload()->getExpirationTime()->getTimestamp());
        $this->assertSame('the-signature', $jwt->getSignature()->getValue());
    }

    public function testDecodeHandlesBase64UrlPayloadThatNeedsPadding(): void
    {
        // "?" forces a '+' or '/' in standard base64 and a padding char, exercising the url-safe decoding path.
        $token = JwtFactory::create(['typ' => 'JWT', 'alg' => 'HS256'], ['sub' => 'a?b?c', 'exp' => 1893456000]);

        $this->assertSame('a?b?c', Decoder::decode($token)->getPayload()->getSubject());
    }

    public function testDecodeThrowsForWrongSegmentCount(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Decoder::decode('only.two');
    }

    public function testDecodeThrowsWhenSegmentIsNotJson(): void
    {
        $this->expectException(RuntimeException::class);

        // base64 of "notjson" in header and payload
        Decoder::decode('bm90anNvbg==.bm90anNvbg==.sig');
    }

    public function testDecodeThrowsWhenSegmentIsJsonButNotAnObject(): void
    {
        $this->expectException(RuntimeException::class);

        // base64url of "123" in both header and payload
        Decoder::decode('MTIz.MTIz.sig');
    }
}
