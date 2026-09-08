<?php

declare(strict_types=1);

namespace VaclavVanik\Oauth2Token\Cache\Exception;

use DomainException;
use Throwable;
use VaclavVanik\Oauth2Token\Cache\Key;

use function sprintf;

class NotFound extends DomainException implements Exception
{
    /** @var string */
    private $clientId;

    public function __construct(
        string $clientId,
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        $this->clientId = $clientId;

        parent::__construct($message, $code, $previous);
    }

    public static function fromKey(Key $key): self
    {
        return new self(
            $key->getClientId(),
            sprintf('No cached token for client id "%s".', $key->getClientId()),
        );
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }
}
