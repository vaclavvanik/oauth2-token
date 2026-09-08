<?php

declare(strict_types=1);

namespace VaclavVanik\Oauth2Token\Jwt;

final class Signature
{
    /** @var string */
    private $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function fromString(string $data): self
    {
        return new self($data);
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
