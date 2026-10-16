<?php

declare(strict_types=1);

namespace Jobu\Dto\Request;

use Jobu\Dto\RequestDto;

final readonly class HttpHeader extends RequestDto
{
    protected const FIELDS = ['name' => 'string', 'value' => 'string|object', 'sensitive' => 'bool'];
    protected const REQUIRED = ['name', 'value'];
}
