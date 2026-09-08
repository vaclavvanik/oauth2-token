<?php

declare(strict_types=1);

namespace VaclavVanik\Oauth2Token;

use DateTimeInterface;

abstract class CachingTokenProviderFactory
{
    public static function createJsonFile(
        TokenProvider $provider,
        string $file,
        ?DateTimeInterface $now = null,
        int $delta = ExpirationValidator::DELTA_DEFAULT
    ): RefreshableTokenProvider {
        return new CachingTokenProvider($provider, new Cache\JsonFileRepository($file, $now), $now, $delta);
    }

    public static function createMemory(
        TokenProvider $provider,
        ?DateTimeInterface $now = null,
        int $delta = ExpirationValidator::DELTA_DEFAULT
    ): RefreshableTokenProvider {
        return new CachingTokenProvider($provider, new Cache\MemoryRepository(), $now, $delta);
    }
}
