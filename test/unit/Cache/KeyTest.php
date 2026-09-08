<?php

declare(strict_types=1);

namespace VaclavVanikTest\Oauth2Token\Cache;

use PHPUnit\Framework\TestCase;
use VaclavVanik\Oauth2Token\Cache\Key;

final class KeyTest extends TestCase
{
    public function testExposesConstructorValues(): void
    {
        $key = new Key('client-id', 'client-secret', 'scope');

        $this->assertSame('client-id', $key->getClientId());
        $this->assertSame('scope', $key->getScope());
    }

    public function testKeyIsDeterministic(): void
    {
        $key = new Key('client-id', 'client-secret', null);

        $this->assertSame($key->key(), (new Key('client-id', 'client-secret', null))->key());
    }

    public function testKeyDiffersWhenScopeDiffers(): void
    {
        $withoutScope = new Key('client-id', 'client-secret', null);
        $withScope = new Key('client-id', 'client-secret', 'scope');

        $this->assertNotSame($withoutScope->key(), $withScope->key());
    }

    public function testKeyDiffersWhenCredentialsDiffer(): void
    {
        $one = new Key('client-id', 'secret-a', null);
        $two = new Key('client-id', 'secret-b', null);

        $this->assertNotSame($one->key(), $two->key());
    }
}
