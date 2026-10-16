<?php

declare(strict_types=1);

use Jobu\Client;

require_once __DIR__ . '/../src/Exception/TransportException.php';
require_once __DIR__ . '/../src/Exception/ProtocolException.php';
require_once __DIR__ . '/../src/Exception/RpcException.php';
require_once __DIR__ . '/../src/Client.php';

function escape(string|int $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

header('Cache-Control: no-store');

$socket = getenv('JOBU_SOCKET_PATH') ?: '/opt/jobu/var/run/jobud.sock';
$client = null;
$info = null;
$queues = null;
$stats = null;
$error = false;

try {
    $client = new Client($socket, 2.0);
    $info = $client->info();

    if (in_array('queue.list', $info->capabilities, true)) {
        $queues = $client->call('queue.list', ['limit' => 20]);
    }
    if (in_array('system.stats', $info->capabilities, true)) {
        $stats = $client->call('system.stats');
    }
} catch (Throwable $e) {
    $error = true;
    http_response_code(503);
} finally {
    $client?->close();
}

$summary = $stats->groups[0] ?? null;
?>
<!doctype html>
<html lang="en">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>JobU daemon status</title>
<style>
    body { max-width: 760px; margin: 6vh auto; padding: 0 24px; background: #f5f2e9; color: #263932; font: 17px/1.55 Georgia, serif; }
    h1, h2 { line-height: 1.1; }
    h1 { font-size: clamp(2.5rem, 7vw, 4rem); margin-bottom: .4em; }
    h2 { margin-top: 2em; }
    .eyebrow, th, .badge, small { font: 12px/1.5 ui-monospace, monospace; letter-spacing: .08em; text-transform: uppercase; }
    .eyebrow { color: #547767; }
    .badge { display: inline-block; padding: .35em .8em; background: #dce9d7; color: #244c32; }
    .error { background: #f3ddd5; color: #833d28; }
    .metrics { display: flex; flex-wrap: wrap; gap: 1.5rem 3rem; padding: 1rem 0; border-block: 1px solid #aab8a9; }
    .metrics strong { display: block; font-size: 2rem; line-height: 1.3; }
    table { width: 100%; border-collapse: collapse; text-align: left; }
    td, th { padding: .7em .4em; border-bottom: 1px solid #c7cec3; }
    td:last-child, th:last-child { text-align: right; }
    small { display: block; margin-top: 1.2rem; color: #547767; }
</style>
<main>
    <div class="eyebrow">JobU / live check</div>
    <h1>Daemon status</h1>
    <?php if ($error): ?>
        <p class="badge error">Unavailable</p>
        <p>Unable to read JobU state. Check that PHP-FPM can access the daemon socket
            and that <code>JOBU_SOCKET_PATH</code> points to it.</p>
    <?php else: ?>
        <p class="badge">Connected</p>
        <p>Daemon <?= escape($info->daemon_version ?? 'unknown') ?> · API
            <?= escape($info->api_version->major) ?>.<?= escape($info->api_version->minor ?? 0) ?></p>

        <?php if ($stats !== null): ?>
            <h2>Retained history · last 24 hours</h2>
            <div class="metrics">
                <div><strong><?= escape($summary->runs->total ?? 0) ?></strong>runs</div>
                <div><strong><?= escape($summary->runs->states->running ?? 0) ?></strong>running</div>
                <div><strong><?= escape($summary->runs->states->failed ?? 0) ?></strong>failed</div>
                <div><strong><?= escape($summary->attempts->total ?? 0) ?></strong>attempts</div>
            </div>
            <small>Counts cover retained runs planned in the daemon's rolling 24-hour window, not all-time totals.</small>
        <?php endif; ?>

        <?php if ($queues !== null): ?>
            <h2>Queues</h2>
            <?php if ($queues->items === []): ?>
                <p>No active queues found.</p>
            <?php else: ?>
                <table>
                    <thead><tr><th>Name</th><th>State</th></tr></thead>
                    <tbody>
                    <?php foreach ($queues->items as $queue): ?>
                        <tr><td><?= escape($queue->name) ?></td><td><?= escape($queue->state) ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if ($queues->next_after_id !== null): ?>
                    <small>Showing the first 20 queues; more are available.</small>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>
    <small>Checked <?= escape(gmdate('Y-m-d H:i:s')) ?> UTC · read-only</small>
</main>
</html>
