<?php

declare(strict_types=1);

namespace VaclavVanik\Oauth2Token\Jwt;

use DateTimeImmutable;
use DateTimeInterface;

final class Payload
{
    /**
     * Identifies principal that issued the JWT.
     *
     * @var string
     */
    private $issuer;

    /**
     * Identifies the subject of the JWT.
     *
     * @var string
     */
    private $subject;

    /**
     * Identifies the recipients that the JWT is intended for.
     * Each principal intended to process the JWT must identify itself with a value in the audience claim.
     * If the principal processing the claim does not identify itself with a value in the aud claim
     * when this claim is present, then the JWT must be rejected.
     *
     * @var string
     */
    private $audience;

    /**
     * Identifies the expiration time on and after which the JWT must not be accepted for processing.
     * The value is a NumericDate. Null when the token carries no `exp` claim - it is an optional claim.
     *
     * @var DateTimeInterface|null
     */
    private $expirationTime;

    /**
     * Identifies the time on which the JWT will start to be accepted for processing.
     * The value is a NumericDate. Null when the token carries no `nbf` claim.
     *
     * @var DateTimeInterface|null
     */
    private $notBefore;

    /**
     * Identifies the time at which the JWT was issued.
     * The value is a NumericDate. Null when the token carries no `iat` claim.
     *
     * @var DateTimeInterface|null
     */
    private $issuedAt;

    /**
     * Case-sensitive unique identifier of the token even among different issuers.
     *
     * @var string
     */
    private $jwtId;

    public const ISSUER = 'iss';

    public const SUBJECT = 'sub';

    public const AUDIENCE = 'aud';

    public const EXPIRATION_TIME = 'exp';

    public const NOT_BEFORE = 'nbf';

    public const ISSUED_AT = 'iat';

    public const JWT_ID = 'jti';

    public function __construct(
        string $issuer,
        string $subject,
        string $audience,
        ?DateTimeInterface $expirationTime,
        ?DateTimeInterface $notBefore,
        ?DateTimeInterface $issuedAt,
        string $jwtId
    ) {
        $this->issuer = $issuer;
        $this->subject = $subject;
        $this->audience = $audience;
        $this->expirationTime = $expirationTime;
        $this->notBefore = $notBefore;
        $this->issuedAt = $issuedAt;
        $this->jwtId = $jwtId;
    }

    /**
     * phpcs:disable Squiz.Commenting.FunctionComment.MissingParamName
     * @param array{
     *     iss?: string,
     *     sub?: string,
     *     aud?: string,
     *     exp?: float|int,
     *     nbf?: float|int,
     *     iat?: float|int,
     *     jti?: string,
     * } $data
     * phpcs:enable Squiz.Commenting.FunctionComment.MissingParamName
     *
     * @throws Exception\InvalidToken
     */
    public static function fromArray(array $data): self
    {
        return new self(
            (string) ($data[self::ISSUER] ?? ''),
            (string) ($data[self::SUBJECT] ?? ''),
            (string) ($data[self::AUDIENCE] ?? ''),
            self::toDateTimeInterface($data[self::EXPIRATION_TIME] ?? null),
            self::toDateTimeInterface($data[self::NOT_BEFORE] ?? null),
            self::toDateTimeInterface($data[self::ISSUED_AT] ?? null),
            (string) ($data[self::JWT_ID] ?? ''),
        );
    }

    public function getIssuer(): string
    {
        return $this->issuer;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getAudience(): string
    {
        return $this->audience;
    }

    public function hasExpirationTime(): bool
    {
        return $this->expirationTime !== null;
    }

    public function getExpirationTime(): ?DateTimeInterface
    {
        return $this->expirationTime;
    }

    public function hasNotBefore(): bool
    {
        return $this->notBefore !== null;
    }

    public function getNotBefore(): ?DateTimeInterface
    {
        return $this->notBefore;
    }

    public function hasIssuedAt(): bool
    {
        return $this->issuedAt !== null;
    }

    public function getIssuedAt(): ?DateTimeInterface
    {
        return $this->issuedAt;
    }

    public function getJwtId(): string
    {
        return $this->jwtId;
    }

    /**
     * @param float|int|string|null $numericDate
     *
     * @throws Exception\InvalidToken
     */
    private static function toDateTimeInterface($numericDate): ?DateTimeInterface
    {
        if ($numericDate === null) {
            return null;
        }

        $timestamp = (string) (int) $numericDate;

        $dateTime = DateTimeImmutable::createFromFormat('U', $timestamp);

        if ($dateTime === false) {
            throw Exception\InvalidToken::invalidNumericDate($timestamp);
        }

        return $dateTime;
    }
}
