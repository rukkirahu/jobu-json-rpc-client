<?php

declare(strict_types=1);

namespace Jobu\Dto\Result;

use Jobu\Dto\WireDto;

final readonly class Queue extends WireDto
{
    public function __construct(
        public string $id,
        public string $name,
        public string $state,
        public int $weight,
        public int $concurrency_limit,
        public string $recovery_policy,
        public \stdClass $defaults,
        public ?int $history_retention_seconds,
        public int $runnable_wait_warning_ms,
        public string $created_at,
        public string $updated_at,
        public ?string $deleted_at,
    ) {
    }
}
