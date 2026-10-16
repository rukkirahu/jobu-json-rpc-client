<?php

declare(strict_types=1);

namespace Jobu\Dto\Request;

use Jobu\Dto\RequestDto;

final readonly class TimeRange extends RequestDto
{
    protected const FIELDS = ['from' => 'string', 'to' => 'string'];
}
