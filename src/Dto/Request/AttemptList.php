<?php

declare(strict_types=1);

namespace Jobu\Dto\Request;

use Jobu\Dto\RequestDto;

final readonly class AttemptList extends RequestDto
{
    protected const FIELDS = ['run_id' => 'string', 'limit' => 'int', 'cursor' => 'string'];
    protected const REQUIRED = ['run_id'];
    protected const CONTINUATION = true;
}
