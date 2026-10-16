<?php

declare(strict_types=1);

namespace Jobu\Dto\Request;

use Jobu\Dto\RequestDto;

final readonly class QueueList extends RequestDto
{
    protected const FIELDS = [
        'include_deleted' => 'bool', 'state' => 'string', 'limit' => 'int', 'after_id' => 'string',
    ];
}
