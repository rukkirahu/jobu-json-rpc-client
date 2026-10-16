<?php

declare(strict_types=1);

namespace Jobu\Dto\Request;

use Jobu\Dto\RequestDto;

final readonly class JobUpdate extends RequestDto
{
    protected const FIELDS = [
        'job_id' => 'string', 'expected_revision' => 'int', 'name' => 'string?',
        'type' => 'string', 'schedule' => 'object', 'priority' => 'int',
        'attributes' => 'object', 'payload' => 'object',
    ];
    protected const REQUIRED = ['job_id', 'expected_revision'];

    public function __construct(array $fields = [])
    {
        parent::__construct($fields);
        if (count($fields) < 3 || count($fields) === 3 &&
            array_key_exists('attributes', $fields) &&
            ($fields['attributes'] === [] || $fields['attributes'] instanceof \stdClass &&
                get_object_vars($fields['attributes']) === [])) {
            throw new \InvalidArgumentException('Job update needs a replacement field');
        }
    }
}
