<?php

declare(strict_types=1);

namespace Jobu\Dto\Result;

use Jobu\Dto\WireDto;

final readonly class SecretMetadata extends WireDto
{
    public function __construct(
        public string $name,
        public string $created_at,
        public string $updated_at,
    ) {
    }
}
