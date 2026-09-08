<?php

declare(strict_types=1);

namespace VaclavVanik\Oauth2Token;

use DateTimeImmutable;
use DateTimeInterface;
use Throwable;

use function sprintf;

class ExpirationValidator
{
    /** @var int */
    private $delta;

    public const DELTA_DEFAULT = 60;

    public function __construct(int $delta = self::DELTA_DEFAULT)
    {
        $this->delta = $delta;
    }

    /**
     * Whether a cached access token needs replacing as of `$now` - it is within the refresh delta of its
     * expiry, or past it. A JWT expires on its own `exp` claim; a JWT without `exp` and every opaque token
     * expires on the token response lifetime, `expiresIn` seconds after it was requested.
     *
     * @throws Exception\Runtime
     */
    public function isExpired(AccessToken $token, int $requestedAt, DateTimeInterface $now): bool
    {
        $expiresAt = null;

        if ($token->isJwt()) {
            $expiresAt = $this->jwtExpirationTime($token->getValue());
        }

        $expiresAt = $expiresAt ?? $this->lifetimeExpiration($requestedAt, $token->getExpiresIn());

        return $expiresAt->getTimestamp() - $this->delta < $now->getTimestamp();
    }

    /**
     * The JWT `exp` claim as a point in time, or null when the token carries no `exp`.
     *
     * @throws Exception\Runtime
     */
    private function jwtExpirationTime(string $jwt): ?DateTimeInterface
    {
        try {
            return Jwt\Decoder::decode($jwt)->getPayload()->getExpirationTime();
        } catch (Throwable $e) {
            throw Exception\Runtime::fromThrowable($e);
        }
    }

    /** @throws Exception\Runtime */
    private function lifetimeExpiration(int $requestedAt, int $expiresIn): DateTimeImmutable
    {
        $expiresAt = DateTimeImmutable::createFromFormat('U', (string) ($requestedAt + $expiresIn));

        if ($expiresAt === false) {
            throw new Exception\Runtime(sprintf('Invalid token lifetime: %d + %d.', $requestedAt, $expiresIn));
        }

        return $expiresAt;
    }
}
