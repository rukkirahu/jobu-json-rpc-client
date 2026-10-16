<?php

declare(strict_types=1);

namespace Jobu\Dto\Result;

use Jobu\Dto\WireDto;

final readonly class Job extends WireDto
{
    public function __construct(
        public string $id,
        public string $queue_id,
        public int $revision,
        public ?string $name,
        public string $state,
        public string $type,
        public Schedule $schedule,
        public int $priority,
        public \stdClass $attributes,
        public \stdClass $payload,
        public string $created_at,
        public string $updated_at,
        public ?string $deleted_at,
    ) {
    }

    protected static function convert(string $field, mixed $value): mixed
    {
        return $field === 'schedule' ? Schedule::from($value) : $value;
    }
}
