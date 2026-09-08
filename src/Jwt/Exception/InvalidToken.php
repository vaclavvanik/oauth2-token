<?php

declare(strict_types=1);

namespace VaclavVanik\Oauth2Token\Jwt\Exception;

use RuntimeException;
use Throwable;
use VaclavVanik\Oauth2Token\Exception\FromThrowable;

use function sprintf;

/**
 * The string is not a JWT this decoder can read - wrong segment count, a segment that is not base64url-encoded
 * JSON, or a claim with an unusable value. A JWT reaches the decoder from a token endpoint response, so a bad
 * one is a runtime condition, not a bug in the calling code.
 */
final class InvalidToken extends RuntimeException implements Exception
{
    use FromThrowable;

    // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found -- the override exists to make the constructor private
    private function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function wrongSegmentCount(): self
    {
        return new self('Given JWT token has invalid number of segments.');
    }

    public static function segmentNotJsonObject(): self
    {
        return new self('Given JWT token segment is not a JSON object.');
    }

    public static function invalidNumericDate(string $numericDate): self
    {
        return new self(sprintf('Cannot create date time from "%s".', $numericDate));
    }
}
