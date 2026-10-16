<?php

declare(strict_types=1);

namespace Jobu\Dto\Result;

use Jobu\Dto\WireDto;

final readonly class Schedule extends WireDto
{
    public function __construct(
        public string $kind,
        public ?string $at = null,
        public ?string $expression = null,
        public ?string $timezone = null,
    ) {
    }
}
