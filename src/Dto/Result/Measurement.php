<?php

declare(strict_types=1);

namespace Jobu\Dto\Result;

use Jobu\Dto\WireDto;

final readonly class Measurement extends WireDto
{
    public function __construct(
        public string $timing,
        public string $runnable_wait,
        public string $capture,
    ) {
    }
}
