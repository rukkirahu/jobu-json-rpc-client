<?php

declare(strict_types=1);

namespace Jobu\Dto\Request;

use Jobu\Dto\RequestDto;

final readonly class QueueUpdate extends RequestDto
{
    protected const FIELDS = [
        'queue_id' => 'string', 'queue_name' => 'string', 'name' => 'string',
        'weight' => 'int', 'concurrency_limit' => 'int', 'recovery_policy' => 'string',
        'defaults' => 'object', 'history_retention_seconds' => 'int?',
        'runnable_wait_warning_ms' => 'int',
    ];
    protected const SELECTOR = ['queue_id', 'queue_name'];

    public function __construct(array $fields = [])
    {
        parent::__construct($fields);
        if (count($fields) < 2) {
            throw new \InvalidArgumentException('Queue update needs at least one setting');
        }
    }
}
