<?php

declare(strict_types=1);

namespace VaclavVanik\Oauth2Token;

interface RefreshableTokenProvider extends TokenProvider
{
    /**
     * Discard any cached token for this request so the next getToken() fetches a fresh one. Use it when an
     * API rejects a token that has not expired yet - a server-side revocation the cache cannot see.
     *
     * @throws Exception\Runtime
     */
    public function forget(TokenRequest $request): void;
}
