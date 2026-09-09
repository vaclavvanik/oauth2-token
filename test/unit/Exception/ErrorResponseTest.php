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
    public function testExposesTheOauthErrorFieldsAndResponse(): void
    {
        $response = $this->createMock(ResponseInterface::class);

        $exception = ErrorResponse::fromResponse($response, [
            ErrorResponse::ERROR => 'invalid_client',
            ErrorResponse::ERROR_DESCRIPTION => 'Client authentication failed',
            ErrorResponse::ERROR_URI => 'https://err',
        ]);

        $this->assertInstanceOf(DomainException::class, $exception);
        $this->assertInstanceOf(Exception::class, $exception);
        $this->assertSame('invalid_client', $exception->getMessage());
        $this->assertSame('invalid_client', $exception->getError());
        $this->assertSame('Client authentication failed', $exception->getErrorDescription());
        $this->assertSame('https://err', $exception->getErrorUri());
        $this->assertSame($response, $exception->getResponse());
    }

    public function testDefaultsMissingErrorFieldsToEmptyStrings(): void
    {
        $exception = ErrorResponse::fromResponse($this->createMock(ResponseInterface::class), []);

        $this->assertSame('', $exception->getError());
        $this->assertSame('', $exception->getErrorDescription());
        $this->assertSame('', $exception->getErrorUri());
    }

    public function testCanBeConstructedDirectlyWithOptionalDescriptionAndUri(): void
    {
        $response = $this->createMock(ResponseInterface::class);

        $exception = new ErrorResponse($response, 'invalid_grant');

        $this->assertSame('invalid_grant', $exception->getError());
        $this->assertSame('', $exception->getErrorDescription());
        $this->assertSame('', $exception->getErrorUri());
        $this->assertSame($response, $exception->getResponse());
    }
}
