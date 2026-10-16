<?php

declare(strict_types=1);

namespace Jobu\Dto\Request;

use Jobu\Dto\RequestDto;

final readonly class JobCreate extends RequestDto
{
    protected const FIELDS = [
        'queue_id' => 'string', 'queue_name' => 'string', 'name' => 'string',
        'type' => 'string', 'schedule' => 'object', 'priority' => 'int',
        'attributes' => 'object', 'payload' => 'object', 'idempotency_key' => 'string',
    ];
    protected const REQUIRED = ['schedule', 'payload'];
    protected const SELECTOR = ['queue_id', 'queue_name'];
}
