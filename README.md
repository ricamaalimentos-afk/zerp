# Zerp API (HTTP + SQLite)

Implementação completa das melhorias sugeridas via API REST com biblioteca padrão Python:

- CRUD completo: grupos de clientes, formas de pagamento, tabelas de preço
- Pedido com itens e regra de preço por prioridade
- Contas a receber: geração, baixa e reabertura
- Relatórios A4 (HTML imprimível) com filtros
- Upload de imagem de produto e logo (base64)
- Auditoria por usuário
- Segurança: CSRF token + rate-limit de login

## Executar API
```bash
python app/main.py
```

## Rodar testes
```bash
pytest -q
```
