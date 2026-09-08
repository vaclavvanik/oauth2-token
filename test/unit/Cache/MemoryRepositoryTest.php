<?php

declare(strict_types=1);

namespace VaclavVanikTest\Oauth2Token\Cache;

use PHPUnit\Framework\TestCase;
use VaclavVanik\Oauth2Token\AccessToken;
use VaclavVanik\Oauth2Token\Cache\Entry;
use VaclavVanik\Oauth2Token\Cache\Exception\NotFound;
use VaclavVanik\Oauth2Token\Cache\Key;
use VaclavVanik\Oauth2Token\Cache\MemoryRepository;

final class MemoryRepositoryTest extends TestCase
{
    public function testHasIsFalseForUnknownKey(): void
    {
        $this->assertFalse((new MemoryRepository())->has($this->key()));
    }

    public function testSavedTokenCanBeLoaded(): void
    {
        $repository = new MemoryRepository();
        $repository->save($this->key(), $this->cacheToken());

        $this->assertTrue($repository->has($this->key()));
        $this->assertSame('abc123', $repository->load($this->key())->getToken()->getValue());
    }

    public function testLoadThrowsNotFoundForUnknownKey(): void
    {
        $this->expectException(NotFound::class);

        (new MemoryRepository())->load($this->key());
    }

    public function testDeleteRemovesTheEntry(): void
    {
        $repository = new MemoryRepository();
        $repository->save($this->key(), $this->cacheToken());

        $repository->delete($this->key());

        $this->assertFalse($repository->has($this->key()));
    }

    public function testDeleteIsANoOpForUnknownKey(): void
    {
        $repository = new MemoryRepository();

        $repository->delete($this->key());

        $this->assertFalse($repository->has($this->key()));
    }

    private function key(): Key
    {
        return new Key('client-id', 'client-secret', null);
    }

    private function cacheToken(): Entry
    {
        return new Entry(new AccessToken('Bearer', 3600, 'abc123'), 1893456000, 60);
    }
}
