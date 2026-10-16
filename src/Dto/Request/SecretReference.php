<?php

declare(strict_types=1);

namespace Jobu\Dto\Request;

use Jobu\Dto\RequestDto;

final readonly class SecretReference extends RequestDto
{
    protected const FIELDS = ['secret' => 'string'];
    protected const REQUIRED = ['secret'];

    public static function of(string $name): self
    {
        return new self(['secret' => $name]);
    }
}
