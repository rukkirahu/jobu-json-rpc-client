<?php

declare(strict_types=1);

namespace Jobu\Dto\Result;

final readonly class Attempt extends AttemptSummary
{
    public function __construct(
        string $run_id,
        int $attempt_number,
        string $due_at,
        ?string $started_at,
        ?string $completed_at,
        string $state,
        ?string $outcome,
        public ?\stdClass $result,
    ) {
        parent::__construct($run_id, $attempt_number, $due_at, $started_at, $completed_at, $state, $outcome);
    }
}
