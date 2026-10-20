# JobU PHP client

Synchronous PHP 8.5+ client for the
[JobU JSON-RPC protocol](https://github.com/evaikene/deferra/tree/02e374408bd58f74abe6e54416a846c5b6de62dc/docs/protocol).
This client targets the protocol documented at Deferra commit
`02e374408bd58f74abe6e54416a846c5b6de62dc` (API 1.6).
Requires the PHP JSON extension and access to the daemon's Unix socket.

Install from [Packagist](https://packagist.org/packages/jobu/json-rpc-client)
in another PHP project:

```sh
composer require jobu/json-rpc-client:^1.0
```

Composer package versions come from Git tags, not the daemon's API version.
Latest library version: `1.2.0`. See [CHANGELOG.md](CHANGELOG.md) for changes.

Use `composer install` to install dependencies in this checkout. The consuming
project must run PHP 8.5+ with `ext-json` enabled. Then:

```php
<?php
require 'vendor/autoload.php';

use Jobu\Client;
use Jobu\Exception\RpcException;

$client = new Client('/path/to/jobud.sock');
$info = $client->info(); // Rejects incompatible API major versions.

if (in_array('queue.list', $info->capabilities, true)) {
    $page = $client->call('queue.list', ['limit' => 20]);
    foreach ($page->items as $queue) {
        echo $queue->name, PHP_EOL;
    }
}

try {
    $queue = $client->call('queue.create', [
        'name' => 'reports',
        'idempotency_key' => 'reports-create-1',
    ]);
} catch (RpcException $e) {
    // Safe JobU operation errors expose a category and stable operation code.
    echo $e->getMessage(), ' ', $e->operationCode(), PHP_EOL;
}

$client->close();
```

The grouped API covers all 30 documented methods with immutable request and
result DTOs. For example:

```php
use Jobu\Dto\Request\JobCreate;
use Jobu\Dto\Request\JobUpdate;
use Jobu\Dto\Request\Payload;
use Jobu\Dto\Request\QueueList;
use Jobu\Dto\Request\QueueGet;
use Jobu\Dto\Request\QueueUpdate;
use Jobu\Dto\Request\Schedule;

$page = $client->queues()->list(QueueList::from(['limit' => 20]));
foreach ($page->items as $queue) {
    echo $queue->name, ': ', $queue->state, PHP_EOL;
}
$nextPage = $page->next_after_id === null
    ? null
    : $client->queues()->list(QueueList::from(['limit' => 20, 'after_id' => $page->next_after_id]));

if ($page->items !== []) {
    $current = $client->queues()->get(QueueGet::byId($page->items[0]->id));
    $current = $client->queues()->update(QueueUpdate::from([
        'queue_id' => $current->id,
        'expected_revision' => $current->revision,
        'concurrency_limit' => 2,
    ]));
}

$job = $client->jobs()->create(JobCreate::from([
    'queue_name' => 'reports',
    'schedule' => Schedule::once('now'),
    'payload' => Payload::cli('/usr/bin/true'),
    'idempotency_key' => 'submit-42',
]));
$job = $client->jobs()->update(JobUpdate::from([
    'job_id' => $job->id,
    'expected_revision' => $job->revision,
    'name' => null,
]));
```

Passing `null` for `job.update.name` clears the name; omitting the field leaves
it unchanged. Optional request fields are never serialized unless supplied.
Cursor-based continuations for runs, attempts, and statistics must contain
only `cursor`; the library returns one page per call rather than fetching all
pages. Use `info()` to check API major version and `supports()` for method
availability before calling methods on older daemons. Typed queue calls that
return queue objects, including `queue.update`, require API 1.6 and issue
`system.info` before sending the operation; `run.list.states` similarly checks
for API 1.5. `queue.delete` and `queue.stats` retain older compatibility.
Results keep unknown additive fields harmlessly ignored and enum values as
strings; extensible payload templates and attributes remain JSON objects.

API 1.6 queue results include a required, exact integer `revision`. Read the
current queue by UUID before updating: keyed create replay returns its original
revision-1 snapshot, not the live token. Updates require `expected_revision`
from that read and at least one setting; a stale token yields
`jobu.queue.revision_conflict`. Re-read and reconcile changes rather than
automatically retrying. `QueueGet::byId($id)->with(include_deleted: true)`
permits a retained deleted queue; a historical name may be ambiguous. Queue
state transitions can also advance revisions, even when no configuration
setting changes. The daemon requires storage format 5 for API 1.6 and does not
automatically migrate older databases.

`RunList::from(['states' => ['failed', 'interrupted']])` selects either state
on API 1.5+ daemons. This typed call checks `system.info` before sending the
new field and throws `UnsupportedFeatureException` for older daemons; scalar
`state`, unfiltered, and cursor-only queries retain their previous behavior.
Raw `call()` does not perform feature checks. New HTTP job payloads accept
GET, POST, PUT, PATCH, DELETE, or HEAD
(case-insensitively); JobU stores uppercase methods, defaults omissions to
GET, and rejects a body with HEAD. JobU applies these checks to newly admitted
payloads, not unrelated edits to existing jobs.

When Deferra updates its protocol, compare the pinned commit with upstream
`docs/protocol`, update typed request/response DTOs and version-sensitive
calls as needed, add socket-level regression tests, and then advance this
commit reference. Additive protocol fields do not automatically appear in
typed requests; `call()` remains available for newer methods and fields.

`call($method, $params)` works with every documented method; pass associative
arrays or `stdClass` for object params. The daemon validates method-specific
fields. Responses decode as `stdClass`, arrays, scalars, or `null`. `info()` and
`supports($method)` use `system.info` to discover available capabilities.

The client keeps one socket open for sequential requests and reconnects on
the next explicit call after a transport or protocol failure. It does **not**
retry calls automatically: a lost reply to a mutation can mean it committed.
Use the protocol's idempotency or reconciliation guidance before retrying.
`RpcException` represents server errors; `TransportException` and
`ProtocolException` distinguish connection failures from invalid responses.
The default I/O timeout is five seconds; pass a positive float as the second
constructor argument to change it. Default framing limits are 16 KiB headers
and 1 MiB bodies. Concurrent in-flight requests, notifications and batches
are not supported by this synchronous client.

Run the socket-level tests with `composer test` (requires the PHP `pcntl`
extension), or run an individual test under `tests/` without installing dependencies.

The optional test/operator web UI lives in [`ui/`](ui/README.md); it is
excluded from Git archive (Composer dist) packages. Composer source installs
still clone the full repository.

Licensed under the [MIT License](LICENSE).
