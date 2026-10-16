<?php

declare(strict_types=1);

namespace Jobu\Dto\Request;

final readonly class RunList extends SystemStats
{
    protected const FIELDS = [
        'queue_id' => 'string', 'job_id' => 'string', 'state' => 'string',
        'origin' => 'string', 'type' => 'string', 'planned' => 'object',
        'started' => 'object', 'completed' => 'object', 'limit' => 'int',
        'cursor' => 'string',
    ];
}
