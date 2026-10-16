<?php

declare(strict_types=1);

namespace Jobu\Dto\Result;

use Jobu\Dto\WireDto;

final readonly class ApiVersion extends WireDto
{
    public function __construct(public int $major, public int $minor)
    {
    }
}
