<?php

declare(strict_types=1);

namespace Jobu\Dto\Result;

final readonly class Run extends RunSummary
{
    public function __construct(
        string $id,
        string $job_id,
        string $queue_id,
        int $job_revision,
        string $origin,
        string $type,
        string $state,
        bool $schedule_owned,
        int $priority,
        string $planned_at,
        string $runnable_at,
        ?string $started_at,
        ?string $completed_at,
        public \stdClass $attributes,
        public \stdClass $payload,
        public ?\stdClass $result,
    ) {
        parent::__construct(
            $id, $job_id, $queue_id, $job_revision, $origin, $type, $state,
            $schedule_owned, $priority, $planned_at, $runnable_at, $started_at, $completed_at
        );
    }
}
