<?php

declare(strict_types=1);

namespace Jobu\Service;

use Jobu\Dto\Request\ScheduleNext;
use Jobu\Dto\Request\ScheduleValidate;
use Jobu\Dto\Result\ScheduleOccurrences;
use Jobu\Dto\Result\ScheduleValidation;

final readonly class ScheduleService extends Service
{
    public function validate(ScheduleValidate $request): ScheduleValidation
    {
        return $this->result('schedule.validate', $request, ScheduleValidation::class);
    }

    public function next(ScheduleNext $request): ScheduleOccurrences
    {
        return $this->result('schedule.next', $request, ScheduleOccurrences::class);
    }
}
