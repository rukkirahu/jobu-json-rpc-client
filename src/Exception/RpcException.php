<?php

declare(strict_types=1);

namespace Jobu\Exception;

final class RpcException extends \RuntimeException
{
    public function __construct(int $code, string $message, public readonly mixed $data = null)
    {
        parent::__construct($message, $code);
    }

    public function category(): ?string
    {
        return $this->data instanceof \stdClass && is_string($this->data->category ?? null)
            ? $this->data->category : null;
    }

    public function operationCode(): ?string
    {
        return $this->data instanceof \stdClass && is_string($this->data->code ?? null)
            ? $this->data->code : null;
    }
}
