<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controlador;
use PDO;

class AuthController extends Controlador
{
    private PDO $banco;

    public function __construct(array $config, PDO $banco)
    {
        parent::__construct($config);
        $this->banco = $banco;
    }

    public function loginFormulario(): void
    {
        require __DIR__ . '/../views/auth/login.php';
    }

    public function loginSalvar(): void
    {
        $email = $_POST['email'] ?? '';
        $senha = $_POST['senha'] ?? '';
        $stmt = $this->banco->prepare('SELECT * FROM usuarios WHERE email = :email AND ativo = 1 LIMIT 1');
        $stmt->execute(['email' => $email]);
        $usuario = $stmt->fetch();

        if (!$usuario || !password_verify($senha, $usuario['senha'])) {
            $_SESSION['erro_login'] = 'Usuário ou senha inválidos.';
            header('Location: ' . $this->config['app']['url_base'] . '/login');
            return;
        }

        $_SESSION['usuario'] = [
            'id' => $usuario['id'],
            'nome' => $usuario['nome'],
            'email' => $usuario['email'],
            'nivel' => $usuario['nivel'],
            'permissoes_menu' => $usuario['permissoes_menu'],
        ];

        $this->redirecionar('/');
    }

    public function sair(): void
    {
        session_destroy();
        header('Location: ' . $this->config['app']['url_base'] . '/login');
    }
}
