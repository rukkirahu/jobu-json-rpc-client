<?php

declare(strict_types=1);

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'action' => 'queue.delete',
    'id' => '00112233-4455-6677-8899-aabbccddeeff',
    'confirm' => 'yes',
    'csrf' => 'incorrect',
];
$_GET = ['view' => 'queues'];
putenv('JOBU_SOCKET_PATH=/nonexistent-jobud.sock');

ob_start();
try {
    require __DIR__ . '/../public/index.php';
    $html = (string) ob_get_contents();
} finally {
    ob_end_clean();
}

if (http_response_code() !== 403 || !str_contains($html, 'Session token expired')) {
    throw new RuntimeException('CSRF rejection failed');
}

echo "UI CSRF test passed\n";
