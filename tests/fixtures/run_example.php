<?php

namespace tests\fixtures;

require __DIR__ . '/DebugStream.php';
require __DIR__ . '/../bootstrap.php';

$scenario = json_decode($argv[2], true, 512, JSON_THROW_ON_ERROR);
Transport::$enabled = true;
Transport::$output = $scenario['body'] ?? false;
Transport::$info = ['http_code' => $scenario['http_code'] ?? 0];
$_GET = $scenario['get'] ?? [];
$_REQUEST = $scenario['request'] ?? [];

ob_start();
register_shutdown_function(static function (): void {
    $output = ob_get_clean();
    echo json_encode([
        'status' => http_response_code() ?: 200,
        'output' => $output,
    ], JSON_THROW_ON_ERROR);
});

require __DIR__ . '/../../examples/' . basename($argv[1]);
