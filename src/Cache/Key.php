<?php

declare(strict_types=1);

namespace VaclavVanik\Oauth2Token\Cache;

use function md5;
use function sprintf;

/**
 * A cache entry's identity. `key()` is the ready-made hash for a keyed store; a `Repository` that wants its
 * own scheme can build one from `getClientId()` and `getScope()`. The client secret is part of the hash but
 * is never exposed - it has no place in a cache key you can list or log.
 */
final class Key
{
    /** @var string */
    private $clientId;

    /** @var string */
    private $clientSecret;

    /** @var string|null */
    private $scope;

    public function __construct(string $clientId, string $clientSecret, ?string $scope)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->scope = $scope;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function getScope(): ?string
    {
        return $this->scope;
    }

    public function key(): string
    {
        if ($this->scope === null) {
            return md5(sprintf('%s:%s', $this->clientId, $this->clientSecret));
        }

        return md5(sprintf('%s:%s:%s', $this->clientId, $this->clientSecret, $this->scope));
    }
}
