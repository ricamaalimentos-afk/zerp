<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controlador;
use PDO;

class DashboardController extends Controlador
{
    private PDO $banco;

    public function __construct(array $config, PDO $banco)
    {
        parent::__construct($config);
        $this->banco = $banco;
    }

    public function index(): void
    {
        $this->exigirAutenticacao();
        $totais = [
            'clientes' => (int) $this->banco->query('SELECT COUNT(*) FROM clientes')->fetchColumn(),
            'produtos' => (int) $this->banco->query('SELECT COUNT(*) FROM produtos')->fetchColumn(),
            'vendas' => (int) $this->banco->query("SELECT COUNT(*) FROM vendas WHERE status = 'finalizado'")->fetchColumn(),
            'receber_aberto' => (float) $this->banco->query("SELECT IFNULL(SUM(valor),0) FROM contas_receber WHERE status = 'aberto'")->fetchColumn(),
        ];
        $this->renderizar('dashboard/index', compact('totais'));
    }
}
