<?php

declare(strict_types=1);

namespace Jobu\Service;

use Jobu\Client;
use Jobu\Dto\RequestDto;
use Jobu\Dto\WireDto;
use Jobu\Exception\ProtocolException;

abstract readonly class Service
{
    public function __construct(protected Client $client)
    {
    }

    /**
     * @template T of WireDto
     * @param class-string<T> $type
     * @return T
     */
    protected function result(string $method, RequestDto $params, string $type): WireDto
    {
        $value = $this->client->call($method, $params->toWire());
        if (!$value instanceof \stdClass) {
            $this->client->close();
            throw new ProtocolException("Invalid $method result");
        }
        try {
            return $type::from($value);
        } catch (ProtocolException $e) {
            $this->client->close();
            throw $e;
        }
    }

    protected function voidResult(string $method, RequestDto $params): void
    {
        if ($this->client->call($method, $params->toWire()) !== null) {
            $this->client->close();
            throw new ProtocolException("Invalid $method result");
        }
    }
}
