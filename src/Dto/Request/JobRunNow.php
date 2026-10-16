<?php

declare(strict_types=1);

namespace Jobu\Dto\Request;

use Jobu\Dto\RequestDto;

final readonly class JobRunNow extends RequestDto
{
    protected const FIELDS = ['job_id' => 'string', 'idempotency_key' => 'string'];
    protected const REQUIRED = ['job_id'];
}
