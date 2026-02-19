# ZERP (PHP puro + MySQL + SB Admin 2)

Projeto inicial de ERP em **PHP puro** para rodar no **XAMPP (Windows 10)**, usando:

- Bootstrap + tema SB Admin 2
- jQuery
- DataTables (pt-BR)
- jQuery Mask
- ViaCEP para autocomplete de endereço

## Módulos iniciais entregues

- Login/autenticação com usuário admin padrão
- Cadastro de usuários com níveis/permissões de menu
- Cadastro de clientes
- Cadastro de produtos
- Dashboard com indicadores
- Listagem de pedidos/vendas
- Listagem de contas a receber
- Configurações gerais (leitura)

## Como rodar no XAMPP

1. Copie a pasta `zerp` para `C:/xampp/htdocs/zerp`.
2. Inicie Apache e MySQL no XAMPP.
3. Crie o banco executando o script `database/schema.sql` no phpMyAdmin.
4. Acesse: `http://localhost/zerp/public`.
5. Login padrão:
   - E-mail: `admin@zerp.local`
   - Senha: `admin123`

## Próximas melhorias sugeridas

- CRUD completo para grupos de clientes, formas de pagamento e tabelas de preço
- Lançamento completo de pedido com itens e regra de preço por prioridade
- Geração/baixa/reabertura de títulos no contas a receber
- Relatórios imprimíveis (A4) com filtros por período e status
- Upload de imagem de produto/logo
- Auditoria (log de ações por usuário)
- Proteções extras de segurança (CSRF token, rate-limit de login)
