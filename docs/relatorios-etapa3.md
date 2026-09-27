# Investimentos, proventos e day trade

Os três módulos agora usam filtros de período, agrupamento, comparação, gráficos, detalhes e exportação PDF da Central. As páginas anteriores continuam acessíveis pelo link Visão clássica, incluindo a avaliação da carteira de investimentos.

## Cálculos e limites

- Investimentos: movimentação por data da operação, quantidade absoluta × preço unitário. Vendas legadas com quantidade negativa são reconhecidas. BRL e USD são separados; não há conversão ou soma entre moedas. A diferença compras menos vendas não é rentabilidade nem valor da carteira. A precisão decimal é preservada até o arredondamento de cada operação.
- Proventos: estimativa bruta pela posição líquida na data-com, incluindo operações nessa data. Posições não positivas são excluídas. Pode agrupar por data-com ou pagamento previsto; eventos sem data de pagamento ficam fora da segunda opção. A data cadastrada não confirma recebimento. Não calcula imposto nem inclui proventos internacionais.
- Day trade: apresenta valores registrados de resultado bruto/final, taxas e DARF. Não recalcula obrigações tributárias ou compensações. Permite filtrar operações abertas/concluídas, ativo e corretora. Ganhos positivos e perdas negativas reconciliam com o resultado final registrado.
- Compartilhamento: investimentos e proventos respeitam o módulo investimentos; day trade exige sua autorização própria. PDF revalida acesso e preserva o snapshot da consulta. Consultas são limitadas a 6.000 registros, sem truncamento silencioso.

## Validação

76 verificações de cálculo (34 financeiro, 22 gastos/receitas/cartões, 20 mercado) e 203 HTTP (40 + 81 + 82): 279 no total. Dados exclusivamente fictícios em 127.0.0.1:3307/mcf_reports_test. Quatro PDFs detalhados de referência, incluindo USD, tiveram totais, registros, moeda, paginação e preservação do snapshot conferidos. A inspeção visual identificou e corrigiu quebra de indicador entre páginas.

Navegador: conta fictícia autorizada, troca BRL/USD, base de pagamento prevista dos proventos, indicadores de day trade, detalhes por mês e largura móvel de 390 px. Sem erros JavaScript observados.

## Continuidade

A rentabilidade histórica da carteira exige histórico de preços, custos completos e tratamento de eventos corporativos; não foi simulada a partir de preços atuais. Proventos efetivamente recebidos exigem confirmação/data de liquidação. Não houve importação real ou alteração de esquema de banco. Relatórios cruzados e novos tipos de gráfico permanecem como evoluções futuras.
