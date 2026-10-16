<?php

declare(strict_types=1);

namespace Jobu\Dto\Request;

use Jobu\Dto\RequestDto;

final readonly class SecretValue extends RequestDto
{
    protected const FIELDS = ['encoding' => 'string', 'data' => 'string'];
    protected const REQUIRED = ['encoding', 'data'];

    public function __construct(array $fields = [])
    {
        parent::__construct($fields);
        if (!in_array($fields['encoding'], ['utf8', 'base64'], true)) {
            throw new \InvalidArgumentException('Invalid secret encoding');
        }
    }

    public static function utf8(string $text): self
    {
        return new self(['encoding' => 'utf8', 'data' => $text]);
    }

    public static function binary(string $bytes): self
    {
        return new self(['encoding' => 'base64', 'data' => base64_encode($bytes)]);
    }
}
