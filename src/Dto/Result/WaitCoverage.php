<?php

declare(strict_types=1);

namespace Jobu\Dto\Result;

use Jobu\Dto\WireDto;

final readonly class WaitCoverage extends WireDto
{
    public function __construct(
        public int $complete,
        public int $partial,
        public int $unmeasured,
        public int $unfinished,
    ) {
    }
}
