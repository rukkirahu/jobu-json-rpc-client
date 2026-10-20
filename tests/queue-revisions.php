<?php

declare(strict_types=1);

use Jobu\Client;
use Jobu\Dto\Request\QueueSelector;
use Jobu\Dto\Request\QueueUpdate;
use Jobu\Exception\ProtocolException;
use Jobu\Exception\RpcException;
use Jobu\Exception\UnsupportedFeatureException;

spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'Jobu\\')) {
        require __DIR__ . '/../src/' . str_replace('\\', '/', substr($class, 5)) . '.php';
    }
});

function checkQueueCalls(array $calls, Closure $exercise): void
{
    $path = sys_get_temp_dir() . '/jobu-queue-' . bin2hex(random_bytes(8)) . '.sock';
    $listener = stream_socket_server("unix://$path", $errno, $error);
    if ($listener === false) {
        throw new RuntimeException('Cannot create queue test socket');
    }
    $pid = pcntl_fork();
    if ($pid === -1) {
        fclose($listener);
        unlink($path);
        throw new RuntimeException('Cannot fork queue test server');
    }
    if ($pid === 0) {
        try {
            $stream = stream_socket_accept($listener, 5);
            if ($stream === false) {
                throw new RuntimeException('Cannot accept queue test connection');
            }
            stream_set_timeout($stream, 2);
            foreach ($calls as [$method, $params, $reply]) {
                $header = fgets($stream);
                if ($header === false || preg_match('/^Content-Length: ([0-9]+)\r\n$/D', $header, $match) !== 1 ||
                    fgets($stream) !== "\r\n") {
                    throw new RuntimeException("Invalid request frame for $method");
                }
                $body = '';
                while (strlen($body) < (int) $match[1]) {
                    $part = fread($stream, (int) $match[1] - strlen($body));
                    if ($part === false || $part === '') {
                        throw new RuntimeException("Incomplete request for $method");
                    }
                    $body .= $part;
                }
                $request = json_decode($body, true, 64, JSON_THROW_ON_ERROR);
                if ($request['method'] !== $method || $request['params'] !== $params) {
                    throw new RuntimeException("Unexpected $method request");
                }
                $response = json_encode(['jsonrpc' => '2.0', 'id' => $request['id']] + $reply, JSON_THROW_ON_ERROR);
                fwrite($stream, 'Content-Length: ' . strlen($response) . "\r\n\r\n" . $response);
            }
            stream_set_timeout($stream, 0, 200000);
            if (fgets($stream) !== false) {
                throw new RuntimeException('Unexpected automatic retry');
            }
            fclose($stream);
            fclose($listener);
            exit(0);
        } catch (Throwable $e) {
            fwrite(STDERR, "Queue test server: $e\n");
            exit(1);
        }
    }

    fclose($listener);
    $client = new Client($path, 1.0);
    try {
        $exercise($client);
    } finally {
        $client->close();
        pcntl_waitpid($pid, $status);
        unlink($path);
    }
    if (!pcntl_wifexited($status) || pcntl_wexitstatus($status) !== 0) {
        throw new RuntimeException('Queue test server failed');
    }
}

$id = '00112233-4455-6677-8899-aabbccddeeff';
$info = static fn(int $minor): array => ['system.info', [],
    ['result' => ['daemon_version' => '1.0', 'api_version' => ['major' => 1, 'minor' => $minor],
        'capabilities' => ['queue.get', 'queue.update']]]];
$update = QueueUpdate::from(['queue_id' => $id, 'expected_revision' => 7, 'weight' => 2]);

checkQueueCalls([$info(5)], static function (Client $client) use ($update): void {
    try {
        $client->queues()->update($update);
        throw new RuntimeException('Old daemon accepted typed queue update');
    } catch (UnsupportedFeatureException $e) {
    }
});
checkQueueCalls([$info(5)], static function (Client $client) use ($id): void {
    try {
        $client->queues()->get(QueueSelector::byId($id));
        throw new RuntimeException('Old daemon accepted typed queue result');
    } catch (UnsupportedFeatureException $e) {
    }
});

checkQueueCalls([$info(6), ['queue.update',
    ['queue_id' => $id, 'expected_revision' => 7, 'weight' => 2],
    ['error' => ['code' => -32000, 'message' => 'Queue revision does not match',
        'data' => ['category' => 'conflict', 'code' => 'jobu.queue.revision_conflict']]]]],
    static function (Client $client) use ($update): void {
        try {
            $client->queues()->update($update);
            throw new RuntimeException('Stale queue update succeeded');
        } catch (RpcException $e) {
            if ($e->operationCode() !== 'jobu.queue.revision_conflict') {
                throw $e;
            }
        }
    });

checkQueueCalls([$info(6), ['queue.get', ['queue_id' => $id], ['result' => [
    'id' => $id, 'name' => 'reports', 'state' => 'active',
]]]], static function (Client $client) use ($id): void {
    try {
        $client->queues()->get(QueueSelector::byId($id));
        throw new RuntimeException('Missing queue revision accepted');
    } catch (ProtocolException $e) {
    }
});

echo "Queue revision tests passed\n";
