<?php

declare(strict_types=1);

namespace VaclavVanikTest\Oauth2Token\Support;

use VaclavVanik\Oauth2Token\AccessToken;
use VaclavVanik\Oauth2Token\TokenProvider;
use VaclavVanik\Oauth2Token\TokenRequest;

use function array_shift;

final class FakeTokenProvider implements TokenProvider
{
    /** @var int */
    public $calls = 0;

    /** @var list<TokenRequest> */
    public $requests = [];

    /** @var list<AccessToken> */
    private $tokens;

    public function __construct(AccessToken ...$tokens)
    {
        $this->tokens = $tokens;
    }

    public function getToken(TokenRequest $request): AccessToken
    {
        ++$this->calls;
        $this->requests[] = $request;

        $token = array_shift($this->tokens);

        if ($token === null) {
            throw new UnexpectedCall('FakeTokenProvider::getToken() called more times than it has tokens to return');
        }

        return $token;
    }
}
