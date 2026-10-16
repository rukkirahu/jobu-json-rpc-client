<?php

declare(strict_types=1);

namespace Jobu\Dto\Request;

use Jobu\Dto\RequestDto;

readonly class ScheduleValidate extends RequestDto
{
    protected const FIELDS = ['schedule' => 'object'];
    protected const REQUIRED = ['schedule'];

    public function __construct(array $fields = [])
    {
        parent::__construct($fields);
        $schedule = $fields['schedule'];
        $shape = $schedule instanceof RequestDto ? (array) $schedule->toWire() : (array) $schedule;
        if (($shape['kind'] ?? null) !== 'cron') {
            throw new \InvalidArgumentException('Cron schedule is required');
        }
    }
}
