<?php

declare(strict_types=1);

namespace Jobu\Dto\Request;

use Jobu\Dto\RequestDto;

final readonly class JobId extends RequestDto
{
    protected const FIELDS = ['job_id' => 'string'];
    protected const REQUIRED = ['job_id'];

    public static function of(string $id): static
    {
        return new static(['job_id' => $id]);
    }
}
