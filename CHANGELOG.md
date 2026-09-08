# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 1.0.0 - Unreleased

First stable release.

### Added

- `TokenProvider` interface - `getToken(TokenRequest): AccessToken`.
- `TokenRequest` value object - client id, client secret and a set of scopes; `getScope()` joins them into the space-delimited wire string (RFC 6749 section 3.3). Rejects empty credentials with `Exception\ValueError`.
- `AccessToken` value object - `getValue()` (the token string), `getTokenType()`, `getExpiresIn()`, `headerValue()`, `isJwt()`. `fromValidatedArray()` reports every bad response - a missing property, an undeterminable lifetime (no `expires_in` and no JWT `exp`), an empty-but-present value - as `Exception\Runtime`; the constructor rejects an invalid argument with `Exception\ValueError`.
- `Exception\ValueError` - thrown when an argument is the right type but not an acceptable value (an empty string, a non-positive `expires_in`, an empty file name). `@internal`, outside the catchable `Exception\Exception` hierarchy; it becomes the native `\ValueError` once the package requires PHP >= 8.0.
- `ExpirationValidator::isExpired(AccessToken, $requestedAt, ?$now)` - whether a cached token needs replacing, with a configurable refresh delta (default 60 s): a JWT expires on its `exp` claim, a JWT without `exp` and every opaque token on the `expires_in` response lifetime.
- `Jwt\Decoder` and the `Jwt\Jwt` / `Jwt\Header` / `Jwt\Payload` / `Jwt\Signature` value objects - unverified decoding, enough to read `exp`. `Jwt\Payload` returns `null` (not an epoch) for an absent `exp` / `nbf` / `iat` and exposes `hasExpirationTime()` and friends. A string that cannot be read as a JWT raises `Jwt\Exception\InvalidToken`.
- `CachingTokenProvider` decorator and `CachingTokenProviderFactory` - transparent, expiry-aware token caching around any `TokenProvider`.
- `RefreshableTokenProvider` - a `TokenProvider` that also exposes `forget(TokenRequest)` to drop a cached token so the next call refetches, for recovering from a server-side revocation. `CachingTokenProvider` implements it; the factory returns it.
- `Cache\Repository` (`has` / `load` / `save` / `delete`) with `Cache\MemoryRepository` and `Cache\JsonFileRepository` implementations, keyed by `Cache\Key`. `Cache\JsonFileRepository` writes the file atomically, treats corrupt cache *content* as an empty cache the next save rebuilds, and throws `Exception\Runtime` (carrying the OS reason) when the file cannot be read or written at all.
- `Exception\ErrorResponse`, `Exception\Runtime` and the `Cache\Exception\*` and `Jwt\Exception\*` hierarchies, all implementing `Exception\Exception`.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.
