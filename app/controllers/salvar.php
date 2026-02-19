<?php

declare(strict_types=1);

$acao = $_POST['acao'] ?? '';

if ($acao === 'login') {
    $email = sanitizar($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (tentarLogin($email, $senha)) {
        redirecionar('dashboard');
    }

    $_SESSION['erro'] = 'E-mail ou senha inválidos.';
    redirecionar('login');
}

exigirLogin();
$pdo = obterConexao();

if ($acao === 'salvar_usuario') {
    $sql = 'INSERT INTO usuarios (codigo, nome, apelido, cep, endereco, bairro, cidade, uf, fone, whatsapp, email, senha, comissao, limite_desconto, nivel, permissoes, ativo)
            VALUES (:codigo, :nome, :apelido, :cep, :endereco, :bairro, :cidade, :uf, :fone, :whatsapp, :email, :senha, :comissao, :limite_desconto, :nivel, :permissoes, :ativo)';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'codigo' => sanitizar($_POST['codigo'] ?? ''),
        'nome' => mb_strtoupper(sanitizar($_POST['nome'] ?? '')),
        'apelido' => mb_strtoupper(sanitizar($_POST['apelido'] ?? '')),
        'cep' => sanitizar($_POST['cep'] ?? ''),
        'endereco' => mb_strtoupper(sanitizar($_POST['endereco'] ?? '')),
        'bairro' => mb_strtoupper(sanitizar($_POST['bairro'] ?? '')),
        'cidade' => mb_strtoupper(sanitizar($_POST['cidade'] ?? '')),
        'uf' => mb_strtoupper(sanitizar($_POST['uf'] ?? '')),
        'fone' => sanitizar($_POST['fone'] ?? ''),
        'whatsapp' => sanitizar($_POST['whatsapp'] ?? ''),
        'email' => sanitizar($_POST['email'] ?? ''),
        'senha' => password_hash($_POST['senha'] ?? '123456', PASSWORD_DEFAULT),
        'comissao' => (float) ($_POST['comissao'] ?? 0),
        'limite_desconto' => (float) ($_POST['limite_desconto'] ?? 0),
        'nivel' => sanitizar($_POST['nivel'] ?? 'VENDEDOR'),
        'permissoes' => json_encode($_POST['permissoes'] ?? []),
        'ativo' => isset($_POST['ativo']) ? 1 : 0,
    ]);

    redirecionar('usuarios');
}

if ($acao === 'salvar_cliente') {
    $sql = 'INSERT INTO clientes (nome_razao, apelido_empresa, contato, cpf_cnpj, cep, endereco, bairro, cidade, uf, cod_municipio_ibge, data_nascimento, telefone, whatsapp, email, codigo_pais, pais, limite_credito, tabela_preco_id, grupo_cliente_id, observacoes, ativo)
            VALUES (:nome_razao, :apelido_empresa, :contato, :cpf_cnpj, :cep, :endereco, :bairro, :cidade, :uf, :cod_municipio_ibge, :data_nascimento, :telefone, :whatsapp, :email, :codigo_pais, :pais, :limite_credito, :tabela_preco_id, :grupo_cliente_id, :observacoes, 1)';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'nome_razao' => mb_strtoupper(sanitizar($_POST['nome_razao'] ?? '')),
        'apelido_empresa' => mb_strtoupper(sanitizar($_POST['apelido_empresa'] ?? '')),
        'contato' => mb_strtoupper(sanitizar($_POST['contato'] ?? '')),
        'cpf_cnpj' => sanitizar($_POST['cpf_cnpj'] ?? ''),
        'cep' => sanitizar($_POST['cep'] ?? ''),
        'endereco' => mb_strtoupper(sanitizar($_POST['endereco'] ?? '')),
        'bairro' => mb_strtoupper(sanitizar($_POST['bairro'] ?? '')),
        'cidade' => mb_strtoupper(sanitizar($_POST['cidade'] ?? '')),
        'uf' => mb_strtoupper(sanitizar($_POST['uf'] ?? '')),
        'cod_municipio_ibge' => sanitizar($_POST['cod_municipio_ibge'] ?? ''),
        'data_nascimento' => !empty($_POST['data_nascimento']) ? date('Y-m-d', strtotime(str_replace('/', '-', $_POST['data_nascimento']))) : null,
        'telefone' => sanitizar($_POST['telefone'] ?? ''),
        'whatsapp' => sanitizar($_POST['whatsapp'] ?? ''),
        'email' => sanitizar($_POST['email'] ?? ''),
        'codigo_pais' => sanitizar($_POST['codigo_pais'] ?? '1058'),
        'pais' => mb_strtoupper(sanitizar($_POST['pais'] ?? 'BRASIL')),
        'limite_credito' => (float) ($_POST['limite_credito'] ?? 0),
        'tabela_preco_id' => !empty($_POST['tabela_preco_id']) ? (int) $_POST['tabela_preco_id'] : null,
        'grupo_cliente_id' => !empty($_POST['grupo_cliente_id']) ? (int) $_POST['grupo_cliente_id'] : null,
        'observacoes' => sanitizar($_POST['observacoes'] ?? ''),
    ]);
    redirecionar('clientes');
}

if ($acao === 'salvar_produto') {
    $sql = 'INSERT INTO produtos (codigo, descricao, descricao_resumida, unidade_medida, marca, codigo_barras, cst_entrada, cst_nfe, cst_nfce, csosn, reducao_base_calculo, mva_origem, ncm, cest, aliquota_icms, aliquota_pis, aliquota_cofins, preco_venda, validade_dias, peso)
            VALUES (:codigo, :descricao, :descricao_resumida, :unidade_medida, :marca, :codigo_barras, :cst_entrada, :cst_nfe, :cst_nfce, :csosn, :reducao_base_calculo, :mva_origem, :ncm, :cest, :aliquota_icms, :aliquota_pis, :aliquota_cofins, :preco_venda, :validade_dias, :peso)';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'codigo' => sanitizar($_POST['codigo'] ?? ''),
        'descricao' => mb_strtoupper(sanitizar($_POST['descricao'] ?? '')),
        'descricao_resumida' => mb_strtoupper(sanitizar($_POST['descricao_resumida'] ?? '')),
        'unidade_medida' => sanitizar($_POST['unidade_medida'] ?? 'UN'),
        'marca' => mb_strtoupper(sanitizar($_POST['marca'] ?? '')),
        'codigo_barras' => sanitizar($_POST['codigo_barras'] ?? ''),
        'cst_entrada' => sanitizar($_POST['cst_entrada'] ?? ''),
        'cst_nfe' => sanitizar($_POST['cst_nfe'] ?? ''),
        'cst_nfce' => sanitizar($_POST['cst_nfce'] ?? ''),
        'csosn' => sanitizar($_POST['csosn'] ?? ''),
        'reducao_base_calculo' => (float) ($_POST['reducao_base_calculo'] ?? 0),
        'mva_origem' => (float) ($_POST['mva_origem'] ?? 0),
        'ncm' => sanitizar($_POST['ncm'] ?? ''),
        'cest' => sanitizar($_POST['cest'] ?? ''),
        'aliquota_icms' => (float) ($_POST['aliquota_icms'] ?? 0),
        'aliquota_pis' => (float) ($_POST['aliquota_pis'] ?? 0),
        'aliquota_cofins' => (float) ($_POST['aliquota_cofins'] ?? 0),
        'preco_venda' => (float) ($_POST['preco_venda'] ?? 0),
        'validade_dias' => (int) ($_POST['validade_dias'] ?? 0),
        'peso' => (float) ($_POST['peso'] ?? 0),
    ]);
    redirecionar('produtos');
}
