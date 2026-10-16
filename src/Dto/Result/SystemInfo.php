<?php

declare(strict_types=1);

namespace Jobu\Dto\Result;

use Jobu\Dto\WireDto;

final readonly class SystemInfo extends WireDto
{
    public function __construct(
        public string $daemon_version,
        public ApiVersion $api_version,
        public array $capabilities,
    ) {
    }

    protected static function convert(string $field, mixed $value): mixed
    {
        return $field === 'api_version' ? ApiVersion::from($value) : $value;
    }
}
