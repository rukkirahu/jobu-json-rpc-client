<?php

declare(strict_types=1);

namespace Jobu\Dto\Request;

final readonly class AttemptOutput extends AttemptGet
{
    protected const FIELDS = [
        'run_id' => 'string', 'attempt_number' => 'int', 'channel' => 'string',
        'offset' => 'int', 'limit' => 'int',
    ];
    protected const REQUIRED = ['run_id', 'attempt_number', 'channel'];
}
