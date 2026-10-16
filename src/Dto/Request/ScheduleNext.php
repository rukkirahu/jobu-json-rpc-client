<?php

declare(strict_types=1);

namespace Jobu\Dto\Request;

final readonly class ScheduleNext extends ScheduleValidate
{
    protected const FIELDS = ['schedule' => 'object', 'after' => 'string', 'count' => 'int'];
    protected const REQUIRED = ['schedule', 'after'];
}
