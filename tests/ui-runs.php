<?php

declare(strict_types=1);

use Jobu\Client;
use Jobu\Web\Console;

require __DIR__ . '/../vendor/autoload.php';

$jobId = '00112233-4455-6677-8899-aabbccddeeff';
$missingId = '10112233-4455-6677-8899-aabbccddeeff';
$time = '2026-01-01T00:00:00.000000Z';
$run = [
    'id' => $jobId, 'job_id' => $jobId, 'queue_id' => $jobId, 'job_revision' => 1,
    'origin' => 'scheduled', 'type' => 'cli', 'state' => 'succeeded',
    'schedule_owned' => true, 'priority' => 0, 'planned_at' => $time,
    'runnable_at' => $time, 'started_at' => $time, 'completed_at' => $time,
];
$job = [
    'id' => $jobId, 'queue_id' => $jobId, 'revision' => 1, 'name' => '<report>',
    'state' => 'active', 'type' => 'cli', 'schedule' => ['kind' => 'once', 'at' => $time],
    'priority' => 0, 'attributes' => (object) [], 'payload' => ['command' => '/bin/true'],
    'created_at' => $time, 'updated_at' => $time, 'deleted_at' => null,
];
$calls = [
    ['system.info', [], ['daemon_version' => '1.0', 'api_version' => ['major' => 1, 'minor' => 4],
        'capabilities' => ['run.list', 'job.get']]],
    ['run.list', ['limit' => 25], ['items' => [
        $run,
        array_replace($run, ['id' => '20112233-4455-6677-8899-aabbccddeeff']),
        array_replace($run, ['id' => $missingId, 'job_id' => $missingId]),
    ], 'next_cursor' => null]],
    ['job.get', ['job_id' => $jobId], ['result' => $job]],
    ['job.get', ['job_id' => $missingId], ['error' => [
        'code' => -32000, 'message' => 'Job not found',
        'data' => ['category' => 'not_found', 'code' => 'jobu.job.not_found'],
    ]]],
];

$socket = sys_get_temp_dir() . '/jobu-ui-runs-' . bin2hex(random_bytes(8)) . '.sock';
$listener = stream_socket_server("unix://$socket", $errno, $error);
if ($listener === false) {
    throw new RuntimeException('Cannot create runs UI test socket');
}
$pid = pcntl_fork();
if ($pid === -1) {
    fclose($listener);
    unlink($socket);
    throw new RuntimeException('Cannot fork runs UI test server');
}
if ($pid === 0) {
    try {
        $stream = stream_socket_accept($listener, 5);
        if ($stream === false) {
            throw new RuntimeException('Cannot accept runs UI test connection');
        }
        stream_set_timeout($stream, 5);
        foreach ($calls as [$method, $params, $response]) {
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
            $envelope = ['jsonrpc' => '2.0', 'id' => $request['id']] +
                (array_key_exists('result', $response) || array_key_exists('error', $response)
                    ? $response : ['result' => $response]);
            $reply = json_encode($envelope, JSON_THROW_ON_ERROR);
            fwrite($stream, 'Content-Length: ' . strlen($reply) . "\r\n\r\n" . $reply);
        }
        fclose($stream);
        fclose($listener);
        exit(0);
    } catch (Throwable $e) {
        fwrite(STDERR, "Runs UI test server: $e\n");
        exit(1);
    }
}

fclose($listener);
$client = new Client($socket, 1.0);
try {
    $console = new Console($client);
    $view = 'runs';
    $_GET = [];
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
    if (substr_count($html, '&lt;report&gt;') !== 2 ||
        str_contains($html, '<report>') ||
        !str_contains($html, 'href="/index.php?view=job&amp;id=' . $jobId . '"') ||
        !str_contains($html, 'href="/index.php?view=job&amp;id=' . $missingId . '"') ||
        !str_contains($html, '10112233…')) {
        throw new RuntimeException('Runs job labels were not rendered correctly');
    }
} finally {
    $client->close();
    pcntl_waitpid($pid, $status);
    unlink($socket);
}
if (!pcntl_wifexited($status) || pcntl_wexitstatus($status) !== 0) {
    throw new RuntimeException('Runs UI test server failed');
}

echo "Runs UI test passed\n";
