<?php

declare(strict_types=1);

namespace Jobu\Dto\Result;

use Jobu\Dto\WireDto;

final readonly class ScheduleValidation extends WireDto
{
    public function __construct(public bool $valid)
    {
    }
}
