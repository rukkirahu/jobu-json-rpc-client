<?php

declare(strict_types=1);

namespace Jobu\Dto\Result;

use Jobu\Dto\WireDto;

final readonly class Duration extends WireDto
{
    public function __construct(public int $samples, public int|float|null $average, public int|float|null $maximum)
    {
    }
}
