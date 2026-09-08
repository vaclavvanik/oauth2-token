<?php

declare(strict_types=1);

namespace VaclavVanik\Oauth2Token\Cache;

use JsonSerializable;
use VaclavVanik\Oauth2Token\AccessToken;

final class Entry implements JsonSerializable
{
    /** @var AccessToken */
    private $token;

    /** @var int */
    private $requestTimestamp;

    /** @var int */
    private $delta;

    public const TOKEN = 'token';

    public const REQUEST_TIMESTAMP = 'request_timestamp';

    public const DELTA = 'delta';

    public function __construct(AccessToken $token, int $requestTimestamp, int $delta)
    {
        $this->token = $token;
        $this->requestTimestamp = $requestTimestamp;
        $this->delta = $delta;
    }

    /** @param array{token: AccessToken, request_timestamp?: int, delta?: int} $data */
    public static function fromArray(array $data): self
    {
        return new self(
            $data[self::TOKEN],
            (int) ($data[self::REQUEST_TIMESTAMP] ?? 0),
            (int) ($data[self::DELTA] ?? 0),
        );
    }

    public function getToken(): AccessToken
    {
        return $this->token;
    }

    public function getRequestTimestamp(): int
    {
        return $this->requestTimestamp;
    }

    public function getDelta(): int
    {
        return $this->delta;
    }

    /** @return array{token: AccessToken, request_timestamp: int, delta: int} */
    public function jsonSerialize(): array
    {
        return [
            self::TOKEN => $this->token,
            self::REQUEST_TIMESTAMP => $this->requestTimestamp,
            self::DELTA => $this->delta,
        ];
    }
}
