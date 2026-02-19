<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class ModeloBase
{
    protected PDO $banco;
    protected string $tabela;

    public function __construct(PDO $banco, string $tabela)
    {
        $this->banco = $banco;
        $this->tabela = $tabela;
    }

    public function listarTodos(string $ordem = 'id DESC'): array
    {
        return $this->banco->query("SELECT * FROM {$this->tabela} ORDER BY {$ordem}")->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->banco->prepare("SELECT * FROM {$this->tabela} WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $linha = $stmt->fetch();
        return $linha ?: null;
    }

    public function inserir(array $dados): int
    {
        $colunas = array_keys($dados);
        $campos = implode(',', $colunas);
        $marcadores = ':' . implode(',:', $colunas);
        $sql = "INSERT INTO {$this->tabela} ({$campos}) VALUES ({$marcadores})";
        $stmt = $this->banco->prepare($sql);
        $stmt->execute($dados);
        return (int) $this->banco->lastInsertId();
    }

    public function atualizar(int $id, array $dados): bool
    {
        $sets = [];
        foreach (array_keys($dados) as $coluna) {
            $sets[] = "{$coluna} = :{$coluna}";
        }
        $sql = "UPDATE {$this->tabela} SET " . implode(',', $sets) . ' WHERE id = :id';
        $dados['id'] = $id;
        $stmt = $this->banco->prepare($sql);
        return $stmt->execute($dados);
    }

    public function excluir(int $id): bool
    {
        $stmt = $this->banco->prepare("DELETE FROM {$this->tabela} WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
