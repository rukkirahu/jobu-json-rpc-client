<?php

declare(strict_types=1);

namespace Jobu\Dto\Result;

use Jobu\Dto\WireDto;

final readonly class SecretPage extends WireDto
{
    public function __construct(public array $items, public ?string $next_after_name)
    {
    }

    protected static function convert(string $field, mixed $value): mixed
    {
        return $field === 'items' ? array_map(SecretMetadata::from(...), $value) : $value;
    }
}
