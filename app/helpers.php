<?php

declare(strict_types=1);

function redirecionar(string $rota): void
{
    header('Location: index.php?rota=' . urlencode($rota));
    exit;
}

function moedaBr(float $valor): string
{
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

function dataBr(?string $data): string
{
    if (!$data) {
        return '';
    }

    $timestamp = strtotime($data);
    return $timestamp ? date('d/m/Y', $timestamp) : '';
}

function sanitizar(string $valor): string
{
    return htmlspecialchars(trim($valor), ENT_QUOTES, 'UTF-8');
}

function usuarioLogado(): ?array
{
    return $_SESSION['usuario'] ?? null;
}

function usuarioEhAdmin(): bool
{
    $usuario = usuarioLogado();
    return isset($usuario['nivel']) && $usuario['nivel'] === 'ADMIN';
}

function menuPermitido(string $menu): bool
{
    if (usuarioEhAdmin()) {
        return true;
    }

    $usuario = usuarioLogado();
    if (!$usuario || empty($usuario['permissoes'])) {
        return false;
    }

    $permissoes = json_decode((string) $usuario['permissoes'], true);
    return is_array($permissoes) && in_array($menu, $permissoes, true);
}
