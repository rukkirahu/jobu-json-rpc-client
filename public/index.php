<?php

declare(strict_types=1);

use Jobu\Client;
use Jobu\Exception\ProtocolException;
use Jobu\Exception\RpcException;
use Jobu\Exception\TransportException;
use Jobu\Web\Console;

require __DIR__ . '/../vendor/autoload.php';

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
header('Referrer-Policy: same-origin');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'self'; style-src 'self'; img-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");

session_name('jobu_console');
session_set_cookie_params([
    'httponly' => true,
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Strict',
]);
session_start();
if (!isset($_SESSION['csrf']) || !is_string($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$view = $_GET['view'] ?? 'dashboard';
$view = is_string($view) ? $view : 'dashboard';
$error = null;
$info = null;
$data = [];
$client = null;

try {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (!in_array($method, ['GET', 'POST'], true)) {
        http_response_code(405);
        throw new InvalidArgumentException('Unsupported HTTP method');
    }
    $client = new Client(getenv('JOBU_SOCKET_PATH') ?: '/opt/jobu/var/run/jobud.sock', 2.0);
    $console = new Console($client);

    if ($method === 'POST') {
        $token = $_POST['csrf'] ?? null;
        if (!is_string($token) || !hash_equals($_SESSION['csrf'], $token)) {
            http_response_code(403);
            throw new InvalidArgumentException('Session token expired. Reload the page and try again.');
        }
        $action = $_POST['action'] ?? null;
        if (!is_string($action)) {
            throw new InvalidArgumentException('Unknown action');
        }
        $redirect = $console->mutate($action, $_POST);
        header('Location: ' . $redirect, true, 303);
        exit;
    }

    $page = $console->page($view, $_GET);
    $info = $page['info'];
    $data = $page['data'];
} catch (RpcException $e) {
    http_response_code(400);
    $error = $e->getMessage() . ($e->operationCode() === null ? '' : ' (' . $e->operationCode() . ')');
} catch (TransportException $e) {
    http_response_code(503);
    $error = 'Cannot reach jobud. If you submitted a change, its outcome may be unknown; inspect the resource before retrying.';
} catch (ProtocolException $e) {
    http_response_code(503);
    $error = 'JobU returned an unexpected response. Check the daemon version and health.';
} catch (InvalidArgumentException | JsonException $e) {
    if (http_response_code() === 200) {
        http_response_code(400);
    }
    $error = $e->getMessage();
} catch (Throwable $e) {
    http_response_code(503);
    $error = 'The console could not load this page. Check the daemon socket and PHP logs.';
} finally {
    $client?->close();
}

$csrf = $_SESSION['csrf'];
require __DIR__ . '/../src/Web/template.php';
