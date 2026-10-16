<?php

declare(strict_types=1);

namespace Jobu\Dto\Request;

use Jobu\Dto\RequestDto;

final readonly class Payload extends RequestDto
{
    protected const ALLOW_UNKNOWN = true;
    protected const FIELDS = [
        'command' => 'string', 'arguments' => 'array', 'working_directory' => 'string',
        'environment' => 'object', 'expected_exit_codes' => 'array',
        'url' => 'string', 'method' => 'string', 'headers' => 'array',
        'body' => 'object', 'expected_statuses' => 'array',
    ];

    public function __construct(array $fields = [])
    {
        parent::__construct($fields);
        if (isset($fields['command']) === isset($fields['url'])) {
            throw new \InvalidArgumentException('Payload requires command or URL');
        }
    }

    public static function cli(string $command): self
    {
        return new self(['command' => $command]);
    }

    public static function http(string $url): self
    {
        return new self(['url' => $url]);
    }
}
