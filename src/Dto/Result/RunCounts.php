<?php

declare(strict_types=1);

namespace Jobu\Dto\Result;

use Jobu\Dto\WireDto;

final readonly class RunCounts extends WireDto
{
    public function __construct(
        public int $total,
        public \stdClass $states,
        public \stdClass $types,
        public \stdClass $origins,
    ) {
    }
}
