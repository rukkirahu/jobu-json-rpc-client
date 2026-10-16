<?php

declare(strict_types=1);

namespace Jobu\Dto\Result;

use Jobu\Dto\WireDto;

final readonly class OutputChunk extends WireDto
{
    public function __construct(
        public string $run_id,
        public int $attempt_number,
        public string $channel,
        public string $status,
        public int $offset,
        public int $bytes_returned,
        public ?int $next_offset,
        public int $retained_bytes,
        public ?int $total_bytes,
        public ?int $omitted_bytes,
        public bool $truncated,
        public bool $capture_lost,
        public string $encoding,
        public string $data,
    ) {
    }

    public function bytes(): string
    {
        if ($this->encoding === 'utf8') {
            return $this->data;
        }
        $bytes = $this->encoding === 'base64' ? base64_decode($this->data, true) : false;
        if ($bytes === false) {
            throw new \Jobu\Exception\ProtocolException('Invalid attempt output encoding');
        }
        return $bytes;
    }
}
