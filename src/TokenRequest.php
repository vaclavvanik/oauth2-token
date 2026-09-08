<?php

declare(strict_types=1);

namespace VaclavVanik\Oauth2Token;

use function array_filter;
use function array_unique;
use function implode;
use function sort;

final class TokenRequest
{
    /** @var string */
    private $clientId;

    /** @var string */
    private $clientSecret;

    /** @var list<string> */
    private $scopes;

    /**
     * `$clientId` and `$clientSecret` are the client's credentials for the token endpoint. `$scopes` is
     * treated as a set - empty entries are dropped, duplicates removed, order does not matter.
     *
     * @param list<string> $scopes
     *
     * @throws Exception\ValueError
     */
    public function __construct(string $clientId, string $clientSecret, array $scopes = [])
    {
        if ($clientId === '') {
            throw new Exception\ValueError('Client id cannot be empty.');
        }

        if ($clientSecret === '') {
            throw new Exception\ValueError('Client secret cannot be empty.');
        }

        $scopes = array_filter($scopes, static function (string $scope): bool {
            return $scope !== '';
        });
        $scopes = array_unique($scopes);
        sort($scopes);

        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->scopes = $scopes;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    /** @return list<string> */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    /** The scopes as one space-delimited string (RFC 6749 section 3.3), or null when none were requested. */
    public function getScope(): ?string
    {
        return $this->scopes === [] ? null : implode(' ', $this->scopes);
    }
}
