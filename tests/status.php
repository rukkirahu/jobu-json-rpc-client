<?php

declare(strict_types=1);

$socket = sys_get_temp_dir() . '/jobu-status-' . bin2hex(random_bytes(8)) . '.sock';
$listener = stream_socket_server("unix://$socket", $errno, $error);
if ($listener === false) {
    throw new RuntimeException('Cannot create test socket');
}

$pid = pcntl_fork();
if ($pid === -1) {
    fclose($listener);
    unlink($socket);
    throw new RuntimeException('Cannot fork test server');
}

if ($pid === 0) {
    try {
        $stream = stream_socket_accept($listener, 5);
        if ($stream === false) {
            throw new RuntimeException('Cannot accept test connection');
        }
        stream_set_timeout($stream, 5);

        $results = [
            ['daemon_version' => '1.0', 'api_version' => ['major' => 1, 'minor' => 4],
                'capabilities' => ['queue.list', 'system.stats']],
            ['items' => [['name' => '<script>alert(1)</script>', 'state' => 'active']], 'next_after_id' => null],
            ['groups' => [['runs' => ['total' => 2, 'states' => ['running' => 1, 'failed' => 0]],
                'attempts' => ['total' => 3]]]],
        ];
        foreach (['system.info', 'queue.list', 'system.stats'] as $index => $method) {
            $header = fgets($stream);
            if ($header === false ||
                preg_match('/^Content-Length: ([0-9]+)\r\n$/D', $header, $matches) !== 1 ||
                fgets($stream) !== "\r\n") {
                throw new RuntimeException('Invalid request frame');
            }
            $body = '';
            while (strlen($body) < (int) $matches[1]) {
                $part = fread($stream, (int) $matches[1] - strlen($body));
                if ($part === false || $part === '') {
                    throw new RuntimeException('Incomplete request body');
                }
                $body .= $part;
            }
            $request = json_decode($body, false, 64, JSON_THROW_ON_ERROR);
            if ($request->method !== $method) {
                throw new RuntimeException('Wrong status method');
            }
            $response = json_encode([
                'jsonrpc' => '2.0', 'id' => $request->id, 'result' => $results[$index],
            ], JSON_THROW_ON_ERROR);
            fwrite($stream, 'Content-Length: ' . strlen($response) . "\r\n\r\n" . $response);
        }
        fclose($stream);
        fclose($listener);
        exit(0);
    } catch (Throwable $e) {
        fwrite(STDERR, "Status server: $e\n");
        exit(1);
    }
}

fclose($listener);
$previousSocket = getenv('JOBU_SOCKET_PATH');
putenv("JOBU_SOCKET_PATH=$socket");
try {
    ob_start();
    try {
        require __DIR__ . '/../public/status.php';
        $html = (string) ob_get_contents();
    } finally {
        ob_end_clean();
    }
} finally {
    if ($previousSocket === false) {
        putenv('JOBU_SOCKET_PATH');
    } else {
        putenv("JOBU_SOCKET_PATH=$previousSocket");
    }
    pcntl_waitpid($pid, $status);
    unlink($socket);
}

if (!pcntl_wifexited($status) || pcntl_wexitstatus($status) !== 0 ||
    !str_contains($html, 'Connected') ||
    !str_contains($html, '&lt;script&gt;alert(1)&lt;/script&gt;') ||
    str_contains($html, '<script>') ||
    !str_contains($html, 'Retained history')) {
    throw new RuntimeException('Status page did not render the safe daemon state');
}

echo "Status page test passed\n";
