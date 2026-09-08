<?php

declare(strict_types=1);

namespace VaclavVanikTest\Oauth2Token\Cache;

use PHPUnit\Framework\TestCase;
use VaclavVanik\Oauth2Token\AccessToken;
use VaclavVanik\Oauth2Token\Cache\Entry;

use function json_decode;
use function json_encode;

final class EntryTest extends TestCase
{
    public function testExposesConstructorValues(): void
    {
        $token = new AccessToken('Bearer', 3600, 'abc123');
        $entry = new Entry($token, 1893456000, 60);

        $this->assertSame($token, $entry->getToken());
        $this->assertSame(1893456000, $entry->getRequestTimestamp());
        $this->assertSame(60, $entry->getDelta());
    }

    public function testFromArrayRebuildsFromStoredShape(): void
    {
        $entry = Entry::fromArray([
            Entry::TOKEN => new AccessToken('Bearer', 3600, 'abc123'),
            Entry::REQUEST_TIMESTAMP => 1893456000,
            Entry::DELTA => 60,
        ]);

        $this->assertSame('abc123', $entry->getToken()->getValue());
        $this->assertSame(1893456000, $entry->getRequestTimestamp());
        $this->assertSame(60, $entry->getDelta());
    }

    public function testJsonSerializeProducesStoredShape(): void
    {
        $entry = new Entry(new AccessToken('Bearer', 3600, 'abc123'), 1893456000, 60);

        $decoded = json_decode((string) json_encode($entry), true);

        $this->assertSame(1893456000, $decoded[Entry::REQUEST_TIMESTAMP]);
        $this->assertSame(60, $decoded[Entry::DELTA]);
        $this->assertSame('abc123', $decoded[Entry::TOKEN][AccessToken::VALUE]);
    }
}
