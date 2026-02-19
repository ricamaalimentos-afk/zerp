# ZERP (PHP puro + MySQL)

Implementação backend em PHP puro para:

- CRUD completo: grupos de clientes, formas de pagamento e tabelas de preço
- Pedidos com itens e prioridade de preço (negociado > tabela > base)
- Contas a receber: geração, baixa e reabertura
- Relatórios A4 (contas a receber e pedidos) com filtros
- Upload de imagem de produto e logo
- Auditoria por usuário
- Segurança adicional: CSRF e rate-limit de login

## Configuração
Defina as variáveis:

- `DB_HOST`
- `DB_PORT`
- `DB_NAME`
- `DB_USER`
- `DB_PASS`

## Migrations
```bash
php database/migrate.php
```

## Executar
```bash
php -S 127.0.0.1:8080 -t public
```

## Endpoints principais
- `POST /auth/login`
- `GET|POST /customer-groups`
- `PUT|DELETE /customer-groups/{id}`
- `GET|POST /payment-methods`
- `PUT|DELETE /payment-methods/{id}`
- `GET|POST /products`
- `GET|POST /price-tables`
- `PUT|DELETE /price-tables/{id}`
- `POST /orders`
- `PUT /orders/{id}`
- `POST /orders/{id}/items`
- `PUT|DELETE /orders/{id}/items/{itemId}`
- `POST /orders/{id}/generate-title`
- `POST /receivables/{id}/settle`
- `POST /receivables/{id}/reopen`
- `GET /reports/receivables`
- `GET /reports/orders`
- `POST /upload/product-image`
- `POST /upload/company-logo`
- `GET /audits`
