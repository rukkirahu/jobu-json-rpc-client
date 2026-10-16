<?php

declare(strict_types=1);

namespace Jobu\Dto\Request;

use Jobu\Dto\RequestDto;

readonly class QueueSelector extends RequestDto
{
    protected const FIELDS = ['queue_id' => 'string', 'queue_name' => 'string'];
    protected const SELECTOR = ['queue_id', 'queue_name'];

    public static function byId(string $id): static
    {
        return new static(['queue_id' => $id]);
    }

    public static function byName(string $name): static
    {
        return new static(['queue_name' => $name]);
    }
}
