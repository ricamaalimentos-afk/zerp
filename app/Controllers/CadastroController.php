<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controlador;
use App\Models\ModeloBase;
use PDO;

class CadastroController extends Controlador
{
    private PDO $banco;
    private array $modulos = [
        'usuarios' => ['tabela' => 'usuarios', 'titulo' => 'Usuários', 'view' => 'usuarios/index', 'permissao' => 'usuarios'],
        'clientes' => ['tabela' => 'clientes', 'titulo' => 'Clientes', 'view' => 'clientes/index', 'permissao' => 'clientes'],
        'grupos-clientes' => ['tabela' => 'grupos_clientes', 'titulo' => 'Grupos de Clientes', 'view' => 'grupos_clientes/index', 'permissao' => 'cadastros'],
        'formas-pagamento' => ['tabela' => 'formas_pagamento', 'titulo' => 'Formas de Pagamento', 'view' => 'formas_pagamento/index', 'permissao' => 'cadastros'],
        'tabelas-preco' => ['tabela' => 'tabelas_preco', 'titulo' => 'Tabelas de Preço', 'view' => 'tabelas_preco/index', 'permissao' => 'cadastros'],
        'produtos' => ['tabela' => 'produtos', 'titulo' => 'Produtos', 'view' => 'produtos/index', 'permissao' => 'produtos'],
        'configuracoes' => ['tabela' => 'configuracoes', 'titulo' => 'Configurações Gerais', 'view' => 'configuracoes/index', 'permissao' => 'configuracoes'],
    ];

    public function __construct(array $config, PDO $banco)
    {
        parent::__construct($config);
        $this->banco = $banco;
    }

    public function index(string $modulo): void
    {
        $this->exigirAutenticacao();
        $cfg = $this->modulos[$modulo] ?? null;
        if (!$cfg) {
            http_response_code(404);
            die('Módulo não encontrado.');
        }
        $this->validarPermissao($cfg['permissao']);
        $modelo = new ModeloBase($this->banco, $cfg['tabela']);
        $registros = $modelo->listarTodos();
        $this->renderizar($cfg['view'], ['registros' => $registros, 'modulo' => $modulo, 'titulo' => $cfg['titulo']]);
    }

    public function salvar(string $modulo): void
    {
        $this->exigirAutenticacao();
        $cfg = $this->modulos[$modulo] ?? null;
        if (!$cfg) {
            http_response_code(404);
            die('Módulo não encontrado.');
        }
        $this->validarPermissao($cfg['permissao']);
        $dados = $_POST;
        unset($dados['id']);

        if ($cfg['tabela'] === 'usuarios' && !empty($dados['senha'])) {
            $dados['senha'] = password_hash($dados['senha'], PASSWORD_DEFAULT);
        }

        if ($cfg['tabela'] === 'configuracoes') {
            $modelo = new ModeloBase($this->banco, $cfg['tabela']);
            $registro = $modelo->listarTodos('id ASC')[0] ?? null;
            if ($registro) {
                $modelo->atualizar((int) $registro['id'], $dados);
                $this->redirecionar('/cadastros/' . $modulo);
                return;
            }
        }

        $modelo = new ModeloBase($this->banco, $cfg['tabela']);
        $modelo->inserir($dados);
        $this->redirecionar('/cadastros/' . $modulo);
    }

    public function excluir(string $modulo, int $id): void
    {
        $this->exigirAutenticacao();
        $cfg = $this->modulos[$modulo] ?? null;
        if (!$cfg) {
            http_response_code(404);
            die('Módulo não encontrado.');
        }
        $this->validarPermissao($cfg['permissao']);
        $modelo = new ModeloBase($this->banco, $cfg['tabela']);
        $modelo->excluir($id);
        $this->redirecionar('/cadastros/' . $modulo);
    }
}
