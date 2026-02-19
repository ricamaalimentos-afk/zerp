# Próximas melhorias sugeridas (plano de execução)

Este repositório está inicializado sem código de aplicação no momento. Para viabilizar a implementação das melhorias solicitadas, segue um plano de execução objetivo com critérios de aceite por módulo.

## 1) CRUD completo
### Escopo
- Grupos de clientes
- Formas de pagamento
- Tabelas de preço

### Entregas
- Modelos de dados (migrações + índices)
- Endpoints/API REST para criar, listar, atualizar e remover
- Telas de listagem e formulário com validações
- Soft-delete onde aplicável
- Testes unitários e de integração

### Critérios de aceite
- Operações CRUD funcionando para os 3 cadastros
- Busca/filtro por nome/código e paginação
- Regras de integridade (não permitir exclusão de registro em uso sem tratamento)

---

## 2) Lançamento completo de pedido
### Escopo
- Pedido com múltiplos itens
- Regra de preço por prioridade

### Entregas
- Entidades: pedido, item de pedido, status de pedido
- Serviço de cálculo com prioridade de preço:
  1. preço negociado no item
  2. tabela de preço do cliente
  3. preço padrão do produto
- Cálculo de subtotal, descontos e total
- Validação de estoque (se aplicável)

### Critérios de aceite
- Inclusão/edição/remoção de itens no pedido
- Recalcular total automaticamente a cada alteração
- Auditoria de alterações de preço manual

---

## 3) Contas a receber
### Escopo
- Geração de títulos
- Baixa
- Reabertura

### Entregas
- Entidade de título com histórico de status
- Rotina de geração de títulos a partir de pedido faturado
- Baixa parcial/total com data e forma de pagamento
- Reabertura com trilha de auditoria e motivo obrigatório

### Critérios de aceite
- Fluxo completo: gerado → aberto → baixado → reaberto
- Controle de saldo por título
- Relatórios com valores em aberto/baixados por período

---

## 4) Relatórios imprimíveis (A4)
### Escopo
- Filtros por período e status

### Entregas
- Templates A4 (CSS de impressão)
- Relatórios de pedidos e contas a receber
- Exportação PDF e impressão direta
- Parâmetros de filtro persistidos na URL

### Critérios de aceite
- Layout A4 sem quebra incorreta de tabela
- Filtros aplicados corretamente no resultado e no PDF

---

## 5) Upload de imagem
### Escopo
- Imagem de produto
- Logo da empresa

### Entregas
- Endpoint de upload com validação (tipo/tamanho)
- Otimização básica (redimensionar/comprimir)
- Armazenamento com naming seguro e URL pública controlada
- Placeholder quando imagem ausente

### Critérios de aceite
- Upload e troca de imagem funcionando
- Bloqueio de arquivos inválidos

---

## 6) Auditoria por usuário
### Escopo
- Log de ações críticas

### Entregas
- Registro de evento: usuário, ação, entidade, antes/depois, timestamp, IP
- Middleware para capturar ações sensíveis
- Tela/consulta de auditoria com filtros

### Critérios de aceite
- Eventos gravados para operações de cadastro, pedidos e financeiro
- Consulta por usuário, período e entidade

---

## 7) Segurança adicional
### Escopo
- CSRF token
- Rate limit de login

### Entregas
- Proteção CSRF em endpoints de escrita
- Limite por IP/usuário para tentativas de login
- Bloqueio temporário progressivo
- Logs e métrica de tentativas bloqueadas

### Critérios de aceite
- Requisições sem CSRF válidas devem falhar
- Ataque de força bruta com múltiplas tentativas deve ser limitado

---

## Ordem recomendada de implementação
1. Base de dados e CRUDs mestres
2. Fluxo de pedidos com cálculo de preço
3. Contas a receber acoplado ao faturamento
4. Auditoria transversal
5. Segurança (CSRF e rate limit)
6. Relatórios e impressão
7. Upload de imagens e ajustes finais de UX

## Definição de pronto (DoD)
- Testes automatizados passando
- Migrações versionadas
- Logs observáveis para fluxos críticos
- Documentação de endpoints e regras de negócio
- Checklist de segurança revisado
