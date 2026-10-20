<?php

declare(strict_types=1);

namespace Jobu\Dto\Request;

final readonly class QueueGet extends QueueSelector
{
    protected const FIELDS = [
        'queue_id' => 'string', 'queue_name' => 'string', 'include_deleted' => 'bool',
    ];
}
