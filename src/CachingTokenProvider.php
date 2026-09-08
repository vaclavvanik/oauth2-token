<?php

declare(strict_types=1);

namespace VaclavVanik\Oauth2Token;

use DateTimeImmutable;
use DateTimeInterface;
use Throwable;

final class CachingTokenProvider implements RefreshableTokenProvider
{
    /** @var TokenProvider */
    private $delegated;

    /** @var Cache\Repository */
    private $repository;

    /** @var DateTimeInterface|null */
    private $now;

    /** @var int */
    private $delta;

    public function __construct(
        TokenProvider $delegated,
        Cache\Repository $repository,
        ?DateTimeInterface $now = null,
        int $delta = ExpirationValidator::DELTA_DEFAULT
    ) {
        $this->delegated = $delegated;
        $this->repository = $repository;
        $this->now = $now;
        $this->delta = $delta;
    }

    public function getToken(TokenRequest $request): AccessToken
    {
        $key = self::cacheKey($request);
        $now = $this->now ?? new DateTimeImmutable();

        $cached = $this->load($key);

        if ($cached !== null && $this->isExpired($cached, $now) === false) {
            return $cached->getToken();
        }

        $entry = new Cache\Entry($this->delegated->getToken($request), $now->getTimestamp(), $this->delta);

        try {
            $this->repository->save($key, $entry);
        } catch (Throwable $e) {
            throw Exception\Runtime::fromThrowable($e);
        }

        return $entry->getToken();
    }

    public function forget(TokenRequest $request): void
    {
        try {
            $this->repository->delete(self::cacheKey($request));
        } catch (Throwable $e) {
            throw Exception\Runtime::fromThrowable($e);
        }
    }

    private static function cacheKey(TokenRequest $request): Cache\Key
    {
        return new Cache\Key($request->getClientId(), $request->getClientSecret(), $request->getScope());
    }

    /**
     * Read the cached token in a single repository round-trip. A cache miss is a null, not an exception.
     *
     * @throws Exception\Runtime
     */
    private function load(Cache\Key $key): ?Cache\Entry
    {
        try {
            return $this->repository->load($key);
        } catch (Cache\Exception\NotFound $e) {
            return null;
        } catch (Throwable $e) {
            throw Exception\Runtime::fromThrowable($e);
        }
    }

    /** @throws Exception\Runtime */
    private function isExpired(Cache\Entry $entry, DateTimeInterface $now): bool
    {
        $validator = new ExpirationValidator($entry->getDelta());

        return $validator->isExpired($entry->getToken(), $entry->getRequestTimestamp(), $now);
    }
}
