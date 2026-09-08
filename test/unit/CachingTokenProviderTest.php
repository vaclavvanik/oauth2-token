<?php

declare(strict_types=1);

namespace VaclavVanikTest\Oauth2Token;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use VaclavVanik\Oauth2Token\AccessToken;
use VaclavVanik\Oauth2Token\Cache\Entry;
use VaclavVanik\Oauth2Token\Cache\Key;
use VaclavVanik\Oauth2Token\Cache\MemoryRepository;
use VaclavVanik\Oauth2Token\CachingTokenProvider;
use VaclavVanik\Oauth2Token\Exception\Runtime;
use VaclavVanik\Oauth2Token\ExpirationValidator;
use VaclavVanik\Oauth2Token\RefreshableTokenProvider;
use VaclavVanik\Oauth2Token\TokenRequest;
use VaclavVanikTest\Oauth2Token\Support\FakeTokenProvider;
use VaclavVanikTest\Oauth2Token\Support\JwtFactory;
use VaclavVanikTest\Oauth2Token\Support\RecordingRepository;
use VaclavVanikTest\Oauth2Token\Support\ThrowingRepository;

final class CachingTokenProviderTest extends TestCase
{
    /** @var DateTimeImmutable */
    private $now;

    /** @var MemoryRepository */
    private $repository;

    public function testIsARefreshableTokenProvider(): void
    {
        $this->assertInstanceOf(RefreshableTokenProvider::class, $this->oauth(new FakeTokenProvider()));
    }

    public function testFetchesAndCachesOnCacheMiss(): void
    {
        $delegated = new FakeTokenProvider(new AccessToken('Bearer', 3600, 'fresh'));

        $token = $this->oauth($delegated)->getToken($this->request());

        $this->assertSame('fresh', $token->getValue());
        $this->assertSame(1, $delegated->calls);
        $this->assertTrue($this->repository->has(new Key('client-id', 'client-secret', null)));
    }

    public function testCachedTokenThatIsStillValidIsReturnedWithoutCallingDelegate(): void
    {
        $this->prime(new AccessToken('Bearer', 3600, 'cached'), $this->now->getTimestamp());

        $delegated = new FakeTokenProvider();

        $token = $this->oauth($delegated)->getToken($this->request());

        $this->assertSame('cached', $token->getValue());
        $this->assertSame(0, $delegated->calls);
    }

    public function testExpiredCachedTokenIsRefreshed(): void
    {
        $this->prime(new AccessToken('Bearer', 3600, 'stale'), $this->now->getTimestamp() - 7200);

        $delegated = new FakeTokenProvider(new AccessToken('Bearer', 3600, 'refreshed'));

        $token = $this->oauth($delegated)->getToken($this->request());

        $this->assertSame('refreshed', $token->getValue());
        $this->assertSame(1, $delegated->calls);
    }

    public function testCachedJwtWithoutExpIsServedWhileItsResponseLifetimeLasts(): void
    {
        $jwt = JwtFactory::create(['typ' => 'JWT'], ['sub' => 'no-exp']);
        $this->prime(new AccessToken('Bearer', 3600, $jwt), $this->now->getTimestamp());

        $delegated = new FakeTokenProvider();

        $token = $this->oauth($delegated)->getToken($this->request());

        $this->assertSame($jwt, $token->getValue());
        $this->assertSame(0, $delegated->calls);
    }

    public function testCachedJwtWithoutExpIsRefreshedAfterItsResponseLifetime(): void
    {
        $jwt = JwtFactory::create(['typ' => 'JWT'], ['sub' => 'no-exp']);
        $this->prime(new AccessToken('Bearer', 3600, $jwt), $this->now->getTimestamp() - 7200);

        $delegated = new FakeTokenProvider(new AccessToken('Bearer', 3600, 'refreshed'));

        $token = $this->oauth($delegated)->getToken($this->request());

        $this->assertSame('refreshed', $token->getValue());
        $this->assertSame(1, $delegated->calls);
    }

    public function testUsesTheSystemClockWhenNoClockIsInjected(): void
    {
        $oauth = new CachingTokenProvider(
            new FakeTokenProvider(new AccessToken('Bearer', 3600, 'fresh')),
            $this->repository,
        );

        $this->assertSame('fresh', $oauth->getToken($this->request())->getValue());
    }

    public function testTokensAreScopedByScope(): void
    {
        $delegated = new FakeTokenProvider(
            new AccessToken('Bearer', 3600, 'scope-a'),
            new AccessToken('Bearer', 3600, 'scope-b'),
        );
        $oauth = $this->oauth($delegated);

        $this->assertSame('scope-a', $oauth->getToken($this->request(['a']))->getValue());
        $this->assertSame('scope-b', $oauth->getToken($this->request(['b']))->getValue());

        // Two distinct scopes miss the cache separately, and each request reaches the delegate intact.
        $this->assertSame(2, $delegated->calls);
        $this->assertSame('a', $delegated->requests[0]->getScope());
        $this->assertSame('b', $delegated->requests[1]->getScope());
    }

    public function testCacheHitReadsTheStoreOnlyOnce(): void
    {
        $this->prime(new AccessToken('Bearer', 3600, 'cached'), $this->now->getTimestamp());

        $recording = new RecordingRepository($this->repository);
        $oauth = new CachingTokenProvider(new FakeTokenProvider(), $recording, $this->now);

        $oauth->getToken($this->request());

        $this->assertSame(1, $recording->calls['load']);
        $this->assertSame(0, $recording->calls['save']);
    }

    public function testRepositoryReadFailureIsWrappedInRuntime(): void
    {
        $oauth = new CachingTokenProvider(new FakeTokenProvider(), new ThrowingRepository(true), $this->now);

        $this->expectException(Runtime::class);

        $oauth->getToken($this->request());
    }

    public function testRepositoryWriteFailureIsWrappedInRuntime(): void
    {
        $oauth = new CachingTokenProvider(
            new FakeTokenProvider(new AccessToken('Bearer', 3600, 'fresh')),
            new ThrowingRepository(false),
            $this->now,
        );

        $this->expectException(Runtime::class);

        $oauth->getToken($this->request());
    }

    public function testForgetForcesTheNextCallToRefetch(): void
    {
        $this->prime(new AccessToken('Bearer', 3600, 'revoked'), $this->now->getTimestamp());

        $delegated = new FakeTokenProvider(new AccessToken('Bearer', 3600, 'reissued'));
        $oauth = $this->oauth($delegated);

        $oauth->forget($this->request());

        $token = $oauth->getToken($this->request());

        $this->assertSame('reissued', $token->getValue());
        $this->assertSame(1, $delegated->calls);
    }

    public function testForgetWrapsRepositoryFailureInRuntime(): void
    {
        $oauth = new CachingTokenProvider(new FakeTokenProvider(), new ThrowingRepository(true), $this->now);

        $this->expectException(Runtime::class);

        $oauth->forget($this->request());
    }

    protected function setUp(): void
    {
        $this->now = new DateTimeImmutable('2026-01-01T12:00:00+00:00');
        $this->repository = new MemoryRepository();
    }

    /** @param list<string> $scopes */
    private function request(array $scopes = []): TokenRequest
    {
        return new TokenRequest('client-id', 'client-secret', $scopes);
    }

    private function oauth(FakeTokenProvider $delegated): CachingTokenProvider
    {
        return new CachingTokenProvider($delegated, $this->repository, $this->now, ExpirationValidator::DELTA_DEFAULT);
    }

    private function prime(AccessToken $token, int $requestTimestamp): void
    {
        $this->repository->save(
            new Key('client-id', 'client-secret', null),
            new Entry($token, $requestTimestamp, ExpirationValidator::DELTA_DEFAULT),
        );
    }
}
