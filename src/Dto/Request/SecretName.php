<?php

declare(strict_types=1);

namespace Jobu\Dto\Request;

use Jobu\Dto\RequestDto;

final readonly class SecretName extends RequestDto
{
    protected const FIELDS = ['name' => 'string'];
    protected const REQUIRED = ['name'];

    public static function of(string $name): self
    {
        return new self(['name' => $name]);
    }
}
