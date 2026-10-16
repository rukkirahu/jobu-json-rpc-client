<?php

declare(strict_types=1);

use Jobu\Client;
use Jobu\Web\Console;

require __DIR__ . '/../vendor/autoload.php';

$firstId = '00112233-4455-6677-8899-aabbccddeeff';
$secondId = '10112233-4455-6677-8899-aabbccddeeff';
$time = '2026-01-01T00:00:00.000000Z';
$queue = static fn(string $id, string $name): array => [
    'id' => $id, 'name' => $name, 'state' => 'active', 'weight' => 1,
    'concurrency_limit' => 1, 'recovery_policy' => 'fail_interrupted',
    'defaults' => (object) [], 'history_retention_seconds' => null,
    'runnable_wait_warning_ms' => 10000, 'created_at' => $time,
    'updated_at' => $time, 'deleted_at' => null,
];
$calls = [
    ['system.info', [], [
        'daemon_version' => '1.0', 'api_version' => ['major' => 1, 'minor' => 4],
        'capabilities' => ['job.list', 'queue.list'],
    ]],
    ['job.list', ['limit' => 25, 'queue_id' => $secondId], ['items' => [], 'next_after_id' => null]],
    ['queue.list', ['limit' => 200], [
        'items' => [$queue($firstId, '<script>unsafe</script>')], 'next_after_id' => $firstId,
    ]],
    ['queue.list', ['limit' => 200, 'after_id' => $firstId], [
        'items' => [$queue($secondId, 'Archive')], 'next_after_id' => null,
    ]],
];

$socket = sys_get_temp_dir() . '/jobu-ui-jobs-' . bin2hex(random_bytes(8)) . '.sock';
$listener = stream_socket_server("unix://$socket", $errno, $error);
if ($listener === false) {
    throw new RuntimeException('Cannot create jobs UI test socket');
}
$pid = pcntl_fork();
if ($pid === -1) {
    fclose($listener);
    unlink($socket);
    throw new RuntimeException('Cannot fork jobs UI test server');
}
if ($pid === 0) {
    try {
        $stream = stream_socket_accept($listener, 5);
        if ($stream === false) {
            throw new RuntimeException('Cannot accept jobs UI test connection');
        }
        stream_set_timeout($stream, 5);
        foreach ($calls as [$method, $params, $result]) {
            $header = fgets($stream);
            if ($header === false || preg_match('/^Content-Length: ([0-9]+)\r\n$/D', $header, $match) !== 1 ||
                fgets($stream) !== "\r\n") {
                throw new RuntimeException("Invalid frame for $method");
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
                throw new RuntimeException("Unexpected params for $method");
            }
            $reply = json_encode(['jsonrpc' => '2.0', 'id' => $request['id'], 'result' => $result],
                JSON_THROW_ON_ERROR);
            fwrite($stream, 'Content-Length: ' . strlen($reply) . "\r\n\r\n" . $reply);
        }
        fclose($stream);
        fclose($listener);
        exit(0);
    } catch (Throwable $e) {
        fwrite(STDERR, "Jobs UI test server: $e\n");
        exit(1);
    }
}

fclose($listener);
$client = new Client($socket, 1.0);
try {
    $console = new Console($client);
    $view = 'jobs';
    $_GET = ['queue_id' => $secondId];
    $page = $console->page($view, $_GET);
    $info = $page['info'];
    $data = $page['data'];
    $csrf = 'test-token';
    $error = null;
    ob_start();
    try {
        require __DIR__ . '/../src/Web/template.php';
        $html = (string) ob_get_contents();
    } finally {
        ob_end_clean();
    }
    if (count($data['queues']) !== 2 ||
        !str_contains($html, 'name="queue_id" required') ||
        !str_contains($html, 'value="' . $secondId . '" selected') ||
        !str_contains($html, '&lt;script&gt;unsafe&lt;/script&gt;') ||
        str_contains($html, '<script>unsafe</script>')) {
        throw new RuntimeException('Queue dropdown did not render all queues safely');
    }
} finally {
    $client->close();
    pcntl_waitpid($pid, $status);
    unlink($socket);
}
if (!pcntl_wifexited($status) || pcntl_wexitstatus($status) !== 0) {
    throw new RuntimeException('Jobs UI test server failed');
}

echo "Jobs UI test passed\n";
