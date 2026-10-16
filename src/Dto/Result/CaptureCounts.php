<?php

declare(strict_types=1);

namespace Jobu\Dto\Result;

use Jobu\Dto\WireDto;

final readonly class CaptureCounts extends WireDto
{
    public function __construct(public int $truncated_attempts, public int $lost_attempts)
    {
    }
}
