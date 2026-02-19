<?php

declare(strict_types=1);

namespace App\Core;

class Controlador
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    protected function renderizar(string $view, array $dados = []): void
    {
        extract($dados);
        $config = $this->config;
        require __DIR__ . '/../views/layout/header.php';
        require __DIR__ . '/../views/' . $view . '.php';
        require __DIR__ . '/../views/layout/footer.php';
    }

    protected function redirecionar(string $caminho): void
    {
        header('Location: ' . $this->config['app']['url_base'] . $caminho);
        exit;
    }

    protected function exigirAutenticacao(): void
    {
        if (empty($_SESSION['usuario'])) {
            $this->redirecionar('/login');
        }
    }

    protected function validarPermissao(string $permissao): void
    {
        $usuario = $_SESSION['usuario'] ?? [];
        if (($usuario['nivel'] ?? '') === 'admin') {
            return;
        }

        $permissoes = array_filter(explode(',', $usuario['permissoes_menu'] ?? ''));
        if (!in_array($permissao, $permissoes, true)) {
            http_response_code(403);
            die('Acesso negado.');
        }
    }
}
