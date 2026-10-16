<?php

declare(strict_types=1);

namespace Jobu\Dto\Request;

final readonly class QueueStats extends QueueSelector
{
    protected const FIELDS = [
        'queue_id' => 'string', 'queue_name' => 'string', 'job_id' => 'string',
        'type' => 'string', 'origin' => 'string', 'planned' => 'object',
        'group_by' => 'string', 'limit' => 'int', 'cursor' => 'string',
    ];
    protected const CONTINUATION = true;
}
