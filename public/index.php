<?php

require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/../src/Core/Http.php';
require_once __DIR__ . '/../src/Controllers/ApiController.php';

use App\Controllers\ApiController;

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    (new ApiController())->handle($method, $path);
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $e->getMessage()]);
}
