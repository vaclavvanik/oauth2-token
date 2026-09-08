<?php

declare(strict_types=1);

namespace VaclavVanikTest\Oauth2Token;

use PHPUnit\Framework\TestCase;
use VaclavVanik\Oauth2Token\Exception\ValueError;
use VaclavVanik\Oauth2Token\TokenRequest;

final class TokenRequestTest extends TestCase
{
    public function testExposesCredentials(): void
    {
        $request = new TokenRequest('client-id', 'client-secret');

        $this->assertSame('client-id', $request->getClientId());
        $this->assertSame('client-secret', $request->getClientSecret());
    }

    public function testRejectsAnEmptyClientId(): void
    {
        $this->expectException(ValueError::class);

        new TokenRequest('', 'client-secret');
    }

    public function testRejectsAnEmptyClientSecret(): void
    {
        $this->expectException(ValueError::class);

        new TokenRequest('client-id', '');
    }

    public function testHasNoScopeByDefault(): void
    {
        $request = new TokenRequest('client-id', 'client-secret');

        $this->assertSame([], $request->getScopes());
        $this->assertNull($request->getScope());
    }

    public function testJoinsScopesIntoTheWireString(): void
    {
        $request = new TokenRequest('client-id', 'client-secret', ['read', 'write']);

        $this->assertSame(['read', 'write'], $request->getScopes());
        $this->assertSame('read write', $request->getScope());
    }

    public function testNormalisesScopesAsASet(): void
    {
        // empty entries dropped, duplicates removed, order does not matter
        $request = new TokenRequest('client-id', 'client-secret', ['write', 'read', '', 'read']);

        $this->assertSame(['read', 'write'], $request->getScopes());
        $this->assertSame('read write', $request->getScope());
    }
}
