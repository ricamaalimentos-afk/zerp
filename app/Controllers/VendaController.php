<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controlador;
use PDO;

class VendaController extends Controlador
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
        $this->validarPermissao('vendas');
        $vendas = $this->banco->query('SELECT v.*, c.nome_razao_social AS cliente_nome, u.nome AS vendedor_nome FROM vendas v LEFT JOIN clientes c ON c.id=v.cliente_id LEFT JOIN usuarios u ON u.id=v.vendedor_id ORDER BY v.id DESC')->fetchAll();
        $clientes = $this->banco->query('SELECT id,nome_razao_social FROM clientes WHERE ativo=1 ORDER BY nome_razao_social')->fetchAll();
        $usuarios = $this->banco->query('SELECT id,nome FROM usuarios WHERE ativo=1 ORDER BY nome')->fetchAll();
        $this->renderizar('vendas/index', compact('vendas', 'clientes', 'usuarios'));
    }

    public function salvar(): void
    {
        $this->exigirAutenticacao();
        $this->validarPermissao('vendas');
        $dados = [
            'cliente_id' => (int)$_POST['cliente_id'],
            'vendedor_id' => (int)($_POST['vendedor_id'] ?: $_SESSION['usuario']['id']),
            'data_venda' => date('Y-m-d'),
            'valor_produtos' => (float)$_POST['valor_produtos'],
            'frete' => (float)$_POST['frete'],
            'icms_st' => (float)$_POST['icms_st'],
            'desconto' => (float)$_POST['desconto'],
            'observacoes' => $_POST['observacoes'] ?? '',
            'status' => 'aberto',
        ];
        $dados['valor_total'] = $dados['valor_produtos'] + $dados['frete'] + $dados['icms_st'] - $dados['desconto'];

        $stmt = $this->banco->prepare('INSERT INTO vendas (cliente_id,vendedor_id,data_venda,valor_produtos,frete,icms_st,desconto,valor_total,observacoes,status) VALUES (:cliente_id,:vendedor_id,:data_venda,:valor_produtos,:frete,:icms_st,:desconto,:valor_total,:observacoes,:status)');
        $stmt->execute($dados);
        $this->redirecionar('/vendas');
    }

    public function finalizar(int $id): void
    {
        $this->exigirAutenticacao();
        $this->validarPermissao('vendas');
        $this->banco->prepare("UPDATE vendas SET status='finalizado' WHERE id=:id")->execute(['id' => $id]);

        $venda = $this->banco->prepare('SELECT * FROM vendas WHERE id=:id');
        $venda->execute(['id' => $id]);
        $dadosVenda = $venda->fetch();

        if ($dadosVenda) {
            $this->banco->prepare('INSERT INTO contas_receber (venda_id,cliente_id,data_emissao,data_vencimento,valor,status,observacoes) VALUES (:venda_id,:cliente_id,:data_emissao,:data_vencimento,:valor,:status,:observacoes)')->execute([
                'venda_id' => $id,
                'cliente_id' => $dadosVenda['cliente_id'],
                'data_emissao' => date('Y-m-d'),
                'data_vencimento' => date('Y-m-d', strtotime('+30 days')),
                'valor' => $dadosVenda['valor_total'],
                'status' => 'aberto',
                'observacoes' => 'Gerado automaticamente ao finalizar a venda #' . $id,
            ]);
        }

        $this->redirecionar('/vendas');
    }
}
