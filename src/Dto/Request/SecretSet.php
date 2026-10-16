<?php

declare(strict_types=1);

namespace Jobu\Dto\Request;

use Jobu\Dto\RequestDto;

final readonly class SecretSet extends RequestDto
{
    protected const FIELDS = ['name' => 'string', 'value' => 'object'];
    protected const REQUIRED = ['name', 'value'];
}
