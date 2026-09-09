<?php

declare(strict_types=1);

namespace VaclavVanikTest\Oauth2Token\Exception;

use DomainException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use VaclavVanik\Oauth2Token\Exception\ErrorResponse;
use VaclavVanik\Oauth2Token\Exception\Exception;

final class ErrorResponseTest extends TestCase
{
    public function testIsDomainExceptionAndPackageException(): void
    {
        $exception = new ErrorResponse($this->response(), 'invalid_client');

        $this->assertInstanceOf(DomainException::class, $exception);
        $this->assertInstanceOf(Exception::class, $exception);
    }

    public function testExposesEveryFieldPassedToTheConstructor(): void
    {
        $response = $this->response();

        $exception = new ErrorResponse($response, 'invalid_client', 'Client authentication failed', 'https://err/1');

        $this->assertSame($response, $exception->getResponse());
        $this->assertSame('invalid_client', $exception->getError());
        $this->assertSame('Client authentication failed', $exception->getErrorDescription());
        $this->assertSame('https://err/1', $exception->getErrorUri());
    }

    public function testTheOauthErrorIsAlsoTheExceptionMessage(): void
    {
        $exception = new ErrorResponse($this->response(), 'invalid_grant', 'the description');

        $this->assertSame('invalid_grant', $exception->getMessage());
    }

    public function testErrorDescriptionAndErrorUriDefaultToEmptyStrings(): void
    {
        $exception = new ErrorResponse($this->response(), 'invalid_client');

        $this->assertSame('', $exception->getErrorDescription());
        $this->assertSame('', $exception->getErrorUri());
    }

    public function testTakesAnErrorDescriptionWithoutAnErrorUri(): void
    {
        $exception = new ErrorResponse($this->response(), 'invalid_scope', 'unknown scope "x"');

        $this->assertSame('unknown scope "x"', $exception->getErrorDescription());
        $this->assertSame('', $exception->getErrorUri());
    }

    private function response(): ResponseInterface
    {
        return $this->createMock(ResponseInterface::class);
    }
}
