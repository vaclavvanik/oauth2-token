<?php

declare(strict_types=1);

namespace VaclavVanik\Oauth2Token;

use Psr\Http;

interface TokenProvider
{
    /**
     * Request an access token for the given credentials and scopes.
     *
     * How the credentials reach the token endpoint is left to the implementation - an HTTP Basic
     * `Authorization` header (the method RFC 6749 recommends), form-encoded body parameters, a JSON body, ...
     *
     * @throws Http\Client\NetworkExceptionInterface The token endpoint could not be reached.
     * @throws Exception\ErrorResponse               The token endpoint returned an OAuth error response.
     * @throws Exception\Runtime                     The response could not be turned into a token.
     */
    public function getToken(TokenRequest $request): AccessToken;
}
