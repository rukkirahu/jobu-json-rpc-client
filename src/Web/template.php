<?php

declare(strict_types=1);

namespace Jobu\Web;

function h(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function path(string $view, array $params = []): string
{
    return h(Console::url($view, $params));
}

function formStart(string $view, string $action, string $csrf, array $fields = []): void
{
    echo '<form method="post" action="', path($view), '">',
        '<input type="hidden" name="csrf" value="', h($csrf), '">',
        '<input type="hidden" name="action" value="', h($action), '">';
    foreach ($fields as $name => $value) {
        echo '<input type="hidden" name="', h($name), '" value="', h($value), '">';
    }
}

$titles = [
    'dashboard' => 'Overview', 'queues' => 'Queues', 'queue' => 'Queue details',
    'jobs' => 'Jobs', 'job' => 'Job details', 'runs' => 'Runs', 'run' => 'Run details',
    'attempt' => 'Attempt details', 'output' => 'Retained output',
    'secrets' => 'Secrets', 'schedules' => 'Schedule lab',
];
$title = $titles[$view] ?? 'JobU';
$notice = $_GET['notice'] ?? '';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($title) ?> · JobU</title>
    <link rel="stylesheet" href="/assets/app.css">
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <a class="brand" href="<?= path('dashboard') ?>"><span class="brand-mark">J<span>U</span></span><span>JobU<small>CONTROL ROOM</small></span></a>
        <div class="nav-label">WORKSPACE</div>
        <nav aria-label="Main navigation">
            <?php foreach (['dashboard' => 'Overview', 'queues' => 'Queues', 'jobs' => 'Jobs',
                'runs' => 'Runs', 'secrets' => 'Secrets', 'schedules' => 'Schedules'] as $tab => $label): ?>
                <a class="<?= $view === $tab || rtrim($view, 's') === rtrim($tab, 's') ? 'active' : '' ?>"
                   href="<?= path($tab) ?>"><?= h($label) ?><span aria-hidden="true">↗</span></a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-bottom">
            <div class="nav-label">CONNECTION</div>
            <span class="connection <?= $error === null ? 'ok' : 'down' ?>">
                <?= $error === null ? 'Daemon connected' : 'Daemon unavailable' ?>
            </span>
            <?php if ($info !== null): ?>
                <small>jobud <?= h($info->daemon_version) ?> · API <?= h($info->api_version->major) ?>.<?= h($info->api_version->minor) ?></small>
            <?php endif; ?>
        </div>
    </aside>

    <main class="main">
        <header class="topbar">
            <div><span class="eyebrow">JOBU / OPERATOR CONSOLE</span><h1><?= h($title) ?></h1></div>
            <div class="topbar-right"><span class="date"><?= h(gmdate('d M Y · H:i')) ?> UTC</span>
                <span class="live-dot">LIVE</span></div>
        </header>

        <?php if ($error !== null): ?>
            <section class="alert error" role="alert"><strong>Unable to complete request</strong><p><?= h($error) ?></p>
                <a href="<?= path($view) ?>">Reload this page</a></section>
        <?php elseif (is_string($notice) && in_array($notice, ['saved', 'deleted', 'unchanged'], true)): ?>
            <section class="alert success" role="status"><?= match ($notice) {
                'deleted' => 'Item deleted.', 'unchanged' => 'No changes to save.', default => 'Change saved.',
            } ?></section>
        <?php endif; ?>

        <?php if ($error === null): ?>
        <?php switch ($view):
            case 'dashboard':
                $group = $data['stats']->groups[0] ?? null; ?>
                <p class="intro">A live view of scheduled work and the daemon behind it.</p>
                <div class="metric-grid">
                    <div class="metric"><span>RETAINED RUNS <small>24H</small></span><strong><?= h($group?->runs->total ?? 0) ?></strong><small>Planned in the last 24 hours</small></div>
                    <div class="metric"><span>RUNNING <small>24H</small></span><strong><?= h($group?->runs->states->running ?? 0) ?></strong><small>Among retained runs</small></div>
                    <div class="metric"><span>FAILED <small>24H</small></span><strong><?= h($group?->runs->states->failed ?? 0) ?></strong><small>Among retained runs</small></div>
                    <div class="metric"><span>ATTEMPTS <small>24H</small></span><strong><?= h($group?->attempts->total ?? 0) ?></strong><small>Across retained runs</small></div>
                </div>
                <div class="two-col">
                    <section class="panel"><div class="panel-head"><div><span class="eyebrow">CAPACITY</span><h2>Queues</h2></div><a href="<?= path('queues') ?>">View all →</a></div>
                        <?php foreach ($data['queues']->items as $queue): ?>
                            <a class="list-row" href="<?= path('queue', ['id' => $queue->id]) ?>"><strong><?= h($queue->name) ?></strong><span class="state"><?= h($queue->state) ?></span></a>
                        <?php endforeach; ?>
                        <?php if ($data['queues']->items === []): ?><p class="empty">No queues created yet.</p><?php endif; ?>
                    </section>
                    <section class="panel"><div class="panel-head"><div><span class="eyebrow">ACTIVITY</span><h2>Recent runs</h2></div><a href="<?= path('runs') ?>">View all →</a></div>
                        <?php foreach ($data['runs']->items as $run): ?>
                            <a class="list-row" href="<?= path('run', ['id' => $run->id]) ?>"><strong><?= h(substr($run->id, 0, 8)) ?>…</strong><span class="state"><?= h($run->state) ?></span></a>
                        <?php endforeach; ?>
                        <?php if ($data['runs']->items === []): ?><p class="empty">No retained runs yet.</p><?php endif; ?>
                    </section>
                </div>
                <?php break;

            case 'queues': ?>
                <div class="section-head"><p class="intro">Control workload capacity and queue lifecycle.</p><a class="button secondary" href="<?= path('dashboard') ?>">← Overview</a></div>
                <section class="panel"><div class="panel-head"><div><span class="eyebrow">INVENTORY</span><h2>Queues</h2></div><span class="count"><?= count($data['page']->items) ?> on this page</span></div>
                    <div class="table-wrap"><table><thead><tr><th>NAME</th><th>STATE</th><th>WEIGHT</th><th>CAPACITY</th><th></th></tr></thead><tbody>
                    <?php foreach ($data['page']->items as $queue): ?>
                        <tr><td><strong><?= h($queue->name) ?></strong></td><td><span class="state"><?= h($queue->state) ?></span></td>
                            <td><?= h($queue->weight) ?></td><td><?= h($queue->concurrency_limit) ?></td>
                            <td><a href="<?= path('queue', ['id' => $queue->id]) ?>">Manage →</a></td></tr>
                    <?php endforeach; ?>
                    </tbody></table></div>
                    <?php if ($data['page']->items === []): ?><p class="empty">No queues on this page.</p><?php endif; ?>
                    <?php if ($data['page']->next_after_id !== null): ?><div class="panel-footer"><a href="<?= path('queues', ['after' => $data['page']->next_after_id]) ?>">Next page →</a></div><?php endif; ?>
                </section>
                <section class="panel form-panel"><div class="panel-head"><div><span class="eyebrow">NEW RESOURCE</span><h2>Create queue</h2></div></div>
                    <?php formStart('queues', 'queue.create', $csrf, ['idempotency_key' => bin2hex(random_bytes(16))]); ?>
                    <div class="fields"><label>Queue name<input name="name" required maxlength="128" placeholder="e.g. reports"></label>
                        <label>Concurrency limit<input name="concurrency_limit" type="number" min="1" placeholder="Default: 1"></label>
                        <label>Weight<input name="weight" type="number" min="1" placeholder="Default: 1"></label>
                        <label>Recovery policy<select name="recovery_policy"><option value="">Default: fail interrupted</option><option value="fail_interrupted">Fail interrupted</option><option value="retry_interrupted">Retry interrupted</option></select></label>
                        <label>Retention (seconds)<input name="history_retention_seconds" type="number" min="0" placeholder="Daemon default"></label>
                        <label class="wide">Defaults (JSON object, optional)<textarea name="defaults_json" rows="2" placeholder='{"job.timeout":120000}'></textarea></label>
                    </div><button type="submit">Create queue →</button></form>
                </section>
                <?php break;

            case 'queue':
                $queue = $data['queue']; ?>
                <div class="section-head"><p class="intro"><?= h($queue->name) ?> <span class="state"><?= h($queue->state) ?></span></p><a class="button secondary" href="<?= path('queues') ?>">← All queues</a></div>
                <div class="facts"><div><span>ID</span><code><?= h($queue->id) ?></code></div><div><span>CAPACITY</span><strong><?= h($queue->concurrency_limit) ?></strong></div><div><span>RECOVERY</span><strong><?= h($queue->recovery_policy) ?></strong></div></div>
                <div class="actions">
                    <?php if ($queue->state === 'active'):
                        formStart('queue', 'queue.suspend', $csrf, ['id' => $queue->id]); ?>
                        <button class="secondary" type="submit">Suspend queue</button></form>
                    <?php elseif ($queue->state === 'suspended'):
                        formStart('queue', 'queue.resume', $csrf, ['id' => $queue->id]); ?>
                        <button class="secondary" type="submit">Resume queue</button></form>
                    <?php endif; ?>
                    <a class="button secondary" href="<?= path('jobs', ['queue_id' => $queue->id]) ?>">View jobs →</a>
                </div>
                <section class="panel form-panel"><div class="panel-head"><div><span class="eyebrow">CONFIGURATION</span><h2>Update queue</h2></div></div>
                    <?php formStart('queue', 'queue.update', $csrf, ['id' => $queue->id]); ?>
                    <p class="muted">Leave fields blank to keep their current values.</p>
                    <div class="fields"><label>Name<input name="name" placeholder="<?= h($queue->name) ?>"></label>
                        <label>Weight<input name="weight" type="number" min="1" placeholder="<?= h($queue->weight) ?>"></label>
                        <label>Capacity<input name="concurrency_limit" type="number" min="1" placeholder="<?= h($queue->concurrency_limit) ?>"></label>
                        <label>Recovery policy<select name="recovery_policy"><option value="">Keep current</option><option value="fail_interrupted">Fail interrupted</option><option value="retry_interrupted">Retry interrupted</option></select></label>
                        <label>Retention (seconds)<input name="history_retention_seconds" type="number" min="0" placeholder="<?= h($queue->history_retention_seconds ?? 'Daemon default') ?>"></label>
                        <label>Wait warning (ms)<input name="runnable_wait_warning_ms" type="number" min="0" placeholder="<?= h($queue->runnable_wait_warning_ms) ?>"></label>
                        <label class="wide">Defaults (replacement JSON object)<textarea name="defaults_json" rows="2" placeholder="Leave blank to keep"></textarea></label>
                        <label class="check wide"><input name="inherit_retention" type="checkbox" value="1"> Use daemon retention default (set null)</label>
                    </div><button type="submit">Save changes →</button></form>
                </section>
                <?php if ($queue->state === 'suspended'): ?>
                    <section class="panel danger"><h2>Delete queue</h2><p>This soft-deletes the queue and its current jobs. Retained history remains.</p>
                        <?php formStart('queue', 'queue.delete', $csrf, ['id' => $queue->id]); ?>
                        <label class="check"><input name="confirm" type="checkbox" value="yes" required> I understand this deletes the queue</label>
                        <button class="danger-button" type="submit">Delete queue</button></form></section>
                <?php endif; ?>
                <?php break;

            case 'jobs': ?>
                <div class="section-head"><p class="intro">Definitions, schedules, and execution templates.</p><a class="button secondary" href="<?= path('queues') ?>">Browse queues →</a></div>
                <section class="panel"><div class="panel-head"><div><span class="eyebrow">DEFINITIONS</span><h2>Jobs</h2></div><span class="count"><?= count($data['page']->items) ?> on this page</span></div>
                    <div class="table-wrap"><table><thead><tr><th>NAME</th><th>TYPE</th><th>STATE</th><th>REVISION</th><th></th></tr></thead><tbody>
                    <?php foreach ($data['page']->items as $job): ?>
                        <tr><td><strong><?= h($job->name ?? 'Untitled') ?></strong><small class="mono"><?= h(substr($job->id, 0, 8)) ?>…</small></td>
                            <td><?= h(strtoupper($job->type)) ?></td><td><span class="state"><?= h($job->state) ?></span></td>
                            <td><?= h($job->revision) ?></td><td><a href="<?= path('job', ['id' => $job->id]) ?>">Manage →</a></td></tr>
                    <?php endforeach; ?>
                    </tbody></table></div>
                    <?php if ($data['page']->items === []): ?><p class="empty">No jobs on this page.</p><?php endif; ?>
                    <?php if ($data['page']->next_after_id !== null): ?><div class="panel-footer"><a href="<?= path('jobs', array_filter(['after' => $data['page']->next_after_id,
                        'queue_id' => $_GET['queue_id'] ?? null])) ?>">Next page →</a></div><?php endif; ?>
                </section>
                <section class="panel form-panel"><div class="panel-head"><div><span class="eyebrow">NEW DEFINITION</span><h2>Create job</h2></div></div>
                    <?php formStart('jobs', 'job.create', $csrf, ['idempotency_key' => bin2hex(random_bytes(16))]); ?>
                    <div class="fields">
                        <label>Queue<select name="queue_id" required>
                            <option value="">Choose a queue</option>
                            <?php foreach ($data['queues'] as $queue): ?>
                                <option value="<?= h($queue->id) ?>" <?= ($_GET['queue_id'] ?? null) === $queue->id ? 'selected' : '' ?>>
                                    <?= h($queue->name) ?> (<?= h($queue->state) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select></label>
                        <label>Name (optional)<input name="name" maxlength="256" placeholder="e.g. morning report"></label>
                        <label>Runner<select name="type"><option value="cli">CLI</option><option value="http">HTTP</option></select></label>
                        <label>Priority<input name="priority" type="number" placeholder="Default: 0"></label>
                        <label class="wide">Schedule (JSON object)<textarea name="schedule_json" rows="3" required spellcheck="false">{"kind":"once","at":"now"}</textarea></label>
                        <label class="wide">Payload (JSON object)<textarea name="payload_json" rows="3" required spellcheck="false">{"command":"/usr/bin/true"}</textarea></label>
                        <label class="wide">Attributes (JSON object, optional)<textarea name="attributes_json" rows="2" spellcheck="false" placeholder='{"job.timeout":120000}'></textarea></label>
                    </div><p class="muted">For HTTP jobs use a payload such as {"url":"https://example.com","method":"GET"}.</p>
                    <?php if ($data['queues'] === []): ?>
                        <p class="muted">Create a queue before adding a job. <a href="<?= path('queues') ?>">Manage queues →</a></p>
                    <?php endif; ?>
                    <button type="submit" <?= $data['queues'] === [] ? 'disabled' : '' ?>>Create job →</button></form>
                </section>
                <?php break;

            case 'job':
                $job = $data['job']; ?>
                <div class="section-head"><p class="intro"><?= h($job->name ?? 'Untitled job') ?> <span class="state"><?= h($job->state) ?></span></p><a class="button secondary" href="<?= path('jobs') ?>">← All jobs</a></div>
                <div class="facts"><div><span>JOB ID</span><code><?= h($job->id) ?></code></div><div><span>REVISION</span><strong><?= h($job->revision) ?></strong></div>
                    <div><span>QUEUE</span><a href="<?= path('queue', ['id' => $job->queue_id]) ?>"><?= h(substr($job->queue_id, 0, 8)) ?>…</a></div>
                    <div><span>SCHEDULE</span><strong><?= h($job->schedule->kind) ?><?= $job->schedule->expression === null ? '' : ' · ' . h($job->schedule->expression) ?></strong></div></div>
                <div class="actions">
                    <?php if ($job->state === 'active'):
                        formStart('job', 'job.suspend', $csrf, ['id' => $job->id]); ?>
                        <button class="secondary" type="submit">Suspend job</button></form>
                    <?php elseif ($job->state === 'suspended'):
                        formStart('job', 'job.resume', $csrf, ['id' => $job->id]); ?>
                        <button class="secondary" type="submit">Resume job</button></form>
                    <?php endif; ?>
                    <?php if (in_array($job->state, ['active', 'suspended'], true)):
                        formStart('job', 'job.run_now', $csrf, ['id' => $job->id, 'idempotency_key' => bin2hex(random_bytes(16))]); ?>
                        <button type="submit">Run now →</button></form>
                    <?php endif; ?>
                </div>
                <?php if (in_array($job->state, ['active', 'suspended'], true)): ?>
                    <section class="panel form-panel"><div class="panel-head"><div><span class="eyebrow">REVISION <?= h($job->revision) ?></span><h2>Update job</h2></div></div>
                        <?php formStart('job', 'job.update', $csrf, ['id' => $job->id, 'expected_revision' => $job->revision]); ?>
                        <p class="muted">Current values are shown below. Only changed fields are submitted; an untouched form does not advance the revision. Attributes are a patch: removing a key here does not remove the stored attribute.</p>
                        <div class="fields">
                            <label>Name (empty clears it)<input name="name" maxlength="256" value="<?= h($job->name ?? '') ?>"></label>
                            <label>Priority<input name="priority" type="number" required value="<?= h($job->priority) ?>"></label>
                            <label>Runner<select name="type"><option value="cli" <?= $job->type === 'cli' ? 'selected' : '' ?>>CLI</option><option value="http" <?= $job->type === 'http' ? 'selected' : '' ?>>HTTP</option></select></label>
                            <label class="wide">Schedule (JSON object)<textarea name="schedule_json" rows="4" required spellcheck="false"><?= h(json_encode(
                                array_filter(get_object_vars($job->schedule), static fn($value) => $value !== null),
                                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
                            )) ?></textarea></label>
                            <label class="wide">Payload (JSON object)<textarea name="payload_json" rows="5" required spellcheck="false"><?= h(json_encode(
                                $job->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
                            )) ?></textarea></label>
                            <label class="wide">Attributes (JSON object)<textarea name="attributes_json" rows="4" required spellcheck="false"><?= h(json_encode(
                                $job->attributes, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
                            )) ?></textarea></label>
                        </div><button type="submit">Save revision →</button></form>
                    </section>
                <?php endif; ?>
                <?php if ($job->state === 'suspended'): ?>
                    <section class="panel form-panel"><h2>Move to another queue</h2>
                        <?php formStart('job', 'job.move', $csrf, ['id' => $job->id, 'expected_revision' => $job->revision]); ?>
                        <div class="fields"><label>Target queue name<input name="target_queue_name" required></label></div>
                        <button class="secondary" type="submit">Move job →</button></form></section>
                <?php endif; ?>
                <section class="panel"><div class="panel-head"><div><span class="eyebrow">HISTORY</span><h2>Recent runs</h2></div><a href="<?= path('runs') ?>">All runs →</a></div>
                    <?php foreach ($data['runs']->items as $run): ?>
                        <a class="list-row" href="<?= path('run', ['id' => $run->id]) ?>"><strong><?= h($run->planned_at) ?></strong><span class="state"><?= h($run->state) ?></span></a>
                    <?php endforeach; ?>
                    <?php if ($data['runs']->items === []): ?><p class="empty">No retained runs for this job.</p><?php endif; ?>
                </section>
                <?php if (in_array($job->state, ['suspended', 'succeeded', 'failed', 'cancelled'], true)): ?>
                    <section class="panel danger"><h2>Delete job</h2><p>This soft-deletes the definition. Retained run history remains.</p>
                        <?php formStart('job', 'job.delete', $csrf, ['id' => $job->id, 'expected_revision' => $job->revision]); ?>
                        <label class="check"><input name="confirm" type="checkbox" value="yes" required> I understand this deletes the job</label>
                        <button class="danger-button" type="submit">Delete job</button></form></section>
                <?php endif; ?>
                <?php break;

            case 'runs': ?>
                <p class="intro">Inspect planned, running, and completed occurrences.</p>
                <section class="panel"><div class="panel-head"><div><span class="eyebrow">HISTORY</span><h2>Runs</h2></div></div>
                    <div class="table-wrap"><table><thead><tr><th>PLANNED AT (UTC)</th><th>ORIGIN</th><th>STATE</th><th>JOB</th><th></th></tr></thead><tbody>
                    <?php foreach ($data['page']->items as $run): ?>
                        <tr><td><?= h($run->planned_at) ?></td><td><?= h($run->origin) ?></td><td><span class="state"><?= h($run->state) ?></span></td>
                            <td><a href="<?= path('job', ['id' => $run->job_id]) ?>"><?= h($data['job_names'][$run->job_id]) ?></a></td>
                            <td><a href="<?= path('run', ['id' => $run->id]) ?>">Inspect →</a></td></tr>
                    <?php endforeach; ?>
                    </tbody></table></div>
                    <?php if ($data['page']->items === []): ?><p class="empty">No retained runs on this page.</p><?php endif; ?>
                    <?php if ($data['page']->next_cursor !== null): ?><div class="panel-footer"><a href="<?= path('runs', ['cursor' => $data['page']->next_cursor]) ?>">Next page →</a></div><?php endif; ?>
                </section>
                <?php break;

            case 'run':
                $run = $data['run']; ?>
                <div class="section-head"><p class="intro"><?= h(substr($run->id, 0, 8)) ?>… <span class="state"><?= h($run->state) ?></span></p><a class="button secondary" href="<?= path('runs') ?>">← All runs</a></div>
                <div class="facts"><div><span>RUN ID</span><code><?= h($run->id) ?></code></div>
                    <div><span>JOB</span><a href="<?= path('job', ['id' => $run->job_id]) ?>"><?= h(substr($run->job_id, 0, 8)) ?>…</a></div>
                    <div><span>ORIGIN</span><strong><?= h($run->origin) ?></strong></div>
                    <div><span>PLANNED</span><strong><?= h($run->planned_at) ?></strong></div></div>
                <?php if (in_array($run->state, ['scheduled', 'running', 'retry_wait'], true)):
                    formStart('run', 'run.cancel', $csrf, ['id' => $run->id]); ?>
                    <button class="secondary" type="submit">Request cancellation</button></form>
                <?php endif; ?>
                <section class="panel"><div class="panel-head"><div><span class="eyebrow">EXECUTION</span><h2>Attempts</h2></div></div>
                    <?php foreach ($data['attempts']->items as $attempt): ?>
                        <a class="list-row" href="<?= path('attempt', ['run_id' => $run->id, 'number' => $attempt->attempt_number]) ?>">
                            <strong>Attempt #<?= h($attempt->attempt_number) ?></strong><span class="state"><?= h($attempt->outcome ?? $attempt->state) ?></span></a>
                    <?php endforeach; ?>
                    <?php if ($data['attempts']->items === []): ?><p class="empty">No attempts recorded yet.</p><?php endif; ?>
                    <?php if ($data['attempts']->next_cursor !== null): ?>
                        <div class="panel-footer"><a href="<?= path('run', ['id' => $run->id,
                            'cursor' => $data['attempts']->next_cursor]) ?>">Next attempts →</a></div>
                    <?php endif; ?>
                </section>
                <?php break;

            case 'attempt':
                $attempt = $data['attempt']; ?>
                <div class="section-head"><p class="intro">Attempt #<?= h($attempt->attempt_number) ?> <span class="state"><?= h($attempt->outcome ?? $attempt->state) ?></span></p>
                    <a class="button secondary" href="<?= path('run', ['id' => $attempt->run_id]) ?>">← Run details</a></div>
                <div class="facts"><div><span>DUE</span><strong><?= h($attempt->due_at) ?></strong></div>
                    <div><span>STARTED</span><strong><?= h($attempt->started_at ?? '—') ?></strong></div>
                    <div><span>COMPLETED</span><strong><?= h($attempt->completed_at ?? '—') ?></strong></div></div>
                <section class="panel"><h2>Captured output</h2><p class="muted">Open a channel to view one retained chunk. Output can contain application secrets.</p>
                    <div class="actions">
                    <?php foreach (['stdout', 'stderr', 'body', 'headers'] as $channel): ?>
                        <a class="button secondary" href="<?= path('output', ['run_id' => $attempt->run_id, 'number' => $attempt->attempt_number, 'channel' => $channel]) ?>"><?= h($channel) ?> →</a>
                    <?php endforeach; ?>
                    </div></section>
                <?php break;

            case 'output':
                $chunk = $data['chunk']; ?>
                <div class="section-head"><p class="intro"><?= h($chunk->channel) ?> · <?= h($chunk->status) ?></p>
                    <a class="button secondary" href="<?= path('attempt', ['run_id' => $chunk->run_id, 'number' => $chunk->attempt_number]) ?>">← Attempt</a></div>
                <section class="alert error"><strong>Potentially sensitive data</strong><p>Captured output may contain secrets. Do not share this page.</p></section>
                <section class="panel"><div class="panel-head"><h2>Retained bytes</h2><span class="count"><?= h($chunk->bytes_returned) ?> bytes</span></div>
                    <?php if ($chunk->encoding === 'utf8'): ?>
                        <pre class="output"><?= h($chunk->data) ?></pre>
                    <?php else: ?>
                        <p class="muted">Binary chunk encoded as base64:</p><pre class="output"><?= h($chunk->data) ?></pre>
                    <?php endif; ?>
                    <?php if ($chunk->next_offset !== null): ?>
                        <div class="panel-footer"><a href="<?= path('output', ['run_id' => $chunk->run_id, 'number' => $chunk->attempt_number,
                            'channel' => $chunk->channel, 'offset' => $chunk->next_offset]) ?>">Next chunk →</a></div>
                    <?php endif; ?>
                </section>
                <?php break;

            case 'secrets': ?>
                <p class="intro">Named secret metadata. Values are never returned by jobud.</p>
                <section class="panel"><div class="panel-head"><div><span class="eyebrow">VAULT</span><h2>Secrets</h2></div></div>
                    <div class="table-wrap"><table><thead><tr><th>NAME</th><th>UPDATED</th><th></th></tr></thead><tbody>
                    <?php foreach ($data['page']->items as $secret): ?>
                        <tr><td><strong><?= h($secret->name) ?></strong></td><td><?= h($secret->updated_at) ?></td>
                            <td><?php formStart('secrets', 'secret.delete', $csrf, ['name' => $secret->name]); ?>
                                <label class="check compact"><input name="confirm" type="checkbox" value="yes" required> Confirm</label>
                                <button type="submit" class="text-danger">Delete</button></form></td></tr>
                    <?php endforeach; ?></tbody></table></div>
                    <?php if ($data['page']->items === []): ?><p class="empty">No secrets stored.</p><?php endif; ?>
                    <?php if ($data['page']->next_after_name !== null): ?>
                        <div class="panel-footer"><a href="<?= path('secrets', ['after' => $data['page']->next_after_name]) ?>">Next page →</a></div>
                    <?php endif; ?>
                </section>
                <section class="panel form-panel"><div class="panel-head"><div><span class="eyebrow">WRITE ONLY</span><h2>Set or rotate</h2></div></div>
                    <?php formStart('secrets', 'secret.set', $csrf); ?>
                    <div class="fields"><label>Canonical name<input name="name" required placeholder="reports.token" autocomplete="off"></label>
                        <label>Encoding<select name="encoding"><option value="utf8">UTF-8 text</option><option value="base64">Base64 bytes</option></select></label>
                        <label class="wide">Value<textarea name="value" rows="3" autocomplete="off" spellcheck="false"></textarea></label>
                    </div><p class="muted">An empty value is allowed. It will not be shown again after submission.</p>
                    <button type="submit">Save secret →</button></form>
                </section>
                <?php break;

            case 'schedules': ?>
                <p class="intro">Validate a cron expression and preview the next five UTC occurrences.</p>
                <section class="panel form-panel"><div class="panel-head"><div><span class="eyebrow">CRON ENGINE</span><h2>Preview schedule</h2></div></div>
                    <form method="get" action="/index.php"><input type="hidden" name="view" value="schedules">
                        <div class="fields"><label>Expression<input name="expression" required value="<?= h(is_string($_GET['expression'] ?? null) ? $_GET['expression'] : '') ?>" placeholder="@daily"></label>
                            <label>Timezone (optional)<input name="timezone" value="<?= h(is_string($_GET['timezone'] ?? null) ? $_GET['timezone'] : '') ?>" placeholder="Daemon default"></label>
                            <label class="wide">After (UTC RFC 3339)<input name="after" value="<?= h(is_string($_GET['after'] ?? null) ? $_GET['after'] : '') ?>" placeholder="Current UTC time"></label>
                        </div><button type="submit">Validate & preview →</button></form>
                </section>
                <?php if (isset($data['occurrences'])): ?>
                    <section class="panel"><h2>Next occurrences</h2>
                        <?php foreach ($data['occurrences']->occurrences as $when): ?>
                            <div class="list-row"><strong><?= h($when) ?></strong><span>UTC</span></div>
                        <?php endforeach; ?></section>
                <?php endif; ?>
                <?php break;
        endswitch; ?>
        <?php endif; ?>
        <footer>JobU console · Data is live and may change between pages. No automatic mutation retries.</footer>
    </main>
</div>
</body>
</html>
