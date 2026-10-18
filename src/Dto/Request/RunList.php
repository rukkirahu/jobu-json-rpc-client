<?php

declare(strict_types=1);

namespace Jobu\Dto\Request;

final readonly class RunList extends SystemStats
{
    private const RUN_STATES = [
        'scheduled', 'running', 'retry_wait', 'succeeded', 'failed', 'interrupted', 'cancelled',
    ];

    protected const FIELDS = [
        'queue_id' => 'string', 'job_id' => 'string', 'state' => 'string',
        'states' => 'array', 'origin' => 'string', 'type' => 'string', 'planned' => 'object',
        'started' => 'object', 'completed' => 'object', 'limit' => 'int',
        'cursor' => 'string',
    ];

    public function __construct(array $fields = [])
    {
        parent::__construct($fields);
        if (!array_key_exists('states', $fields)) {
            return;
        }

        $states = $fields['states'];
        if (array_key_exists('state', $fields) ||
            count($states) < 1 || count($states) > 7) {
            throw new \InvalidArgumentException('run.list.states requires 1-7 distinct states and no state');
        }
        foreach ($states as $state) {
            if (!is_string($state) || !in_array($state, self::RUN_STATES, true)) {
                throw new \InvalidArgumentException('Invalid run.list state');
            }
        }
        if (count(array_unique($states)) !== count($states)) {
            throw new \InvalidArgumentException('Duplicate run.list state');
        }
    }

    public function usesStateSet(): bool
    {
        return array_key_exists('states', $this->fields);
    }
}
