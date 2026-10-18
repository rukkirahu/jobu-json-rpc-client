<?php

declare(strict_types=1);

namespace Jobu;

use Jobu\Exception\ProtocolException;
use Jobu\Exception\RpcException;
use Jobu\Exception\TransportException;
use Jobu\Service\AttemptService;
use Jobu\Service\JobService;
use Jobu\Service\QueueService;
use Jobu\Service\RunService;
use Jobu\Service\ScheduleService;
use Jobu\Service\SecretService;
use Jobu\Service\SystemService;

/**
 * Sequential JSON-RPC 2.0 client for a persistent JobU Unix socket.
 *
 * A failed transport call is never replayed automatically: a mutation may have
 * committed before its reply was lost. Do not share one instance across fibers.
 */
final class Client
{
    private const MAX_HEADER_BYTES = 16384;
    private const MAX_BODY_BYTES = 1048576;

    /** @var resource|null */
    private $stream = null;
    private int $nextId = 1;

    public function __construct(
        private readonly string $socketPath,
        private readonly float $timeout = 5.0,
    ) {
        if ($socketPath === '' || $timeout <= 0 || !is_finite($timeout)) {
            throw new \InvalidArgumentException('Socket path and positive finite timeout are required');
        }
    }

    /**
     * Send a request with object params and return its decoded JSON result.
     * Objects decode to stdClass; arrays remain arrays; void results are null.
     *
     * @throws RpcException A represented JSON-RPC or JobU operation error.
     * @throws TransportException A connection, write, read, or I/O timeout failure.
     * @throws ProtocolException Invalid framing or response from the daemon.
     */
    public function call(string $method, array|\stdClass $params = []): mixed
    {
        if ($method === '') {
            throw new \InvalidArgumentException('Method must not be empty');
        }

        // Casting an empty array is essential: JobU requires {} rather than [].
        $params = is_array($params) ? (object) $params : $params;
        $id = $this->nextId;
        $this->nextId = $id === PHP_INT_MAX ? 1 : $id + 1;

        $body = json_encode(
            ['jsonrpc' => '2.0', 'id' => $id, 'method' => $method, 'params' => $params],
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        if (strlen($body) > self::MAX_BODY_BYTES) {
            throw new \InvalidArgumentException('Request exceeds the 1 MiB body limit');
        }

        try {
            $stream = $this->connect();
            $this->writeAll($stream, "Content-Length: " . strlen($body) . "\r\n\r\n" . $body);
            $response = $this->readResponse($stream);
            return $this->result($response, $id);
        } catch (TransportException | ProtocolException $e) {
            $this->close();
            throw $e;
        }
    }

    /** Discover the daemon version and its advertised methods; reject incompatible major versions. */
    public function info(): \stdClass
    {
        $info = $this->call('system.info');
        if (!$info instanceof \stdClass ||
            !isset($info->api_version->major, $info->api_version->minor, $info->capabilities) ||
            !is_int($info->api_version->major) ||
            !is_int($info->api_version->minor) || $info->api_version->minor < 0 ||
            !is_array($info->capabilities)) {
            $this->close();
            throw new ProtocolException('Invalid system.info result');
        }
        if ($info->api_version->major !== 1) {
            $this->close();
            throw new ProtocolException('Unsupported JobU API major version');
        }

        return $info;
    }

    /** Check the method list advertised by system.info. */
    public function supports(string $method): bool
    {
        return in_array($method, $this->info()->capabilities, true);
    }

    public function system(): SystemService
    {
        return new SystemService($this);
    }

    public function queues(): QueueService
    {
        return new QueueService($this);
    }

    public function jobs(): JobService
    {
        return new JobService($this);
    }

    public function runs(): RunService
    {
        return new RunService($this);
    }

    public function attempts(): AttemptService
    {
        return new AttemptService($this);
    }

    public function secrets(): SecretService
    {
        return new SecretService($this);
    }

    public function schedules(): ScheduleService
    {
        return new ScheduleService($this);
    }

    public function close(): void
    {
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
        $this->stream = null;
    }

    public function __destruct()
    {
        $this->close();
    }

    /** @return resource */
    private function connect()
    {
        if (is_resource($this->stream)) {
            return $this->stream;
        }

        $stream = @stream_socket_client("unix://{$this->socketPath}", $errno, $error, $this->timeout);
        if ($stream === false) {
            throw new TransportException('Cannot connect to JobU socket');
        }
        $this->stream = $stream;
        $this->setTimeout($stream);
        return $stream;
    }

    /** @param resource $stream */
    private function setTimeout($stream): void
    {
        $seconds = (int) $this->timeout;
        $micros = (int) (($this->timeout - $seconds) * 1000000);
        stream_set_timeout($stream, $seconds, $micros);
    }

    /** @param resource $stream */
    private function writeAll($stream, string $bytes): void
    {
        $offset = 0;
        while ($offset < strlen($bytes)) {
            $written = @fwrite($stream, substr($bytes, $offset));
            if ($written === false || $written === 0) {
                throw new TransportException('Failed to write JobU request');
            }
            $offset += $written;
        }
    }

    /** @param resource $stream */
    private function readResponse($stream): \stdClass
    {
        $headerBytes = 0;
        $length = null;
        $contentTypeSeen = false;

        while (true) {
            $line = @fgets($stream, self::MAX_HEADER_BYTES + 1);
            if ($line === false || $line === '') {
                throw new TransportException('JobU connection closed or timed out while reading headers');
            }
            $headerBytes += strlen($line);
            if ($headerBytes > self::MAX_HEADER_BYTES || !str_ends_with($line, "\r\n")) {
                throw new ProtocolException('Invalid or oversized JobU response headers');
            }
            if ($line === "\r\n") {
                break;
            }

            if (!preg_match('/^([A-Za-z][A-Za-z0-9-]*):[ \t]*([^\r\n]*)\r\n$/D', $line, $matches)) {
                throw new ProtocolException('Malformed JobU response header');
            }
            $name = strtolower($matches[1]);
            $value = trim($matches[2]);
            if ($name === 'content-length') {
                if ($length !== null || !preg_match('/^(0|[1-9][0-9]*)$/D', $value) ||
                    strlen($value) > 7 || (int) $value > self::MAX_BODY_BYTES) {
                    throw new ProtocolException('Invalid or duplicate Content-Length');
                }
                $length = (int) $value;
            } elseif ($name === 'content-type') {
                $validType = preg_match(
                    '/^[A-Za-z0-9!#$%&\'*+.^_`|~-]+\/[A-Za-z0-9!#$%&\'*+.^_`|~-]+(?:\s*;\s*[A-Za-z][A-Za-z0-9-]*\s*=\s*(?:[A-Za-z0-9-]+|"[^"]*"))*$/D',
                    $value
                );
                preg_match_all('/(?:^|;)\s*charset\s*=\s*"?([^";\s]+)"?/i', $value, $charsets);
                if ($contentTypeSeen || !$validType || count($charsets[1]) !== 1 ||
                    !in_array(strtolower($charsets[1][0]), ['utf-8', 'utf8'], true)) {
                    throw new ProtocolException('Invalid or duplicate Content-Type');
                }
                $contentTypeSeen = true;
            }
        }

        if ($length === null || $length === 0) {
            throw new ProtocolException('Missing or empty Content-Length');
        }

        $body = '';
        while (strlen($body) < $length) {
            $part = @fread($stream, min(8192, $length - strlen($body)));
            if ($part === false || $part === '') {
                throw new TransportException('JobU connection closed or timed out while reading body');
            }
            $body .= $part;
        }

        try {
            $response = json_decode($body, false, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new ProtocolException('Invalid JobU response JSON', 0, $e);
        }
        if (!$response instanceof \stdClass) {
            throw new ProtocolException('JobU response must be an object');
        }

        return $response;
    }

    private function result(\stdClass $response, int $id): mixed
    {
        if (($response->jsonrpc ?? null) !== '2.0' ||
            !property_exists($response, 'id') || $response->id !== $id ||
            property_exists($response, 'result') === property_exists($response, 'error')) {
            throw new ProtocolException('Invalid JobU response envelope or request ID');
        }

        if (property_exists($response, 'error')) {
            $error = $response->error;
            if (!$error instanceof \stdClass ||
                !is_int($error->code ?? null) || !is_string($error->message ?? null)) {
                throw new ProtocolException('Invalid JobU error envelope');
            }
            throw new RpcException($error->code, $error->message, $error->data ?? null);
        }

        return $response->result;
    }
}
