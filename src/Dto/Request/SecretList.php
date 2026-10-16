<?php

declare(strict_types=1);

namespace Jobu\Dto\Request;

use Jobu\Dto\RequestDto;

final readonly class SecretList extends RequestDto
{
    protected const FIELDS = ['limit' => 'int', 'after_name' => 'string'];
}
