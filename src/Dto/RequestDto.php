<?php

declare(strict_types=1);

namespace Jobu\Dto;

abstract readonly class RequestDto implements \JsonSerializable
{
    protected const FIELDS = [];
    protected const REQUIRED = [];
    protected const SELECTOR = [];
    protected const CONTINUATION = false;
    protected const ALLOW_UNKNOWN = false;

    public function __construct(protected array $fields = [])
    {
        foreach ($fields as $name => $value) {
            if (!is_string($name) || (!isset(static::FIELDS[$name]) && !static::ALLOW_UNKNOWN)) {
                throw new \InvalidArgumentException('Unknown request field');
            }
            $type = static::FIELDS[$name] ?? 'any';
            $valid = match ($type) {
                'string' => is_string($value),
                'int' => is_int($value),
                'bool' => is_bool($value),
                'string?' => is_string($value) || $value === null,
                'int?' => is_int($value) || $value === null,
                'string|object' => is_string($value) || $value instanceof self ||
                    $value instanceof \stdClass ||
                    (is_array($value) && ($value === [] || !array_is_list($value))),
                'object' => $value instanceof self || $value instanceof \stdClass ||
                    (is_array($value) && ($value === [] || !array_is_list($value))),
                'array' => is_array($value) && array_is_list($value),
                'any' => true,
                default => false,
            };
            if (!$valid) {
                throw new \InvalidArgumentException("Invalid request field: $name");
            }
        }

        if (static::CONTINUATION && array_key_exists('cursor', $fields)) {
            if (count($fields) !== 1) {
                throw new \InvalidArgumentException('Cursor continuation must contain only cursor');
            }
            return;
        }
        foreach (static::REQUIRED as $name) {
            if (!array_key_exists($name, $fields)) {
                throw new \InvalidArgumentException("Missing required request field: $name");
            }
        }
        if (static::SELECTOR !== []) {
            $count = count(array_intersect(static::SELECTOR, array_keys($fields)));
            if ($count !== 1) {
                throw new \InvalidArgumentException('Exactly one selector is required');
            }
        }
    }

    public static function from(array $fields): static
    {
        return new static($fields);
    }

    public function with(...$changes): static
    {
        return new static(array_replace($this->fields, $changes));
    }

    public function jsonSerialize(): \stdClass
    {
        $fields = [];
        foreach ($this->fields as $name => $value) {
            $encoded = self::encode($value);
            $fields[$name] = in_array(static::FIELDS[$name] ?? null, ['object', 'string|object'], true) &&
                is_array($encoded)
                ? (object) $encoded : $encoded;
        }
        return (object) $fields;
    }

    public function toWire(): \stdClass
    {
        return $this->jsonSerialize();
    }

    private static function encode(mixed $value): mixed
    {
        if ($value instanceof self) {
            return $value->jsonSerialize();
        }
        if (is_array($value)) {
            return array_map(self::encode(...), $value);
        }
        return $value;
    }
}
