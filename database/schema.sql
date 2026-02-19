CREATE DATABASE IF NOT EXISTS zerp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE zerp;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(20) NOT NULL UNIQUE,
    nome VARCHAR(120) NOT NULL,
    apelido VARCHAR(120),
    cep VARCHAR(9),
    endereco VARCHAR(150),
    bairro VARCHAR(100),
    cidade VARCHAR(100),
    uf CHAR(2),
    fone VARCHAR(20),
    whatsapp VARCHAR(20),
    email VARCHAR(120) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    comissao DECIMAL(10,2) DEFAULT 0,
    limite_desconto DECIMAL(10,2) DEFAULT 0,
    nivel ENUM('ADMIN', 'VENDEDOR') DEFAULT 'VENDEDOR',
    permissoes JSON NULL,
    ativo TINYINT(1) DEFAULT 1,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS grupos_clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao VARCHAR(255),
    ativo TINYINT(1) DEFAULT 1
);

CREATE TABLE IF NOT EXISTS formas_pagamento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    prazo_dias INT DEFAULT 0,
    parcelas INT DEFAULT 1,
    ativo TINYINT(1) DEFAULT 1
);

CREATE TABLE IF NOT EXISTS tabelas_preco (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    percentual_acrescimo DECIMAL(10,2) DEFAULT 0,
    ativo TINYINT(1) DEFAULT 1
);

CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_razao VARCHAR(150) NOT NULL,
    apelido_empresa VARCHAR(150),
    contato VARCHAR(120),
    cpf_cnpj VARCHAR(20),
    cep VARCHAR(9),
    endereco VARCHAR(150),
    bairro VARCHAR(100),
    cidade VARCHAR(100),
    uf CHAR(2),
    cod_municipio_ibge VARCHAR(10),
    data_nascimento DATE,
    telefone VARCHAR(20),
    whatsapp VARCHAR(20),
    email VARCHAR(120),
    codigo_pais VARCHAR(5) DEFAULT '1058',
    pais VARCHAR(80) DEFAULT 'BRASIL',
    limite_credito DECIMAL(12,2) DEFAULT 0,
    tabela_preco_id INT NULL,
    grupo_cliente_id INT NULL,
    observacoes TEXT,
    ativo TINYINT(1) DEFAULT 1,
    FOREIGN KEY (tabela_preco_id) REFERENCES tabelas_preco(id),
    FOREIGN KEY (grupo_cliente_id) REFERENCES grupos_clientes(id)
);

CREATE TABLE IF NOT EXISTS produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(20) NOT NULL UNIQUE,
    descricao VARCHAR(180) NOT NULL,
    descricao_resumida VARCHAR(120),
    unidade_medida VARCHAR(10) DEFAULT 'UN',
    marca VARCHAR(100),
    codigo_barras VARCHAR(20),
    cst_entrada VARCHAR(5),
    cst_nfe VARCHAR(5),
    cst_nfce VARCHAR(5),
    csosn VARCHAR(5),
    reducao_base_calculo DECIMAL(10,2) DEFAULT 0,
    mva_origem DECIMAL(10,2) DEFAULT 0,
    ncm VARCHAR(10),
    cest VARCHAR(10),
    aliquota_icms DECIMAL(10,2) DEFAULT 0,
    aliquota_pis DECIMAL(10,2) DEFAULT 0,
    aliquota_cofins DECIMAL(10,2) DEFAULT 0,
    preco_venda DECIMAL(12,2) NOT NULL,
    validade_dias INT DEFAULT 0,
    peso DECIMAL(10,3) DEFAULT 0,
    imagem VARCHAR(255),
    ativo TINYINT(1) DEFAULT 1
);

CREATE TABLE IF NOT EXISTS vendas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    usuario_id INT NOT NULL,
    data_venda DATETIME DEFAULT CURRENT_TIMESTAMP,
    frete DECIMAL(12,2) DEFAULT 0,
    icms_st DECIMAL(12,2) DEFAULT 0,
    desconto DECIMAL(12,2) DEFAULT 0,
    observacoes TEXT,
    total_bruto DECIMAL(12,2) DEFAULT 0,
    total_liquido DECIMAL(12,2) DEFAULT 0,
    status ENUM('ABERTO','FINALIZADO','CANCELADO') DEFAULT 'ABERTO',
    FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

CREATE TABLE IF NOT EXISTS contas_receber (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venda_id INT NOT NULL,
    parcela INT NOT NULL,
    vencimento DATE NOT NULL,
    valor_parcela DECIMAL(12,2) NOT NULL,
    valor_aberto DECIMAL(12,2) NOT NULL,
    status ENUM('ABERTO','BAIXADO','REABERTO') DEFAULT 'ABERTO',
    data_baixa DATETIME NULL,
    FOREIGN KEY (venda_id) REFERENCES vendas(id)
);

CREATE TABLE IF NOT EXISTS configuracoes_gerais (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_sistema VARCHAR(120) NOT NULL,
    versao VARCHAR(30) NOT NULL,
    logo VARCHAR(255),
    cor_primaria VARCHAR(20) DEFAULT '#4e73df',
    cor_secundaria VARCHAR(20) DEFAULT '#1cc88a',
    fonte VARCHAR(100) DEFAULT 'Nunito',
    cabecalho_impressao TEXT,
    rodape_impressao TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO usuarios (codigo, nome, apelido, email, senha, nivel, permissoes, ativo)
SELECT 'U0001', 'ADMINISTRADOR', 'ADMIN', 'admin@zerp.local', '$2y$10$Z4EgbcmnJj9q2a4NOGdS/.Pi0hP7vM8Rz4qqsN7LtvQWwG6Dvlh0a', 'ADMIN', JSON_ARRAY('usuarios','clientes','produtos','vendas','receber','configuracoes'), 1
WHERE NOT EXISTS (SELECT 1 FROM usuarios WHERE email = 'admin@zerp.local');

INSERT INTO grupos_clientes (nome, descricao, ativo)
SELECT 'VAREJO', 'GRUPO PADRÃO', 1
WHERE NOT EXISTS (SELECT 1 FROM grupos_clientes WHERE nome = 'VAREJO');

INSERT INTO tabelas_preco (nome, percentual_acrescimo, ativo)
SELECT 'PADRÃO', 0, 1
WHERE NOT EXISTS (SELECT 1 FROM tabelas_preco WHERE nome = 'PADRÃO');

INSERT INTO configuracoes_gerais (nome_sistema, versao, cabecalho_impressao, rodape_impressao)
SELECT 'ZERP - SISTEMA ERP', '0.1.0', 'Cabeçalho padrão', 'Rodapé padrão'
WHERE NOT EXISTS (SELECT 1 FROM configuracoes_gerais);
