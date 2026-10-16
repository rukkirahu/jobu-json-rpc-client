<?php

declare(strict_types=1);

namespace Jobu\Dto\Result;

use Jobu\Dto\WireDto;

readonly class RunSummary extends WireDto
{
    public function __construct(
        public string $id,
        public string $job_id,
        public string $queue_id,
        public int $job_revision,
        public string $origin,
        public string $type,
        public string $state,
        public bool $schedule_owned,
        public int $priority,
        public string $planned_at,
        public string $runnable_at,
        public ?string $started_at,
        public ?string $completed_at,
    ) {
    }
}
