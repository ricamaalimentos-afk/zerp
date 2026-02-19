<?php

namespace App\Core;

final class Http
{
    public static function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public static function body(): array
    {
        $raw = file_get_contents('php://input') ?: '{}';
        return json_decode($raw, true) ?? [];
    }

    public static function user(): string
    {
        return $_SERVER['HTTP_X_USER'] ?? '';
    }

    public static function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
