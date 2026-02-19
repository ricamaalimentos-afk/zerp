<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';

function tentarLogin(string $email, string $senha): bool
{
    $pdo = obterConexao();
    $sql = 'SELECT id, codigo, nome, apelido, email, senha, nivel, permissoes, ativo FROM usuarios WHERE email = :email LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['email' => $email]);
    $usuario = $stmt->fetch();

    if (!$usuario || (int) $usuario['ativo'] !== 1) {
        return false;
    }

    if (!password_verify($senha, $usuario['senha'])) {
        return false;
    }

    unset($usuario['senha']);
    $_SESSION['usuario'] = $usuario;
    return true;
}

function exigirLogin(): void
{
    if (!usuarioLogado()) {
        redirecionar('login');
    }
}
