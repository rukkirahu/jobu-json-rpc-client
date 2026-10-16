<?php

declare(strict_types=1);

namespace Jobu\Dto\Request;

use Jobu\Dto\RequestDto;

readonly class SystemStats extends RequestDto
{
    protected const FIELDS = [
        'queue_id' => 'string', 'job_id' => 'string', 'type' => 'string',
        'origin' => 'string', 'planned' => 'object', 'group_by' => 'string',
        'limit' => 'int', 'cursor' => 'string',
    ];
    protected const CONTINUATION = true;
}
