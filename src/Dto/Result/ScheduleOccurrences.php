<?php

declare(strict_types=1);

namespace Jobu\Dto\Result;

use Jobu\Dto\WireDto;

final readonly class ScheduleOccurrences extends WireDto
{
    public function __construct(public array $occurrences)
    {
    }
}
