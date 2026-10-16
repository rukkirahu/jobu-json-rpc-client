<?php

declare(strict_types=1);

namespace Jobu\Dto\Request;

use Jobu\Dto\RequestDto;

final readonly class JobDelete extends RequestDto
{
    protected const FIELDS = ['job_id' => 'string', 'expected_revision' => 'int'];
    protected const REQUIRED = ['job_id', 'expected_revision'];
}
