<?php

declare(strict_types=1);

namespace Jobu\Dto\Request;

use Jobu\Dto\RequestDto;

readonly class AttemptGet extends RequestDto
{
    protected const FIELDS = ['run_id' => 'string', 'attempt_number' => 'int'];
    protected const REQUIRED = ['run_id', 'attempt_number'];
}
