<?php

declare(strict_types=1);

namespace VaclavVanik\Oauth2Token\Jwt;

use InvalidArgumentException;
use RuntimeException;
use Throwable;

use function base64_decode;
use function count;
use function explode;
use function is_array;
use function json_decode;
use function str_replace;
use function strlen;

use const JSON_THROW_ON_ERROR;

class Decoder
{
    public static function isJwt(string $value): bool
    {
        $parts = self::parseParts($value);

        return self::isPartsValid($parts);
    }

    /**
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public static function decode(string $token): Jwt
    {
        $parts = self::parseParts($token);

        if (self::isPartsValid($parts) === false) {
            throw new InvalidArgumentException('Given JWT token has invalid number of segments.');
        }

        return new Jwt(
            Header::fromArray(self::jsonDecode(self::base64Decode($parts[0]))),
            Payload::fromArray(self::jsonDecode(self::base64Decode($parts[1]))),
            Signature::fromString($parts[2]),
        );
    }

    /** @return list<string> */
    private static function parseParts(string $value): array
    {
        return explode('.', $value);
    }

    private static function isPartsValid(array $parts): bool
    {
        return count($parts) === 3;
    }

    /** @throws RuntimeException */
    private static function jsonDecode(string $string): array
    {
        try {
            $decoded = json_decode($string, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        if (! is_array($decoded)) {
            throw new RuntimeException('Given JWT token segment is not a JSON object.');
        }

        return $decoded;
    }

    private static function base64Decode(string $string): string
    {
        return (string) base64_decode(self::addPadding(self::toBase64($string)), true);
    }

    private static function addPadding(string $base64String): string
    {
        if (strlen($base64String) % 4 !== 0) {
            return self::addPadding($base64String . '=');
        }

        return $base64String;
    }

    private static function toBase64(string $urlString): string
    {
        return str_replace(['-', '_'], ['+', '/'], $urlString);
    }
}
