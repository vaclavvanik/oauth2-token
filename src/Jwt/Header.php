<?php

declare(strict_types=1);

namespace VaclavVanik\Oauth2Token\Jwt;

final class Header
{
    /** @var string */
    private $typ;

    /** @var string */
    private $alg;

    public const TYP = 'typ';

    public const ALG = 'alg';

    public function __construct(string $typ, string $alg)
    {
        $this->typ = $typ;
        $this->alg = $alg;
    }

    /** @param array{typ?: string, alg?: string} $data */
    public static function fromArray(array $data): self
    {
        $typ = (string) ($data[self::TYP] ?? '');
        $alg = (string) ($data[self::ALG] ?? '');

        return new self($typ, $alg);
    }

    public function getTyp(): string
    {
        return $this->typ;
    }

    public function getAlg(): string
    {
        return $this->alg;
    }
}
