<?php

declare(strict_types=1);

namespace Jobu\Dto\Result;

use Jobu\Dto\WireDto;

final readonly class Cancellation extends WireDto
{
    public function __construct(public string $disposition, public Run $run)
    {
    }

    protected static function convert(string $field, mixed $value): mixed
    {
        return $field === 'run' ? Run::from($value) : $value;
    }
}
