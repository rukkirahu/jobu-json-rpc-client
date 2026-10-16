<?php

declare(strict_types=1);

namespace Jobu\Dto\Request;

use Jobu\Dto\RequestDto;

final readonly class JobList extends RequestDto
{
    protected const FIELDS = [
        'queue_id' => 'string', 'queue_name' => 'string', 'include_deleted' => 'bool',
        'state' => 'string', 'type' => 'string', 'limit' => 'int', 'after_id' => 'string',
    ];

    public function __construct(array $fields = [])
    {
        parent::__construct($fields);
        if (isset($fields['queue_id'], $fields['queue_name'])) {
            throw new \InvalidArgumentException('Only one queue selector is permitted');
        }
    }
}
