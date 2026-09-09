<?php

declare(strict_types=1);

namespace VaclavVanik\Oauth2Token\Exception;

use DomainException;
use Psr\Http;

final class ErrorResponse extends DomainException implements Exception
{
    /** @var Http\Message\ResponseInterface */
    private $response;

    /** @var string */
    private $error;

    /** @var string */
    private $errorDescription;

    /** @var string */
    private $errorUri;

    public const ERROR = 'error';

    public const ERROR_DESCRIPTION = 'error_description';

    public const ERROR_URI = 'error_uri';

    public function __construct(
        Http\Message\ResponseInterface $response,
        string $error,
        string $errorDescription = '',
        string $errorUri = ''
    ) {
        $this->response = $response;
        $this->error = $error;
        $this->errorDescription = $errorDescription;
        $this->errorUri = $errorUri;

        parent::__construct($error);
    }

    public function getResponse(): Http\Message\ResponseInterface
    {
        return $this->response;
    }

    public function getError(): string
    {
        return $this->error;
    }

    public function getErrorDescription(): string
    {
        return $this->errorDescription;
    }

    public function getErrorUri(): string
    {
        return $this->errorUri;
    }
}
