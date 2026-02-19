<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controlador;
use PDO;

class ContaReceberController extends Controlador
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
        $this->validarPermissao('financeiro');
        $titulos = $this->banco->query('SELECT cr.*, c.nome_razao_social AS cliente_nome FROM contas_receber cr LEFT JOIN clientes c ON c.id = cr.cliente_id ORDER BY cr.id DESC')->fetchAll();
        $this->renderizar('contas_receber/index', compact('titulos'));
    }

    public function baixar(int $id): void
    {
        $this->exigirAutenticacao();
        $this->validarPermissao('financeiro');
        $this->banco->prepare("UPDATE contas_receber SET status='baixado', data_pagamento=:data_pagamento WHERE id=:id")->execute(['id' => $id, 'data_pagamento' => date('Y-m-d')]);
        $this->redirecionar('/contas-receber');
    }

    public function reabrir(int $id): void
    {
        $this->exigirAutenticacao();
        $this->validarPermissao('financeiro');
        $this->banco->prepare("UPDATE contas_receber SET status='aberto', data_pagamento=NULL WHERE id=:id")->execute(['id' => $id]);
        $this->redirecionar('/contas-receber');
    }
}
