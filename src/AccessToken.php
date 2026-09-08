<?php

declare(strict_types=1);

namespace VaclavVanik\Oauth2Token;

use JsonSerializable;
use OutOfBoundsException;
use Throwable;

use function sprintf;

final class AccessToken implements JsonSerializable
{
    /** @var string */
    private $tokenType;

    /** @var int */
    private $expiresIn;

    /** @var string */
    private $value;

    public const TOKEN_TYPE = 'token_type';

    public const EXPIRES_IN = 'expires_in';

    public const VALUE = 'access_token';

    /** @throws Exception\ValueError */
    public function __construct(string $tokenType, int $expiresIn, string $value)
    {
        if ($tokenType === '') {
            throw new Exception\ValueError('Token type cannot be empty.');
        }

        if ($expiresIn < 1) {
            throw new Exception\ValueError('Expires in must be greater than 0.');
        }

        if ($value === '') {
            throw new Exception\ValueError('Access token cannot be empty.');
        }

        $this->tokenType = $tokenType;
        $this->expiresIn = $expiresIn;
        $this->value = $value;
    }

    /**
     * @param array{token_type?: string, expires_in?: int, access_token?: string} $data
     *
     * @throws Exception\ValueError
     */
    public static function fromArray(array $data): self
    {
        $tokenType = (string) ($data[self::TOKEN_TYPE] ?? '');
        // A JWT carries its own exp, so a response may legitimately omit expires_in; 1 is a placeholder the
        // JWT path never reads. fromValidatedArray() is what rejects a genuinely missing lifetime.
        $expiresIn = (int) ($data[self::EXPIRES_IN] ?? 1);
        $value = (string) ($data[self::VALUE] ?? '');

        return new self($tokenType, $expiresIn, $value);
    }

    /**
     * @param array{token_type: string, expires_in?: int, access_token: string} $data
     *
     * @throws Exception\Runtime
     */
    public static function fromValidatedArray(array $data): self
    {
        try {
            if (! isset($data[self::VALUE])) {
                throw new OutOfBoundsException(
                    sprintf('Response body does not have "%s" property.', self::VALUE),
                );
            }

            if (! isset($data[self::TOKEN_TYPE])) {
                throw new OutOfBoundsException(
                    sprintf('Response body does not have "%s" property.', self::TOKEN_TYPE),
                );
            }

            if (
                ! isset($data[self::EXPIRES_IN])
                && ! self::isJwtWithExpiry((string) $data[self::VALUE])
            ) {
                throw new OutOfBoundsException(
                    'Cannot determine token lifetime: response body has no "expires_in" property '
                    . 'and the access token is not a JWT with an "exp" claim.',
                );
            }

            return self::fromArray($data);
        } catch (Throwable $e) {
            throw Exception\Runtime::fromThrowable($e);
        }
    }

    public function getTokenType(): string
    {
        return $this->tokenType;
    }

    public function getExpiresIn(): int
    {
        return $this->expiresIn;
    }

    /** The token string as it goes into the request - opaque, or a JWT. */
    public function getValue(): string
    {
        return $this->value;
    }

    public function headerValue(): string
    {
        return sprintf('%s %s', $this->tokenType, $this->value);
    }

    public function isJwt(): bool
    {
        return Jwt\Decoder::isJwt($this->value);
    }

    /** @return array{token_type: string, expires_in: int, access_token: string} */
    public function jsonSerialize(): array
    {
        return [
            self::TOKEN_TYPE => $this->tokenType,
            self::EXPIRES_IN => $this->expiresIn,
            self::VALUE => $this->value,
        ];
    }

    private static function isJwtWithExpiry(string $value): bool
    {
        if (Jwt\Decoder::isJwt($value) === false) {
            return false;
        }

        try {
            return Jwt\Decoder::decode($value)->getPayload()->hasExpirationTime();
        } catch (Throwable $e) {
            return false;
        }
    }
}
