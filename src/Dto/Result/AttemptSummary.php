<?php

declare(strict_types=1);

namespace Jobu\Dto\Result;

use Jobu\Dto\WireDto;

readonly class AttemptSummary extends WireDto
{
    public function __construct(
        public string $run_id,
        public int $attempt_number,
        public string $due_at,
        public ?string $started_at,
        public ?string $completed_at,
        public string $state,
        public ?string $outcome,
    ) {
    }
}
