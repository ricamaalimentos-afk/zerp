# ERP Zerp (PHP puro + MySQL)

Projeto ERP inicial em **PHP puro**, com frontend em **Bootstrap 4 + SB Admin 2 + jQuery + DataTables**, preparado para uso em ambiente de testes no **XAMPP / Windows 10**.

## Funcionalidades entregues
- Login com usuário/senha e permissões por menu.
- Dashboard com indicadores.
- Cadastros: Usuários, Clientes, Grupos de Clientes, Formas de Pagamento e Prazos, Tabelas de Preço, Produtos, Configurações.
- Pedidos/Vendas com cálculo de frete, ICMS ST, desconto e total.
- Finalização da venda gerando título em Contas a Receber.
- Contas a Receber com baixa e reabertura.
- Máscaras, uppercase automático e preenchimento de endereço por CEP (ViaCEP).
- Layout responsivo com SB Admin 2.

## Instalação (XAMPP)
1. Copie a pasta para `C:\xampp\htdocs\zerp`.
2. Crie o banco importando `database/schema.sql` pelo phpMyAdmin.
3. Ajuste `app/config/config.php` se necessário.
4. Acesse `http://localhost/zerp/public`.
5. Login inicial:
   - E-mail: `admin@erp.local`
   - Senha: `admin123`

## Observações
- Padrão monetário em Real Brasileiro (R$).
- Datas em formato brasileiro nas listagens.
- Este é um MVP inicial pronto para expansão (itens de venda, fiscal avançado, relatórios detalhados, impressão térmica/A4 avançada etc.).
