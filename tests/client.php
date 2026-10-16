<?php

declare(strict_types=1);

use Jobu\Client;
use Jobu\Exception\ProtocolException;
use Jobu\Exception\RpcException;
use Jobu\Exception\TransportException;

spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'Jobu\\')) {
        require __DIR__ . '/../src/' . str_replace('\\', '/', substr($class, 5)) . '.php';
    }
});

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function frame(string $body): string
{
    return "Content-Length: " . strlen($body) . "\r\n\r\n" . $body;
}

/** @param resource $stream */
function request($stream): stdClass
{
    $line = fgets($stream);
    if ($line === false) {
        throw new RuntimeException('request length missing');
    }
    check(preg_match('/^Content-Length: ([0-9]+)\r\n$/D', $line, $match) === 1, 'request length');
    check(fgets($stream) === "\r\n", 'request separator');
    $body = '';
    while (strlen($body) < (int) $match[1]) {
        $body .= fread($stream, (int) $match[1] - strlen($body));
    }

    return json_decode($body, false, 64, JSON_THROW_ON_ERROR);
}

/** @param callable(resource):void $server @param callable(Client):void $test */
function withServer(callable $server, callable $test): void
{
    $path = sys_get_temp_dir() . '/jobu-php-' . bin2hex(random_bytes(8)) . '.sock';
    $listener = stream_socket_server("unix://$path", $errno, $error);
    if ($listener === false) {
        throw new RuntimeException('create test socket');
    }
    $pid = pcntl_fork();
    check($pid !== -1, 'fork test server');

    if ($pid === 0) {
        try {
            $stream = stream_socket_accept($listener, 5);
            if ($stream === false) {
                throw new RuntimeException('accept connection');
            }
            stream_set_timeout($stream, 5);
            $server($stream);
            fclose($stream);
            exit(0);
        } catch (Throwable $e) {
            fwrite(STDERR, "Server: $e\n");
            exit(1);
        }
    }

    fclose($listener);
    $client = new Client($path, 1.0);
    try {
        $test($client);
    } finally {
        $client->close();
        pcntl_waitpid($pid, $status);
        unlink($path);
    }
    check(pcntl_wifexited($status) && pcntl_wexitstatus($status) === 0, 'test server succeeded');
}

withServer(
    static function ($stream): void {
        $first = request($stream);
        check($first->method === 'system.info' && $first->params instanceof stdClass &&
            get_object_vars($first->params) === [], 'empty object params');
        $body = json_encode([
            'jsonrpc' => '2.0', 'id' => $first->id,
            'result' => ['api_version' => ['major' => 1, 'minor' => 4], 'capabilities' => ['queue.create']],
        ], JSON_THROW_ON_ERROR);
        $response = "Content-Type: application/json; charset=utf-8; profile=jobu\r\n" . frame($body);
        fwrite($stream, substr($response, 0, 18));
        fwrite($stream, substr($response, 18));

        $second = request($stream);
        check($second->method === 'queue.create' && $second->params->name === 'Tõnu', 'UTF-8 params');
        fwrite($stream, frame(json_encode([
            'jsonrpc' => '2.0', 'id' => $second->id,
            'error' => ['code' => -32000, 'message' => 'Conflict',
                'data' => ['category' => 'conflict', 'code' => 'jobu.queue.name_conflict']],
        ], JSON_THROW_ON_ERROR)));

        $third = request($stream);
        check($third->id === $second->id + 1, 'sequential request ids');
        fwrite($stream, frame('{"jsonrpc":"2.0","id":3,"result":null}'));
    },
    static function (Client $client): void {
        check($client->info()->api_version->major === 1, 'API discovery');
        try {
            $client->call('queue.create', ['name' => 'Tõnu']);
            throw new RuntimeException('expected RPC error');
        } catch (RpcException $e) {
            check($e->getCode() === -32000 && $e->category() === 'conflict' &&
                $e->operationCode() === 'jobu.queue.name_conflict', 'operation error details');
        }
        check($client->call('queue.delete', ['queue_name' => 'Tõnu']) === null, 'void result and reuse');
    }
);

withServer(
    static function ($stream): void {
        request($stream);
        fwrite($stream, frame('{"jsonrpc":"2.0","id":99,"result":{}}'));
    },
    static function (Client $client): void {
        try {
            $client->call('queue.list');
            throw new RuntimeException('expected protocol error');
        } catch (ProtocolException $e) {
            check(str_contains($e->getMessage(), 'ID'), 'reject mismatched response ID');
        }
    }
);

withServer(
    static function ($stream): void {
        request($stream);
        fwrite($stream, "content-length: 2\r\nCONTENT-LENGTH: 2\r\n\r\n{}");
    },
    static function (Client $client): void {
        try {
            $client->call('queue.list');
            throw new RuntimeException('expected protocol error');
        } catch (ProtocolException $e) {
            check(str_contains($e->getMessage(), 'Content-Length'), 'reject duplicate length');
        }
    }
);

withServer(
    static function ($stream): void {
        request($stream);
        fwrite($stream, "Content-Length: 50\r\n\r\n{}");
    },
    static function (Client $client): void {
        try {
            $client->call('queue.list');
            throw new RuntimeException('expected transport error');
        } catch (TransportException $e) {
            check(str_contains($e->getMessage(), 'body'), 'reject truncated body');
        }
    }
);

withServer(
    static function ($stream): void {
        request($stream);
        fwrite($stream, frame('not-json'));
    },
    static function (Client $client): void {
        try {
            $client->call('queue.list');
            throw new RuntimeException('expected protocol error');
        } catch (ProtocolException $e) {
            check(str_contains($e->getMessage(), 'JSON'), 'reject invalid response JSON');
        }
    }
);

echo "Client tests passed\n";
