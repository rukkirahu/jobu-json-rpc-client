<?php

declare(strict_types=1);

namespace Jobu\Dto;

abstract readonly class WireDto
{
    public static function from(mixed $data): static
    {
        if (!is_array($data) && !$data instanceof \stdClass) {
            throw new \Jobu\Exception\ProtocolException('Invalid ' . static::class . ' result');
        }
        $fields = (array) $data;
        $arguments = [];
        $constructor = (new \ReflectionClass(static::class))->getConstructor();
        if ($constructor === null) {
            throw new \Jobu\Exception\ProtocolException('Invalid ' . static::class . ' result');
        }
        try {
            foreach ($constructor->getParameters() as $parameter) {
                $name = $parameter->getName();
                if (array_key_exists($name, $fields)) {
                    $arguments[$name] = static::convert($name, $fields[$name]);
                }
            }

            return new static(...$arguments);
        } catch (\TypeError | \ArgumentCountError $e) {
            throw new \Jobu\Exception\ProtocolException('Invalid ' . static::class . ' result');
        }
    }

    public function with(...$changes): static
    {
        return new static(...array_replace(get_object_vars($this), $changes));
    }

    protected static function convert(string $field, mixed $value): mixed
    {
        return $value;
    }
}
