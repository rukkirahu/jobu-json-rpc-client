<?php

declare(strict_types=1);

namespace Jobu\Dto\Result;

use Jobu\Dto\WireDto;

final readonly class StatisticsPage extends WireDto
{
    public function __construct(
        public TimeWindow $window,
        public string $group_by,
        public array $groups,
        public ?string $next_cursor,
        public Measurement $measurement,
    ) {
    }

    protected static function convert(string $field, mixed $value): mixed
    {
        return match ($field) {
            'window' => TimeWindow::from($value),
            'groups' => array_map(StatisticsGroup::from(...), $value),
            'measurement' => Measurement::from($value),
            default => $value,
        };
    }
}
