# oauth2-token

[![CI](https://github.com/vaclavvanik/oauth2-token/actions/workflows/ci.yml/badge.svg)](https://github.com/vaclavvanik/oauth2-token/actions/workflows/ci.yml)
[![Latest Stable Version](https://img.shields.io/packagist/v/vaclavvanik/oauth2-token)](https://packagist.org/packages/vaclavvanik/oauth2-token)
[![Total Downloads](https://img.shields.io/packagist/dt/vaclavvanik/oauth2-token)](https://packagist.org/packages/vaclavvanik/oauth2-token)
[![License](https://img.shields.io/packagist/l/vaclavvanik/oauth2-token)](LICENSE.md)

A small contract for obtaining an OAuth 2.0 access token, plus a caching decorator that hands back the token
it already has until it is about to expire - so an API client is not doing a token round-trip before every
request.

## Why

Plenty of APIs hand out a short-lived bearer token in exchange for a client id and secret (the
`client_credentials` grant, or something close to it). The token fetch is trivial; the annoying part is
*not doing it every time*. This package is that annoying part, done once:

- `TokenProvider` - a one-method contract, `getToken(TokenRequest $request): AccessToken`, that you implement
  once per API (or pull in a ready-made implementation).
- `CachingTokenProvider` - a decorator around any `TokenProvider` that stores the token and reuses it until
  it is within a refresh delta of expiry, then fetches a new one. It is a `RefreshableTokenProvider`, so it
  also lets you `forget()` a token that was revoked before its expiry.
- `ExpirationValidator` - a JWT access token expires on its own `exp` claim rather than a guessed lifetime;
  a JWT without `exp`, and every non-JWT token, falls back to the `expires_in` from the token response.
- Pluggable storage: in-memory for a single process, a JSON file for sharing across processes, or your own
  `Cache\Repository`.

No HTTP client is pulled in - the transport lives in the concrete `TokenProvider` you provide; the only
runtime dependencies are the `psr/http-client` and `psr/http-message` interface packages. Tested on
PHP 7.3 - 8.5.

## Install

``` bash
composer require vaclavvanik/oauth2-token
```

## Usage

### Implement the contract

```php
<?php

declare(strict_types=1);

use VaclavVanik\Oauth2Token\AccessToken;
use VaclavVanik\Oauth2Token\TokenProvider;
use VaclavVanik\Oauth2Token\TokenRequest;

final class AcmeTokenProvider implements TokenProvider
{
    // ... your PSR-18 / Guzzle / curl call ...

    public function getToken(TokenRequest $request): AccessToken
    {
        $body = $this->post('https://acme.example/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $request->getClientId(),
            'client_secret' => $request->getClientSecret(),
            'scope' => $request->getScope(), // "read write" or null
        ]);

        return AccessToken::fromValidatedArray($body);
    }
}
```

`fromValidatedArray()` needs the token lifetime to be knowable - either an `expires_in` in the response, or a
JWT `access_token` with an `exp` claim - otherwise it throws. If your provider returns an opaque token with a
lifetime you only know from its docs, supply it yourself:

```php
return AccessToken::fromArray($body + [AccessToken::EXPIRES_IN => 3600]);
```

### Wrap it with caching

```php
<?php

declare(strict_types=1);

use VaclavVanik\Oauth2Token\CachingTokenProviderFactory;
use VaclavVanik\Oauth2Token\RefreshableTokenProvider;

// Both factory methods return a RefreshableTokenProvider (getToken() + forget()).

// Shared across processes - the token survives until it expires.
$provider = CachingTokenProviderFactory::createJsonFile(new AcmeTokenProvider(), '/var/cache/acme-token.json');

// Or, for a single long-running process:
$provider = CachingTokenProviderFactory::createMemory(new AcmeTokenProvider());
```

### Use the token

```php
<?php

declare(strict_types=1);

use VaclavVanik\Oauth2Token\TokenRequest;

$token = $provider->getToken(new TokenRequest($clientId, $clientSecret));      // or (..., ['read', 'write'])

$httpRequest = $httpRequest->withHeader('Authorization', $token->headerValue()); // "Bearer eyJhbGci..."
```

`getToken()` returns a cached token when one is still valid and only calls the wrapped `TokenProvider` when
the cache misses or the stored token is within the refresh delta of expiry. Tokens are cached per client id +
secret + scope set.

There is no lock around the fetch: if several processes hit a cold or expired cache at the same moment, each
fetches its own token. Fine for most providers; if yours rate-limits the token endpoint hard, pre-warm the
cache or add locking in a custom `Repository`.

### Forcing a refresh

The cache cannot see a token that was revoked before its expiry - the API just starts rejecting it. RFC 6750
says that is a `401` with `error="invalid_token"`, but check what your API actually does (some use `403`).
Catch it, `forget()` the token, and retry once with a fresh one:

```php
<?php

declare(strict_types=1);

use VaclavVanik\Oauth2Token\TokenRequest;

$tokenRequest = new TokenRequest($clientId, $clientSecret);

$token = $provider->getToken($tokenRequest);
$response = $api->send($httpRequest->withHeader('Authorization', $token->headerValue()));

if ($response->getStatusCode() === 401) {   // adjust to your API
    $provider->forget($tokenRequest);

    $token = $provider->getToken($tokenRequest);
    $response = $api->send($httpRequest->withHeader('Authorization', $token->headerValue()));
}
```

`forget()` is part of `RefreshableTokenProvider` (what the factory returns), not the base `TokenProvider`
interface - type-hint `RefreshableTokenProvider` where you need it.

### Refresh delta

By default a token is treated as expired 60 seconds before its real expiry, leaving room for clock skew and
the request itself. Override it when building the decorator:

```php
<?php

declare(strict_types=1);

use VaclavVanik\Oauth2Token\CachingTokenProviderFactory;

$provider = CachingTokenProviderFactory::createJsonFile(
    new AcmeTokenProvider(),
    '/var/cache/acme-token.json',
    null, // clock - defaults to "now"
    120,  // refresh delta, in seconds
);
```

## Storage

| Repository | Use it for |
| --- | --- |
| [`Cache\MemoryRepository`](src/Cache/MemoryRepository.php) | a single long-running process; nothing persists |
| [`Cache\JsonFileRepository`](src/Cache/JsonFileRepository.php) | sharing a token across CLI runs / workers; writes atomically, drops expired entries on save, and treats a *corrupt* file as an empty cache the next save rebuilds (a file it cannot read at all - permissions, a directory - still throws) |
| your own [`Cache\Repository`](src/Cache/Repository.php) | Redis, APCu, PSR-6/PSR-16, ... - four methods: `has`, `load`, `save`, `delete` |

Cache entries are keyed by [`Cache\Key`](src/Cache/Key.php) - an `md5` of the client id, secret and the
`TokenRequest` scope string. Scopes are a set, so `['read', 'write']` and `['write', 'read']` share one
entry. The secret is used only to derive the hash: this package never exposes it through its API and never
puts it in an exception or a log line.

## Exceptions

`getToken()` fails in one of two ways, and callers are meant to tell them apart:

- **`Psr\Http\Client\NetworkExceptionInterface`** - the token endpoint could not be reached (DNS, connection
  refused, TLS, timeout). Transient; a retry may succeed. The `TokenProvider` contract lets this bubble up
  from the implementation untouched, so `catch (NetworkExceptionInterface $e)` around `getToken()` works.
- **`Exception\Runtime`** - the exchange itself failed (a malformed response body, a token whose lifetime
  cannot be determined, a failed cache write, a clock error). Not retryable without a change.

Everything else this package throws implements [`Exception\Exception`](src/Exception/Exception.php):

- [`Exception\ErrorResponse`](src/Exception/ErrorResponse.php) - the token endpoint answered with an OAuth
  error; carries `getError()`, `getErrorDescription()`, `getErrorUri()` and the PSR-7 `getResponse()`. Throw
  it from your `TokenProvider` implementation on a non-2xx response - `new ErrorResponse($response, $error,
  $description, $uri)` (only `$error` is required), or `ErrorResponse::fromResponse($response, $parsedBody)`
  when the body is already the RFC 6749 section 5.2 shape.
- [`Exception\Runtime`](src/Exception/Runtime.php) - as above. Corrupt cache *content* is not an error - it
  degrades to an empty cache that rebuilds itself - but a cache file that cannot be read or written at all
  (permissions, a directory) throws this, carrying the underlying reason.
- [`Cache\Exception\NotFound`](src/Cache/Exception/NotFound.php) - a repository was asked to `load()` a key it
  does not hold.

## Run check - coding standards and php-unit

Install dependencies:

```bash
make install
```

Run check:

```bash
make check
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
