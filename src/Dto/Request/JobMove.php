<?php

declare(strict_types=1);

namespace Jobu\Dto\Request;

use Jobu\Dto\RequestDto;

final readonly class JobMove extends RequestDto
{
    protected const FIELDS = [
        'job_id' => 'string', 'expected_revision' => 'int',
        'target_queue_id' => 'string', 'target_queue_name' => 'string',
    ];
    protected const REQUIRED = ['job_id', 'expected_revision'];
    protected const SELECTOR = ['target_queue_id', 'target_queue_name'];
}
