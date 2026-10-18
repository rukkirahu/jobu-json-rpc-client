<?php

declare(strict_types=1);

use Jobu\Client;
use Jobu\Dto\Request as R;
use Jobu\Dto\Result as D;

spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'Jobu\\')) {
        require __DIR__ . '/../src/' . str_replace('\\', '/', substr($class, 5)) . '.php';
    }
});

$id = '00112233-4455-6677-8899-aabbccddeeff';
$time = '2026-01-01T00:00:00.000000Z';
$queue = [
    'id' => $id, 'name' => 'reports', 'state' => 'active', 'weight' => 1,
    'concurrency_limit' => 2, 'recovery_policy' => 'fail_interrupted',
    'defaults' => (object) [], 'history_retention_seconds' => null,
    'runnable_wait_warning_ms' => 10000, 'created_at' => $time,
    'updated_at' => $time, 'deleted_at' => null, 'future_member' => true,
];
$job = [
    'id' => $id, 'queue_id' => $id, 'revision' => 1, 'name' => null,
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
$attempt = [
    'run_id' => $id, 'attempt_number' => 1, 'due_at' => $time, 'started_at' => null,
    'completed_at' => null, 'state' => 'pending', 'outcome' => null, 'result' => null,
];
$metadata = ['name' => 'reports.token', 'created_at' => $time, 'updated_at' => $time];
$statsJson = file_get_contents(__DIR__ . '/fixtures/system-stats.result.json');
if ($statsJson === false) {
    throw new RuntimeException('Cannot read statistics fixture');
}
$stats = json_decode(
    $statsJson,
    true,
    64,
    JSON_THROW_ON_ERROR
);
$info = ['daemon_version' => '1.0', 'api_version' => ['major' => 1, 'minor' => 4],
    'capabilities' => ['queue.list', 'system.stats']];
$output = [
    'run_id' => $id, 'attempt_number' => 1, 'channel' => 'stdout', 'status' => 'available',
    'offset' => 0, 'bytes_returned' => 2, 'next_offset' => null, 'retained_bytes' => 2,
    'total_bytes' => 2, 'omitted_bytes' => 0, 'truncated' => false,
    'capture_lost' => false, 'encoding' => 'base64', 'data' => 'AP8=',
];
$cases = [
    ['system.info', [], $info, D\SystemInfo::class, static fn(Client $c) => $c->system()->info()],
    ['system.stats', ['group_by' => 'none'], $stats, D\StatisticsPage::class,
        static fn(Client $c) => $c->system()->stats(R\SystemStats::from(['group_by' => 'none']))],
    ['queue.create', ['name' => 'reports', 'history_retention_seconds' => null], $queue, D\Queue::class,
        static fn(Client $c) => $c->queues()->create(R\QueueCreate::from(['name' => 'reports'])
            ->with(history_retention_seconds: null))],
    ['queue.get', ['queue_name' => 'reports'], $queue, D\Queue::class,
        static fn(Client $c) => $c->queues()->get(R\QueueSelector::byName('reports'))],
    ['queue.list', [], ['items' => [$queue], 'next_after_id' => null], D\QueuePage::class,
        static fn(Client $c) => $c->queues()->list()],
    ['queue.update', ['queue_name' => 'reports', 'history_retention_seconds' => null], $queue, D\Queue::class,
        static fn(Client $c) => $c->queues()->update(R\QueueUpdate::from([
            'queue_name' => 'reports', 'history_retention_seconds' => null,
        ]))],
    ['queue.suspend', ['queue_id' => $id], $queue, D\Queue::class,
        static fn(Client $c) => $c->queues()->suspend(R\QueueSelector::byId($id))],
    ['queue.resume', ['queue_id' => $id], $queue, D\Queue::class,
        static fn(Client $c) => $c->queues()->resume(R\QueueSelector::byId($id))],
    ['queue.delete', ['queue_id' => $id], null, null,
        static fn(Client $c) => $c->queues()->delete(R\QueueSelector::byId($id))],
    ['queue.stats', ['queue_name' => 'reports'], $stats, D\StatisticsPage::class,
        static fn(Client $c) => $c->queues()->stats(R\QueueStats::byName('reports'))],
    ['job.create', ['queue_name' => 'reports', 'schedule' => ['kind' => 'once', 'at' => 'now'],
        'payload' => ['command' => '/bin/true']], $job, D\Job::class,
        static fn(Client $c) => $c->jobs()->create(R\JobCreate::from([
            'queue_name' => 'reports', 'schedule' => R\Schedule::once('now'),
            'payload' => R\Payload::cli('/bin/true'),
        ]))],
    ['job.get', ['job_id' => $id], $job, D\Job::class,
        static fn(Client $c) => $c->jobs()->get(R\JobId::of($id))],
    ['job.list', ['limit' => 1], ['items' => [$job], 'next_after_id' => $id], D\JobPage::class,
        static fn(Client $c) => $c->jobs()->list(R\JobList::from(['limit' => 1]))],
    ['job.update', ['job_id' => $id, 'expected_revision' => 1, 'name' => null], $job, D\Job::class,
        static fn(Client $c) => $c->jobs()->update(R\JobUpdate::from([
            'job_id' => $id, 'expected_revision' => 1, 'name' => null,
        ]))],
    ['job.suspend', ['job_id' => $id], $job, D\Job::class,
        static fn(Client $c) => $c->jobs()->suspend(R\JobId::of($id))],
    ['job.resume', ['job_id' => $id], $job, D\Job::class,
        static fn(Client $c) => $c->jobs()->resume(R\JobId::of($id))],
    ['job.move', ['job_id' => $id, 'expected_revision' => 1, 'target_queue_name' => 'archive'],
        $job, D\Job::class, static fn(Client $c) => $c->jobs()->move(R\JobMove::from([
            'job_id' => $id, 'expected_revision' => 1, 'target_queue_name' => 'archive',
        ]))],
    ['job.delete', ['job_id' => $id, 'expected_revision' => 1], null, null,
        static fn(Client $c) => $c->jobs()->delete(R\JobDelete::from([
            'job_id' => $id, 'expected_revision' => 1,
        ]))],
    ['job.run_now', ['job_id' => $id, 'idempotency_key' => 'run-1'], $run, D\Run::class,
        static fn(Client $c) => $c->jobs()->runNow(R\JobRunNow::from([
            'job_id' => $id, 'idempotency_key' => 'run-1',
        ]))],
    ['run.get', ['run_id' => $id], $run, D\Run::class,
        static fn(Client $c) => $c->runs()->get(R\RunId::of($id))],
    ['run.list', ['cursor' => 'token'], ['items' => [$run], 'next_cursor' => null], D\RunPage::class,
        static fn(Client $c) => $c->runs()->list(R\RunList::from(['cursor' => 'token']))],
    ['run.cancel', ['run_id' => $id], ['disposition' => 'requested', 'run' => $run], D\Cancellation::class,
        static fn(Client $c) => $c->runs()->cancel(R\RunId::of($id))],
    ['attempt.get', ['run_id' => $id, 'attempt_number' => 1], $attempt, D\Attempt::class,
        static fn(Client $c) => $c->attempts()->get(R\AttemptGet::from([
            'run_id' => $id, 'attempt_number' => 1,
        ]))],
    ['attempt.list', ['cursor' => 'token'], ['items' => [$attempt], 'next_cursor' => null],
        D\AttemptPage::class,
        static fn(Client $c) => $c->attempts()->list(R\AttemptList::from(['cursor' => 'token']))],
    ['attempt.output', ['run_id' => $id, 'attempt_number' => 1, 'channel' => 'stdout'],
        $output, D\OutputChunk::class,
        static fn(Client $c) => $c->attempts()->output(R\AttemptOutput::from([
            'run_id' => $id, 'attempt_number' => 1, 'channel' => 'stdout',
        ]))],
    ['secret.set', ['name' => 'reports.token', 'value' => ['encoding' => 'base64', 'data' => 'AP8=']],
        $metadata, D\SecretMetadata::class,
        static fn(Client $c) => $c->secrets()->set(R\SecretSet::from([
            'name' => 'reports.token', 'value' => R\SecretValue::binary("\0\xff"),
        ]))],
    ['secret.list', [], ['items' => [$metadata], 'next_after_name' => null], D\SecretPage::class,
        static fn(Client $c) => $c->secrets()->list()],
    ['secret.delete', ['name' => 'reports.token'], null, null,
        static fn(Client $c) => $c->secrets()->delete(R\SecretName::of('reports.token'))],
    ['schedule.validate', ['schedule' => ['kind' => 'cron', 'expression' => '@daily']],
        ['valid' => true], D\ScheduleValidation::class,
        static fn(Client $c) => $c->schedules()->validate(R\ScheduleValidate::from([
            'schedule' => R\Schedule::cron('@daily'),
        ]))],
    ['schedule.next', ['schedule' => ['kind' => 'cron', 'expression' => '@daily'], 'after' => $time],
        ['occurrences' => [$time]], D\ScheduleOccurrences::class,
        static fn(Client $c) => $c->schedules()->next(R\ScheduleNext::from([
            'schedule' => R\Schedule::cron('@daily'), 'after' => $time,
        ]))],
];

if (count($cases) !== 30) {
    throw new RuntimeException('Expected all 30 methods');
}

$path = sys_get_temp_dir() . '/jobu-services-' . bin2hex(random_bytes(8)) . '.sock';
$listener = stream_socket_server("unix://$path", $errno, $error);
if ($listener === false) {
    throw new RuntimeException('Cannot create test socket');
}
$pid = pcntl_fork();
if ($pid === -1) {
    fclose($listener);
    unlink($path);
    throw new RuntimeException('Cannot fork test server');
}
if ($pid === 0) {
    try {
        $stream = stream_socket_accept($listener, 5);
        if ($stream === false) {
            throw new RuntimeException('Cannot accept test connection');
        }
        stream_set_timeout($stream, 5);
        foreach ($cases as [$method, $params, $result]) {
            $header = fgets($stream);
            if ($header === false || preg_match('/^Content-Length: ([0-9]+)\r\n$/D', $header, $match) !== 1 ||
                fgets($stream) !== "\r\n") {
                throw new RuntimeException("Invalid frame for $method");
            }
            $body = '';
            while (strlen($body) < (int) $match[1]) {
                $chunk = fread($stream, (int) $match[1] - strlen($body));
                if ($chunk === false || $chunk === '') {
                    throw new RuntimeException("Incomplete request for $method");
                }
                $body .= $chunk;
            }
            $request = json_decode($body, true, 64, JSON_THROW_ON_ERROR);
            if ($request['method'] !== $method || $request['params'] !== $params) {
                throw new RuntimeException("Wrong request for $method");
            }
            $reply = json_encode([
                'jsonrpc' => '2.0', 'id' => $request['id'], 'result' => $result,
            ], JSON_THROW_ON_ERROR);
            fwrite($stream, 'Content-Length: ' . strlen($reply) . "\r\n\r\n" . $reply);
        }
        fclose($stream);
        fclose($listener);
        exit(0);
    } catch (Throwable $e) {
        fwrite(STDERR, "Service server: $e\n");
        exit(1);
    }
}

fclose($listener);
$client = new Client($path, 1.0);
try {
    foreach ($cases as [$method, , , $type, $call]) {
        $value = $call($client);
        if ($type === null ? $value !== null : !$value instanceof $type) {
            throw new RuntimeException("Wrong return type for $method");
        }
        if ($method === 'queue.list' && !$value->items[0] instanceof D\Queue ||
            $method === 'system.stats' && !$value->groups[0] instanceof D\StatisticsGroup ||
            $method === 'attempt.output' && $value->bytes() !== "\0\xff") {
            throw new RuntimeException("Wrong nested result for $method");
        }
    }
} finally {
    $client->close();
    pcntl_waitpid($pid, $status);
    unlink($path);
}
if (!pcntl_wifexited($status) || pcntl_wexitstatus($status) !== 0) {
    throw new RuntimeException('Service test server failed');
}

foreach ([
    static fn() => R\QueueSelector::from(['queue_id' => $id, 'queue_name' => 'reports']),
    static fn() => R\AttemptList::from(['cursor' => 'token', 'run_id' => $id]),
    static fn() => R\JobUpdate::from(['job_id' => $id, 'expected_revision' => 1]),
    static fn() => R\QueueCreate::from(['name' => 'reports', 'unexpected' => 'x']),
    static fn() => R\RunList::from(['states' => []]),
    static fn() => R\RunList::from(['states' => ['failed', 'failed']]),
    static fn() => R\RunList::from(['states' => ['failed', 'bogus']]),
    static fn() => R\RunList::from(['states' => [['failed']]]),
    static fn() => R\RunList::from(['states' => ['failed'], 'state' => 'failed']),
    static fn() => R\RunList::from(['states' => ['failed'], 'cursor' => 'token']),
    static fn() => R\RunList::from(['states' => array_fill(0, 8, 'failed')]),
] as $invalid) {
    try {
        $invalid();
        throw new RuntimeException('Invalid request was accepted');
    } catch (InvalidArgumentException $e) {
    }
}
$states = R\RunList::from(['states' => ['failed', 'interrupted']]);
if (!$states->usesStateSet() || $states->toWire()->states !== ['failed', 'interrupted'] ||
    R\RunList::from(['state' => 'failed'])->usesStateSet()) {
    throw new RuntimeException('Run state set was not encoded correctly');
}

$plainUpdate = R\JobUpdate::from(['job_id' => $id, 'expected_revision' => 1, 'priority' => 2]);
if (property_exists($plainUpdate->toWire(), 'name') ||
    !property_exists($plainUpdate->with(name: null)->toWire(), 'name')) {
    throw new RuntimeException('Omission and null were conflated');
}
$payload = R\Payload::cli('/bin/true')->with(
    arguments: [R\SecretReference::of('reports.token')],
    environment: [],
);
$encoded = $payload->toWire();
if (!$encoded->arguments[0] instanceof stdClass ||
    $encoded->arguments[0]->secret !== 'reports.token' ||
    !$encoded->environment instanceof stdClass) {
    throw new RuntimeException('Nested payload or empty object encoded incorrectly');
}
$httpPayload = R\Payload::http('http://127.0.0.1:8080/events')->with(method: 'pOsT');
if ($httpPayload->toWire()->method !== 'pOsT' ||
    property_exists(R\Payload::http('http://127.0.0.1:8080/events')->toWire(), 'method')) {
    throw new RuntimeException('HTTP method input must reach daemon admission unchanged');
}
$futureQueue = D\Queue::from((object) array_replace($queue, ['state' => 'new_future_state']));
if ($futureQueue->state !== 'new_future_state' || $futureQueue->deleted_at !== null) {
    throw new RuntimeException('Additive result compatibility failed');
}
if (get_object_vars(R\QueueStats::from(['cursor' => 'token'])->toWire()) !== ['cursor' => 'token'] ||
    get_object_vars(R\AttemptList::from(['cursor' => 'token'])->toWire()) !== ['cursor' => 'token']) {
    throw new RuntimeException('Cursor-only continuation was not preserved');
}
try {
    D\Queue::from((object) ['id' => $id]);
    throw new RuntimeException('Incomplete queue result accepted');
} catch (Jobu\Exception\ProtocolException $e) {
}

echo "Service tests passed\n";
