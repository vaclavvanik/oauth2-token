<?php

declare(strict_types=1);

namespace VaclavVanik\Oauth2Token\Jwt;

final class Jwt
{
    /** @var Header */
    private $header;

    /** @var Payload */
    private $payload;

    /** @var Signature */
    private $signature;

    public function __construct(Header $header, Payload $payload, Signature $signature)
    {
        $this->header = $header;
        $this->payload = $payload;
        $this->signature = $signature;
    }

    public function getHeader(): Header
    {
        return $this->header;
    }

    public function getPayload(): Payload
    {
        return $this->payload;
    }

    public function getSignature(): Signature
    {
        return $this->signature;
    }
}
