CREATE DATABASE IF NOT EXISTS zerp_erp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE zerp_erp;

CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(150) NOT NULL,
  apelido VARCHAR(100) NULL,
  cep VARCHAR(9) NULL,
  endereco VARCHAR(180) NULL,
  bairro VARCHAR(120) NULL,
  cidade VARCHAR(120) NULL,
  uf CHAR(2) NULL,
  fone VARCHAR(20) NULL,
  whatsapp VARCHAR(20) NULL,
  email VARCHAR(180) NOT NULL UNIQUE,
  senha VARCHAR(255) NOT NULL,
  comissao DECIMAL(10,2) DEFAULT 0,
  limite_desconto DECIMAL(10,2) DEFAULT 0,
  nivel ENUM('admin','vendedor') DEFAULT 'vendedor',
  permissoes_menu TEXT NULL,
  ativo TINYINT(1) DEFAULT 1,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE grupos_clientes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  ativo TINYINT(1) DEFAULT 1,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE tabelas_preco (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  percentual_ajuste DECIMAL(10,2) DEFAULT 0,
  grupo_cliente_id INT NULL,
  ativo TINYINT(1) DEFAULT 1,
  FOREIGN KEY (grupo_cliente_id) REFERENCES grupos_clientes(id)
);

CREATE TABLE formas_pagamento (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  prazo_dias INT DEFAULT 0,
  ativo TINYINT(1) DEFAULT 1
);

CREATE TABLE clientes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome_razao_social VARCHAR(180) NOT NULL,
  apelido_empresa VARCHAR(180) NULL,
  contato VARCHAR(150) NULL,
  cpf_cnpj VARCHAR(20) NULL,
  cep VARCHAR(9) NULL,
  endereco VARCHAR(180) NULL,
  bairro VARCHAR(120) NULL,
  cidade VARCHAR(120) NULL,
  uf CHAR(2) NULL,
  cod_mun_ibge VARCHAR(10) NULL,
  data_nascimento DATE NULL,
  telefone VARCHAR(20) NULL,
  whatsapp VARCHAR(20) NULL,
  email VARCHAR(180) NULL,
  codigo_pais VARCHAR(5) DEFAULT '1058',
  pais VARCHAR(80) DEFAULT 'BRASIL',
  limite_credito DECIMAL(12,2) DEFAULT 0,
  tabela_preco_id INT NULL,
  grupo_cliente_id INT NULL,
  observacoes TEXT NULL,
  ativo TINYINT(1) DEFAULT 1,
  FOREIGN KEY (tabela_preco_id) REFERENCES tabelas_preco(id),
  FOREIGN KEY (grupo_cliente_id) REFERENCES grupos_clientes(id)
);

CREATE TABLE produtos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  descricao VARCHAR(200) NOT NULL,
  descricao_resumida VARCHAR(120) NULL,
  unidade_medida VARCHAR(20) NULL,
  marca VARCHAR(80) NULL,
  codigo_barras VARCHAR(30) NULL,
  cst_entrada VARCHAR(10) NULL,
  cst_nfe VARCHAR(10) NULL,
  cst_nfce VARCHAR(10) NULL,
  csosn VARCHAR(10) NULL,
  reducao_base_calculo DECIMAL(10,2) DEFAULT 0,
  mva_origem DECIMAL(10,2) DEFAULT 0,
  ncm VARCHAR(12) NULL,
  cest VARCHAR(12) NULL,
  aliquota_icms DECIMAL(10,2) DEFAULT 0,
  aliquota_pis DECIMAL(10,2) DEFAULT 0,
  aliquota_cofins DECIMAL(10,2) DEFAULT 0,
  preco_venda DECIMAL(12,2) DEFAULT 0,
  validade_dias INT DEFAULT 0,
  peso DECIMAL(10,3) DEFAULT 0,
  imagem VARCHAR(255) NULL,
  ativo TINYINT(1) DEFAULT 1
);

CREATE TABLE vendas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cliente_id INT NOT NULL,
  vendedor_id INT NOT NULL,
  data_venda DATE NOT NULL,
  valor_produtos DECIMAL(12,2) DEFAULT 0,
  frete DECIMAL(12,2) DEFAULT 0,
  icms_st DECIMAL(12,2) DEFAULT 0,
  desconto DECIMAL(12,2) DEFAULT 0,
  valor_total DECIMAL(12,2) DEFAULT 0,
  observacoes TEXT NULL,
  status ENUM('aberto','finalizado','cancelado') DEFAULT 'aberto',
  FOREIGN KEY (cliente_id) REFERENCES clientes(id),
  FOREIGN KEY (vendedor_id) REFERENCES usuarios(id)
);

CREATE TABLE contas_receber (
  id INT AUTO_INCREMENT PRIMARY KEY,
  venda_id INT NOT NULL,
  cliente_id INT NOT NULL,
  data_emissao DATE NOT NULL,
  data_vencimento DATE NOT NULL,
  data_pagamento DATE NULL,
  valor DECIMAL(12,2) NOT NULL,
  status ENUM('aberto','baixado') DEFAULT 'aberto',
  observacoes TEXT NULL,
  FOREIGN KEY (venda_id) REFERENCES vendas(id),
  FOREIGN KEY (cliente_id) REFERENCES clientes(id)
);

CREATE TABLE configuracoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome_sistema VARCHAR(120),
  versao VARCHAR(40),
  tema_cor VARCHAR(30),
  fonte VARCHAR(80),
  logo VARCHAR(255),
  cabecalho_relatorio VARCHAR(255),
  rodape_relatorio VARCHAR(255),
  informacoes_relatorio TEXT
);

INSERT INTO usuarios (nome,email,senha,nivel,permissoes_menu,ativo) VALUES
('ADMINISTRADOR','admin@erp.local', '$2y$10$uSQ4icULhZ.Q5UQ6ddf7Ju4w4J8hcof6f5vQ9R8mKQhP/Bt5W6A7S', 'admin', 'usuarios,clientes,produtos,vendas,financeiro,cadastros,configuracoes', 1);
