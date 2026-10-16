<?php

declare(strict_types=1);

namespace Jobu\Dto\Request;

use Jobu\Dto\RequestDto;

final readonly class QueueCreate extends RequestDto
{
    protected const FIELDS = [
        'name' => 'string', 'weight' => 'int', 'concurrency_limit' => 'int',
        'recovery_policy' => 'string', 'defaults' => 'object',
        'history_retention_seconds' => 'int?', 'runnable_wait_warning_ms' => 'int',
        'idempotency_key' => 'string',
    ];
    protected const REQUIRED = ['name'];
}
