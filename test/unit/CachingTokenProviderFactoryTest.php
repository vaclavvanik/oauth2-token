<?php

declare(strict_types=1);

namespace VaclavVanikTest\Oauth2Token;

use PHPUnit\Framework\TestCase;
use VaclavVanik\Oauth2Token\AccessToken;
use VaclavVanik\Oauth2Token\CachingTokenProviderFactory;
use VaclavVanik\Oauth2Token\RefreshableTokenProvider;
use VaclavVanik\Oauth2Token\TokenRequest;
use VaclavVanikTest\Oauth2Token\Support\FakeTokenProvider;

use function file_exists;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

final class CachingTokenProviderFactoryTest extends TestCase
{
    public function testCreateMemoryRepositoryWrapsDelegate(): void
    {
        $oauth = CachingTokenProviderFactory::createMemory(
            new FakeTokenProvider(new AccessToken('Bearer', 3600, 'fresh')),
        );

        $this->assertInstanceOf(RefreshableTokenProvider::class, $oauth);
        $this->assertSame('fresh', $oauth->getToken(new TokenRequest('client-id', 'client-secret'))->getValue());
    }

    public function testCreateJsonFileRepositoryPersistsThroughTheGivenFile(): void
    {
        $file = (string) tempnam(sys_get_temp_dir(), 'oauth-factory-');
        unlink($file);

        try {
            $request = new TokenRequest('client-id', 'client-secret');

            $first = CachingTokenProviderFactory::createJsonFile(
                new FakeTokenProvider(new AccessToken('Bearer', 3600, 'fresh')),
                $file,
            );
            $first->getToken($request);

            // A second wrapper with an empty delegate must still resolve the token from the shared file.
            $second = CachingTokenProviderFactory::createJsonFile(new FakeTokenProvider(), $file);

            $this->assertSame('fresh', $second->getToken($request)->getValue());
        } finally {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }
}
