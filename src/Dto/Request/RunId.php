<?php

declare(strict_types=1);

namespace Jobu\Dto\Request;

use Jobu\Dto\RequestDto;

final readonly class RunId extends RequestDto
{
    protected const FIELDS = ['run_id' => 'string'];
    protected const REQUIRED = ['run_id'];

    public static function of(string $id): self
    {
        return new self(['run_id' => $id]);
    }
}
