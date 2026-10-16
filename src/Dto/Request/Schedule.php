<?php

declare(strict_types=1);

namespace Jobu\Dto\Request;

use Jobu\Dto\RequestDto;

final readonly class Schedule extends RequestDto
{
    protected const FIELDS = ['kind' => 'string', 'at' => 'string', 'expression' => 'string', 'timezone' => 'string'];
    protected const REQUIRED = ['kind'];

    public static function once(string $at): self
    {
        return new self(['kind' => 'once', 'at' => $at]);
    }

    public static function cron(string $expression, ?string $timezone = null): self
    {
        $fields = ['kind' => 'cron', 'expression' => $expression];
        if ($timezone !== null) {
            $fields['timezone'] = $timezone;
        }
        return new self($fields);
    }

    public function __construct(array $fields = [])
    {
        parent::__construct($fields);
        if (($fields['kind'] ?? null) === 'once' &&
            (!isset($fields['at']) || isset($fields['expression']) || isset($fields['timezone'])) ||
            ($fields['kind'] ?? null) === 'cron' &&
            (!isset($fields['expression']) || isset($fields['at'])) ||
            !in_array($fields['kind'], ['once', 'cron'], true)) {
            throw new \InvalidArgumentException('Invalid schedule shape');
        }
    }
}
