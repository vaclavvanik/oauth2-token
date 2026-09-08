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

    private function __construct(
        Http\Message\ResponseInterface $response,
        string $error,
        string $errorDescription,
        string $errorUri
    ) {
        $this->response = $response;
        $this->error = $error;
        $this->errorDescription = $errorDescription;
        $this->errorUri = $errorUri;

        parent::__construct($error);
    }

    /**
     * @param array{error?: string, error_description?: string, error_uri?: string} $data The parsed
     *        RFC 6749 section 5.2 error response body.
     */
    public static function fromResponse(Http\Message\ResponseInterface $response, array $data): self
    {
        return new self(
            $response,
            (string) ($data[self::ERROR] ?? ''),
            (string) ($data[self::ERROR_DESCRIPTION] ?? ''),
            (string) ($data[self::ERROR_URI] ?? ''),
        );
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
