<?php

declare(strict_types=1);

namespace Jobu\Web;

use Jobu\Client;
use Jobu\Dto\Request as R;
use Jobu\Exception\RpcException;

final readonly class Console
{
    public function __construct(private Client $client)
    {
    }

    public function page(string $view, array $query): array
    {
        $info = $this->client->system()->info();
        $data = match ($view) {
            'dashboard' => [
                'stats' => $this->client->system()->stats(),
                'queues' => $this->client->queues()->list(R\QueueList::from(['limit' => 5])),
                'runs' => $this->client->runs()->list(R\RunList::from(['limit' => 6])),
            ],
            'queues' => ['page' => $this->client->queues()->list(R\QueueList::from(
                array_filter(['limit' => 25, 'after_id' => self::text($query, 'after')], static fn($v) => $v !== '')
            ))],
            'queue' => ['queue' => $this->client->queues()->get(
                R\QueueSelector::byId(self::required($query, 'id'))
            )],
            'jobs' => [
                'page' => $this->client->jobs()->list(R\JobList::from(
                    array_filter(['limit' => 25, 'after_id' => self::text($query, 'after'),
                        'queue_id' => self::text($query, 'queue_id')], static fn($v) => $v !== '')
                )),
                'queues' => $this->allQueues(),
            ],
            'job' => [
                'job' => $this->client->jobs()->get(R\JobId::of(self::required($query, 'id'))),
                'runs' => $this->client->runs()->list(R\RunList::from([
                    'job_id' => self::required($query, 'id'), 'limit' => 8,
                ])),
            ],
            'runs' => $this->runsPage($query),
            'run' => [
                'run' => $this->client->runs()->get(R\RunId::of(self::required($query, 'id'))),
                'attempts' => $this->client->attempts()->list(R\AttemptList::from(
                    self::text($query, 'cursor') === ''
                        ? ['run_id' => self::required($query, 'id'), 'limit' => 20]
                        : ['cursor' => self::text($query, 'cursor')]
                )),
            ],
            'attempt' => ['attempt' => $this->client->attempts()->get(R\AttemptGet::from([
                'run_id' => self::required($query, 'run_id'),
                'attempt_number' => self::integer($query, 'number'),
            ]))],
            'output' => ['chunk' => $this->client->attempts()->output(R\AttemptOutput::from([
                'run_id' => self::required($query, 'run_id'),
                'attempt_number' => self::integer($query, 'number'),
                'channel' => self::required($query, 'channel'),
                'offset' => self::integer($query, 'offset', 0),
                'limit' => 4096,
            ]))],
            'secrets' => ['page' => $this->client->secrets()->list(R\SecretList::from(
                array_filter(['limit' => 25, 'after_name' => self::text($query, 'after')],
                    static fn($v) => $v !== '')
            ))],
            'schedules' => $this->schedule($query),
            default => throw new \InvalidArgumentException('Unknown page'),
        };

        return ['info' => $info, 'data' => $data];
    }

    public function mutate(string $action, array $form): string
    {
        return match ($action) {
            'queue.create' => $this->createQueue($form),
            'queue.update' => $this->updateQueue($form),
            'queue.suspend' => $this->changeQueue($form, 'suspend'),
            'queue.resume' => $this->changeQueue($form, 'resume'),
            'queue.delete' => $this->changeQueue($form, 'delete'),
            'job.create' => $this->createJob($form),
            'job.update' => $this->updateJob($form),
            'job.suspend' => $this->changeJob($form, 'suspend'),
            'job.resume' => $this->changeJob($form, 'resume'),
            'job.move' => $this->changeJob($form, 'move'),
            'job.delete' => $this->changeJob($form, 'delete'),
            'job.run_now' => $this->changeJob($form, 'run_now'),
            'run.cancel' => $this->cancelRun($form),
            'secret.set' => $this->setSecret($form),
            'secret.delete' => $this->deleteSecret($form),
            default => throw new \InvalidArgumentException('Unknown action'),
        };
    }

    private function allQueues(): array
    {
        $queues = [];
        $seen = [];
        $after = null;
        do {
            $params = ['limit' => 200];
            if ($after !== null) {
                $params['after_id'] = $after;
            }
            $page = $this->client->queues()->list(R\QueueList::from($params));
            foreach ($page->items as $queue) {
                $queues[$queue->id] = $queue;
            }
            $after = $page->next_after_id;
            if ($after !== null) {
                if (isset($seen[$after])) {
                    throw new \Jobu\Exception\ProtocolException('Queue list continuation did not advance');
                }
                $seen[$after] = true;
            }
        } while ($after !== null);

        return array_values($queues);
    }

    private function runsPage(array $query): array
    {
        $page = $this->client->runs()->list(R\RunList::from(
            self::text($query, 'cursor') === ''
                ? ['limit' => 25] : ['cursor' => self::text($query, 'cursor')]
        ));
        $jobNames = [];
        foreach ($page->items as $run) {
            if (array_key_exists($run->job_id, $jobNames)) {
                continue;
            }
            $fallback = substr($run->job_id, 0, 8) . '…';
            try {
                $name = $this->client->jobs()->get(R\JobId::of($run->job_id))->name;
                $jobNames[$run->job_id] = $name !== null && $name !== '' ? $name : $fallback;
            } catch (RpcException $e) {
                if (!in_array($e->operationCode(), ['jobu.job.not_found', 'jobu.job.deleted'], true)) {
                    throw $e;
                }
                $jobNames[$run->job_id] = $fallback;
            }
        }
        return ['page' => $page, 'job_names' => $jobNames];
    }

    private function createQueue(array $form): string
    {
        $params = ['name' => self::required($form, 'name'),
            'idempotency_key' => self::required($form, 'idempotency_key')];
        foreach (['weight', 'concurrency_limit', 'runnable_wait_warning_ms',
            'history_retention_seconds'] as $field) {
            if (self::text($form, $field) !== '') {
                $params[$field] = self::integer($form, $field);
            }
        }
        if (self::text($form, 'recovery_policy') !== '') {
            $params['recovery_policy'] = self::text($form, 'recovery_policy');
        }
        if (self::text($form, 'defaults_json') !== '') {
            $params['defaults'] = self::object($form, 'defaults_json');
        }
        $queue = $this->client->queues()->create(R\QueueCreate::from($params));
        return self::url('queue', ['id' => $queue->id, 'notice' => 'saved']);
    }

    private function updateQueue(array $form): string
    {
        $id = self::required($form, 'id');
        $params = ['queue_id' => $id];
        foreach (['name', 'weight', 'concurrency_limit', 'recovery_policy',
            'runnable_wait_warning_ms'] as $field) {
            if (self::text($form, $field) !== '') {
                $params[$field] = in_array($field, ['weight', 'concurrency_limit',
                    'runnable_wait_warning_ms'], true)
                    ? self::integer($form, $field) : self::text($form, $field);
            }
        }
        if (self::text($form, 'inherit_retention') === '1') {
            $params['history_retention_seconds'] = null;
        } elseif (self::text($form, 'history_retention_seconds') !== '') {
            $params['history_retention_seconds'] = self::integer($form, 'history_retention_seconds');
        }
        if (self::text($form, 'defaults_json') !== '') {
            $params['defaults'] = self::object($form, 'defaults_json');
        }
        $this->client->queues()->update(R\QueueUpdate::from($params));
        return self::url('queue', ['id' => $id, 'notice' => 'saved']);
    }

    private function changeQueue(array $form, string $operation): string
    {
        $id = self::required($form, 'id');
        $selector = R\QueueSelector::byId($id);
        if ($operation === 'delete') {
            self::confirm($form);
            $this->client->queues()->delete($selector);
            return self::url('queues', ['notice' => 'deleted']);
        }
        if ($operation === 'suspend') {
            $this->client->queues()->suspend($selector);
        } else {
            $this->client->queues()->resume($selector);
        }
        return self::url('queue', ['id' => $id, 'notice' => 'saved']);
    }

    private function createJob(array $form): string
    {
        $params = [
            'queue_id' => self::required($form, 'queue_id'),
            'schedule' => self::object($form, 'schedule_json'),
            'payload' => self::object($form, 'payload_json'),
            'idempotency_key' => self::required($form, 'idempotency_key'),
        ];
        if (self::text($form, 'name') !== '') {
            $params['name'] = self::text($form, 'name');
        }
        if (self::text($form, 'type') !== '') {
            $params['type'] = self::text($form, 'type');
        }
        if (self::text($form, 'priority') !== '') {
            $params['priority'] = self::integer($form, 'priority');
        }
        if (self::text($form, 'attributes_json') !== '') {
            $params['attributes'] = self::object($form, 'attributes_json');
        }
        $job = $this->client->jobs()->create(R\JobCreate::from($params));
        return self::url('job', ['id' => $job->id, 'notice' => 'saved']);
    }

    private function updateJob(array $form): string
    {
        $id = self::required($form, 'id');
        $expectedRevision = self::integer($form, 'expected_revision');
        $job = $this->client->jobs()->get(R\JobId::of($id));
        if ($job->revision !== $expectedRevision) {
            throw new \InvalidArgumentException('Job changed since this page loaded. Reload before editing.');
        }

        $params = ['job_id' => $id, 'expected_revision' => $expectedRevision];
        $name = self::text($form, 'name');
        if ($name !== ($job->name ?? '')) {
            $params['name'] = $name === '' ? null : $name;
        }
        $type = self::required($form, 'type');
        if ($type !== $job->type) {
            $params['type'] = $type;
        }
        $priority = self::integer($form, 'priority');
        if ($priority !== $job->priority) {
            $params['priority'] = $priority;
        }
        $schedule = (object) array_filter(get_object_vars($job->schedule), static fn($value) => $value !== null);
        foreach (['schedule_json' => 'schedule', 'payload_json' => 'payload',
            'attributes_json' => 'attributes'] as $input => $field) {
            $value = self::object($form, $input);
            $current = match ($field) {
                'schedule' => $schedule,
                'payload' => $job->payload,
                'attributes' => $job->attributes,
            };
            if ($value != $current) {
                $params[$field] = $value;
            }
        }
        if (count($params) === 2) {
            return self::url('job', ['id' => $id, 'notice' => 'unchanged']);
        }

        $this->client->jobs()->update(R\JobUpdate::from($params));
        return self::url('jobs', ['notice' => 'saved']);
    }

    private function changeJob(array $form, string $operation): string
    {
        $id = self::required($form, 'id');
        $request = R\JobId::of($id);
        match ($operation) {
            'suspend' => $this->client->jobs()->suspend($request),
            'resume' => $this->client->jobs()->resume($request),
            'move' => $this->client->jobs()->move(R\JobMove::from([
                'job_id' => $id, 'expected_revision' => self::integer($form, 'expected_revision'),
                'target_queue_name' => self::required($form, 'target_queue_name'),
            ])),
            'run_now' => $this->client->jobs()->runNow(R\JobRunNow::from([
                'job_id' => $id, 'idempotency_key' => self::required($form, 'idempotency_key'),
            ])),
            'delete' => $this->deleteJob($form),
        };
        return $operation === 'delete'
            ? self::url('jobs', ['notice' => 'deleted'])
            : self::url('job', ['id' => $id, 'notice' => 'saved']);
    }

    private function deleteJob(array $form): void
    {
        self::confirm($form);
        $this->client->jobs()->delete(R\JobDelete::from([
            'job_id' => self::required($form, 'id'),
            'expected_revision' => self::integer($form, 'expected_revision'),
        ]));
    }

    private function cancelRun(array $form): string
    {
        $id = self::required($form, 'id');
        $this->client->runs()->cancel(R\RunId::of($id));
        return self::url('run', ['id' => $id, 'notice' => 'saved']);
    }

    private function setSecret(array $form): string
    {
        $encoding = self::text($form, 'encoding');
        if (!in_array($encoding, ['utf8', 'base64'], true)) {
            throw new \InvalidArgumentException('Invalid secret encoding');
        }
        $this->client->secrets()->set(R\SecretSet::from([
            'name' => self::required($form, 'name'),
            'value' => R\SecretValue::from([
                'encoding' => $encoding, 'data' => self::text($form, 'value'),
            ]),
        ]));
        return self::url('secrets', ['notice' => 'saved']);
    }

    private function deleteSecret(array $form): string
    {
        self::confirm($form);
        $this->client->secrets()->delete(R\SecretName::of(self::required($form, 'name')));
        return self::url('secrets', ['notice' => 'deleted']);
    }

    private function schedule(array $query): array
    {
        $expression = self::text($query, 'expression');
        if ($expression === '') {
            return [];
        }
        $schedule = R\Schedule::cron($expression, self::text($query, 'timezone') ?: null);
        $this->client->schedules()->validate(R\ScheduleValidate::from(['schedule' => $schedule]));
        $after = self::text($query, 'after') ?: gmdate('Y-m-d\TH:i:s\Z');
        return ['occurrences' => $this->client->schedules()->next(R\ScheduleNext::from([
            'schedule' => $schedule, 'after' => $after, 'count' => 5,
        ]))];
    }

    private static function text(array $source, string $name): string
    {
        $value = $source[$name] ?? '';
        if (!is_string($value) || strlen($value) > (str_ends_with($name, '_json') ? 1048576 : 65536)) {
            throw new \InvalidArgumentException("Invalid $name");
        }
        return $value;
    }

    private static function required(array $source, string $name): string
    {
        $value = self::text($source, $name);
        if ($value === '') {
            throw new \InvalidArgumentException("Missing $name");
        }
        return $value;
    }

    private static function integer(array $source, string $name, ?int $default = null): int
    {
        $value = self::text($source, $name);
        if ($value === '' && $default !== null) {
            return $default;
        }
        $integer = filter_var($value, FILTER_VALIDATE_INT);
        if ($integer === false) {
            throw new \InvalidArgumentException("Invalid $name");
        }
        return $integer;
    }

    private static function object(array $source, string $name): \stdClass
    {
        try {
            $value = json_decode(self::required($source, $name), false, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \InvalidArgumentException("Invalid JSON in $name");
        }
        if (!$value instanceof \stdClass) {
            throw new \InvalidArgumentException("$name must be a JSON object");
        }
        return $value;
    }

    private static function confirm(array $form): void
    {
        if (self::text($form, 'confirm') !== 'yes') {
            throw new \InvalidArgumentException('Confirm deletion before submitting');
        }
    }

    public static function url(string $view, array $params = []): string
    {
        return '/index.php?' . http_build_query(['view' => $view] + $params, '', '&', PHP_QUERY_RFC3986);
    }
}
