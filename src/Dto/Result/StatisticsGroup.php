<?php

declare(strict_types=1);

namespace Jobu\Dto\Result;

use Jobu\Dto\WireDto;

final readonly class StatisticsGroup extends WireDto
{
    public function __construct(
        public ?string $key,
        public RunCounts $runs,
        public AttemptCounts $attempts,
        public CaptureCounts $capture,
        public Duration $schedule_lateness_ms,
        public Duration $execution_wall_duration_ms,
        public ?Duration $runnable_wait_ms = null,
        public ?WaitCoverage $runnable_wait_coverage = null,
    ) {
    }

    protected static function convert(string $field, mixed $value): mixed
    {
        return match ($field) {
            'runs' => RunCounts::from($value),
            'attempts' => AttemptCounts::from($value),
            'capture' => CaptureCounts::from($value),
            'schedule_lateness_ms', 'execution_wall_duration_ms' => Duration::from($value),
            'runnable_wait_ms' => $value === null ? null : Duration::from($value),
            'runnable_wait_coverage' => $value === null ? null : WaitCoverage::from($value),
            default => $value,
        };
    }
}
