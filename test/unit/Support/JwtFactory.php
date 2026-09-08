<?php

declare(strict_types=1);

namespace VaclavVanikTest\Oauth2Token\Support;

use function base64_encode;
use function json_encode;
use function rtrim;
use function strtr;

final class JwtFactory
{
    /**
     * @param array<string, mixed> $header
     * @param array<string, mixed> $payload
     */
    public static function create(array $header, array $payload, string $signature = 'signature'): string
    {
        // The signature segment is opaque to the decoder - it is stored verbatim, never decoded - so it is
        // appended as given.
        return self::encode((string) json_encode($header))
            . '.' . self::encode((string) json_encode($payload))
            . '.' . $signature;
    }

    private static function encode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
