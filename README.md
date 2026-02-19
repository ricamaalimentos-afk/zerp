# Zerp (domínio ERP simplificado)

Implementa em Python puro os módulos solicitados:
- CRUD de grupos de clientes, formas de pagamento e tabelas de preço
- Lançamento de pedidos com itens e regra de preço por prioridade
- Geração/baixa/reabertura de títulos no contas a receber
- Relatórios imprimíveis A4 (HTML)
- Upload de imagem de produto/logo
- Auditoria por usuário
- Segurança extra com CSRF token e rate limit de login

## Rodar testes
```bash
pytest -q
```
