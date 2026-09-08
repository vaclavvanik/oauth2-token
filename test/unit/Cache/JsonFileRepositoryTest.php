<?php

declare(strict_types=1);

namespace VaclavVanikTest\Oauth2Token\Cache;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use VaclavVanik\Oauth2Token\AccessToken;
use VaclavVanik\Oauth2Token\Cache\Entry;
use VaclavVanik\Oauth2Token\Cache\Exception\NotFound;
use VaclavVanik\Oauth2Token\Cache\Exception\Runtime;
use VaclavVanik\Oauth2Token\Cache\JsonFileRepository;
use VaclavVanik\Oauth2Token\Cache\Key;
use VaclavVanik\Oauth2Token\Exception\ValueError;
use VaclavVanik\Oauth2Token\ExpirationValidator;

use function file_exists;
use function file_put_contents;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

final class JsonFileRepositoryTest extends TestCase
{
    /** @var string */
    private $file;

    /** @var DateTimeImmutable */
    private $now;

    public function testConstructorRejectsEmptyFileName(): void
    {
        $this->expectException(ValueError::class);

        new JsonFileRepository('');
    }

    public function testHasIsFalseWhenFileDoesNotExist(): void
    {
        $this->assertFalse($this->repo()->has($this->key()));
    }

    public function testSavedTokenSurvivesANewRepositoryInstance(): void
    {
        $this->repo()->save($this->key(), $this->cacheToken());

        $reloaded = $this->repo();

        $this->assertTrue($reloaded->has($this->key()));
        $this->assertSame('abc123', $reloaded->load($this->key())->getToken()->getValue());
    }

    public function testSaveDropsAlreadyExpiredEntries(): void
    {
        $repository = $this->repo();

        $staleRequestedAt = $this->now->modify('-2 hours')->getTimestamp();

        $repository->save($this->key('stale'), $this->cacheToken(3600, $staleRequestedAt));
        $repository->save($this->key('fresh'), $this->cacheToken());

        $this->assertFalse($repository->has($this->key('stale')));
        $this->assertTrue($repository->has($this->key('fresh')));
    }

    public function testTheInjectedClockDrivesTheExpiryPrune(): void
    {
        // Requested "now" but the clock is pinned far in the past, so relative to it the token is still fresh
        // and must survive the prune - proving save() uses the injected clock, not the wall clock.
        $pastClock = new DateTimeImmutable('2000-01-01T00:00:00+00:00');

        $repository = new JsonFileRepository($this->file, $pastClock);
        $repository->save($this->key(), $this->cacheToken(3600, $pastClock->getTimestamp()));

        $this->assertTrue($repository->has($this->key()));
    }

    public function testLoadThrowsNotFoundForUnknownKey(): void
    {
        $repository = $this->repo();
        $repository->save($this->key('known'), $this->cacheToken());

        $this->expectException(NotFound::class);

        $repository->load($this->key('unknown'));
    }

    public function testDeleteRemovesTheEntryAndLeavesOthersIntact(): void
    {
        $repository = $this->repo();
        $repository->save($this->key('a'), $this->cacheToken());
        $repository->save($this->key('b'), $this->cacheToken());

        $repository->delete($this->key('a'));

        $this->assertFalse($this->repo()->has($this->key('a')));
        $this->assertTrue($this->repo()->has($this->key('b')));
    }

    public function testDeleteIsANoOpForUnknownKey(): void
    {
        $repository = $this->repo();

        $repository->delete($this->key());

        $this->assertFalse($repository->has($this->key()));
    }

    public function testCorruptCacheFileIsTreatedAsEmptyAndHealsOnNextSave(): void
    {
        file_put_contents($this->file, '{ not json');

        $repository = $this->repo();

        // A corrupt cache must not blow up - it reads as empty.
        $this->assertFalse($repository->has($this->key()));

        // ... and the next save rewrites it into a usable state.
        $repository->save($this->key(), $this->cacheToken());

        $healed = $this->repo()->load($this->key());

        $this->assertSame('abc123', $healed->getToken()->getValue());
    }

    public function testStructurallyBrokenEntryIsTreatedAsEmpty(): void
    {
        // Valid JSON, but the stored entry has no token.
        file_put_contents($this->file, '{"deadbeef":{"request_timestamp":1,"delta":60}}');

        $this->assertFalse($this->repo()->has($this->key()));
    }

    public function testUnreadableCacheFileIsReportedAsRuntimeWithTheReason(): void
    {
        // A directory cannot be read as a file - stands in for any "cannot access" failure and works as root.
        $repository = new JsonFileRepository(sys_get_temp_dir(), $this->now);

        $this->expectException(Runtime::class);
        $this->expectExceptionMessageMatches('/^Cannot read cache file .+: .+/');

        $repository->has($this->key());
    }

    public function testUnwritableCacheFileIsReportedAsRuntimeWithTheReason(): void
    {
        $repository = new JsonFileRepository('/proc/nonexistent-oauth-cache/tokens.json', $this->now);

        $this->expectException(Runtime::class);
        $this->expectExceptionMessageMatches('/^Cannot write cache file .+: .+/');

        $repository->save($this->key(), $this->cacheToken());
    }

    protected function setUp(): void
    {
        $this->file = (string) tempnam(sys_get_temp_dir(), 'oauth-cache-');
        $this->now = new DateTimeImmutable('2026-01-01T12:00:00+00:00');
        unlink($this->file);
    }

    protected function tearDown(): void
    {
        if (! file_exists($this->file)) {
            return;
        }

        unlink($this->file);
    }

    private function repo(): JsonFileRepository
    {
        return new JsonFileRepository($this->file, $this->now);
    }

    private function key(string $secret = 'client-secret'): Key
    {
        return new Key('client-id', $secret, null);
    }

    private function cacheToken(int $expiresIn = 3600, ?int $requestTimestamp = null): Entry
    {
        return new Entry(
            new AccessToken('Bearer', $expiresIn, 'abc123'),
            $requestTimestamp ?? $this->now->getTimestamp(),
            ExpirationValidator::DELTA_DEFAULT,
        );
    }
}
