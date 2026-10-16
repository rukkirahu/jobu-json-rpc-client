<?php

declare(strict_types=1);

use Jobu\Client;
use Jobu\Web\Console;

require __DIR__ . '/../vendor/autoload.php';

$id = '00112233-4455-6677-8899-aabbccddeeff';
$time = '2026-01-01T00:00:00.000000Z';
$queue = [
    'id' => $id, 'name' => '<b>reports</b>', 'state' => 'active', 'weight' => 1,
    'concurrency_limit' => 2, 'recovery_policy' => 'fail_interrupted',
    'defaults' => (object) [], 'history_retention_seconds' => null,
    'runnable_wait_warning_ms' => 10000, 'created_at' => $time,
    'updated_at' => $time, 'deleted_at' => null,
];
$job = [
    'id' => $id, 'queue_id' => $id, 'revision' => 1, 'name' => 'First job',
    'state' => 'active', 'type' => 'cli', 'schedule' => ['kind' => 'once', 'at' => $time],
    'priority' => 0, 'attributes' => (object) [], 'payload' => ['command' => '/bin/true'],
    'created_at' => $time, 'updated_at' => $time, 'deleted_at' => null,
];
$run = [
    'id' => $id, 'job_id' => $id, 'queue_id' => $id, 'job_revision' => 1,
    'origin' => 'manual', 'type' => 'cli', 'state' => 'scheduled', 'schedule_owned' => false,
    'priority' => 0, 'planned_at' => $time, 'runnable_at' => $time,
    'started_at' => null, 'completed_at' => null,
    'attributes' => (object) [], 'payload' => ['command' => '/bin/true'], 'result' => null,
];
$calls = [
    ['system.info', [], ['daemon_version' => '1.0', 'api_version' => ['major' => 1, 'minor' => 4],
        'capabilities' => ['queue.list', 'system.stats', 'run.list']]],
    ['job.get', ['job_id' => $id], $job],
    ['run.list', ['job_id' => $id, 'limit' => 8], ['items' => [], 'next_cursor' => null]],
    ['queue.create', ['name' => 'reports', 'idempotency_key' => 'create-1'], $queue],
    ['queue.update', ['queue_id' => $id, 'history_retention_seconds' => null], $queue],
    ['queue.delete', ['queue_id' => $id], null],
    ['job.create', ['queue_id' => $id, 'schedule' => ['kind' => 'once', 'at' => 'now'],
        'payload' => ['command' => '/bin/true'], 'idempotency_key' => 'create-job-1'], $job],
    ['job.get', ['job_id' => $id], $job],
    ['job.update', ['job_id' => $id, 'expected_revision' => 1, 'name' => null], $job],
    ['job.get', ['job_id' => $id], $job],
    ['job.get', ['job_id' => $id], $job],
    ['job.update', ['job_id' => $id, 'expected_revision' => 1, 'priority' => 5], $job],
    ['job.delete', ['job_id' => $id, 'expected_revision' => 1], null],
    ['job.run_now', ['job_id' => $id, 'idempotency_key' => 'run-1'], $run],
    ['run.cancel', ['run_id' => $id], ['disposition' => 'completed', 'run' => $run]],
    ['secret.set', ['name' => 'reports.token', 'value' => ['encoding' => 'utf8', 'data' => 'example']],
        ['name' => 'reports.token', 'created_at' => $time, 'updated_at' => $time]],
    ['secret.delete', ['name' => 'reports.token'], null],
    ['job.get', ['job_id' => $id], $job],
];

$socket = sys_get_temp_dir() . '/jobu-ui-' . bin2hex(random_bytes(8)) . '.sock';
$listener = stream_socket_server("unix://$socket", $errno, $error);
if ($listener === false) {
    throw new RuntimeException('Cannot create UI test socket');
}
$pid = pcntl_fork();
if ($pid === -1) {
    fclose($listener);
    unlink($socket);
    throw new RuntimeException('Cannot fork UI test server');
}
if ($pid === 0) {
    try {
        $stream = stream_socket_accept($listener, 5);
        if ($stream === false) {
            throw new RuntimeException('Cannot accept UI test connection');
        }
        stream_set_timeout($stream, 5);
        foreach ($calls as [$method, $params, $result]) {
            $line = fgets($stream);
            if ($line === false || preg_match('/^Content-Length: ([0-9]+)\r\n$/D', $line, $match) !== 1 ||
                fgets($stream) !== "\r\n") {
                throw new RuntimeException("Bad frame for $method");
            }
            $body = '';
            while (strlen($body) < (int) $match[1]) {
                $part = fread($stream, (int) $match[1] - strlen($body));
                if ($part === false || $part === '') {
                    throw new RuntimeException("Incomplete $method request");
                }
                $body .= $part;
            }
            $request = json_decode($body, true, 64, JSON_THROW_ON_ERROR);
            if ($request['method'] !== $method || $request['params'] !== $params) {
                throw new RuntimeException("Unexpected $method request");
            }
            $reply = json_encode(['jsonrpc' => '2.0', 'id' => $request['id'], 'result' => $result],
                JSON_THROW_ON_ERROR);
            fwrite($stream, 'Content-Length: ' . strlen($reply) . "\r\n\r\n" . $reply);
        }
        fclose($stream);
        fclose($listener);
        exit(0);
    } catch (Throwable $e) {
        fwrite(STDERR, "UI test server: $e\n");
        exit(1);
    }
}

fclose($listener);
$client = new Client($socket, 1.0);
try {
    $console = new Console($client);
    $view = 'job';
    $_GET = ['id' => $id];
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
    if (!str_contains($html, 'value="First job"') ||
        !str_contains($html, 'name="priority" type="number" required value="0"') ||
        !str_contains($html, 'name="schedule_json"') ||
        !str_contains($html, '&quot;kind&quot;: &quot;once&quot;') ||
        !str_contains($html, 'name="payload_json"') ||
        !str_contains($html, '&quot;command&quot;: &quot;/bin/true&quot;') ||
        !str_contains($html, 'name="attributes_json"') ||
        !str_contains($html, 'test-token')) {
        throw new RuntimeException('Job update form did not show current values');
    }

    $actions = [
        ['queue.create', ['name' => 'reports', 'idempotency_key' => 'create-1'], 'view=queue'],
        ['queue.update', ['id' => $id, 'inherit_retention' => '1'], 'view=queue'],
        ['queue.delete', ['id' => $id, 'confirm' => 'yes'], 'view=queues'],
        ['job.create', ['queue_id' => $id, 'schedule_json' => '{"kind":"once","at":"now"}',
            'payload_json' => '{"command":"/bin/true"}', 'idempotency_key' => 'create-job-1'], 'view=job'],
        ['job.update', ['id' => $id, 'expected_revision' => '1', 'name' => '',
            'priority' => '0', 'type' => 'cli',
            'schedule_json' => '{"kind":"once","at":"' . $time . '"}',
            'payload_json' => '{"command":"/bin/true"}', 'attributes_json' => '{}'], 'view=jobs&notice=saved'],
        ['job.update', ['id' => $id, 'expected_revision' => '1', 'name' => 'First job',
            'priority' => '0', 'type' => 'cli',
            'schedule_json' => '{"kind":"once","at":"' . $time . '"}',
            'payload_json' => '{"command":"/bin/true"}', 'attributes_json' => '{}'], 'notice=unchanged'],
        ['job.update', ['id' => $id, 'expected_revision' => '1', 'name' => 'First job',
            'priority' => '5', 'type' => 'cli',
            'schedule_json' => '{"kind":"once","at":"' . $time . '"}',
            'payload_json' => '{"command":"/bin/true"}', 'attributes_json' => '{}'], 'view=jobs&notice=saved'],
        ['job.delete', ['id' => $id, 'expected_revision' => '1', 'confirm' => 'yes'], 'view=jobs'],
        ['job.run_now', ['id' => $id, 'idempotency_key' => 'run-1'], 'view=job'],
        ['run.cancel', ['id' => $id], 'view=run'],
        ['secret.set', ['name' => 'reports.token', 'encoding' => 'utf8', 'value' => 'example'], 'view=secrets'],
        ['secret.delete', ['name' => 'reports.token', 'confirm' => 'yes'], 'view=secrets'],
    ];
    foreach ($actions as [$action, $form, $destination]) {
        if (!str_contains($console->mutate($action, $form), $destination)) {
            throw new RuntimeException("Wrong redirect for $action");
        }
    }
    try {
        $console->mutate('job.update', ['id' => $id, 'expected_revision' => '0']);
        throw new RuntimeException('Stale job update was accepted');
    } catch (InvalidArgumentException $e) {
    }
    foreach ([
        ['queue.delete', ['id' => $id]],
        ['job.create', ['queue_id' => $id, 'schedule_json' => '[]',
            'payload_json' => '{"command":"/bin/true"}', 'idempotency_key' => 'x']],
    ] as [$action, $form]) {
        try {
            $console->mutate($action, $form);
            throw new RuntimeException("Unsafe $action accepted");
        } catch (InvalidArgumentException $e) {
        }
    }
} finally {
    $client->close();
    pcntl_waitpid($pid, $status);
    unlink($socket);
}
if (!pcntl_wifexited($status) || pcntl_wexitstatus($status) !== 0) {
    throw new RuntimeException('UI test server failed');
}

echo "UI tests passed\n";
