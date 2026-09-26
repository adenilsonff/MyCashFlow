# Relatórios: gastos, receitas e cartões

Os três relatórios usam os períodos, agrupamentos, comparações, gráficos e filtros comuns da Central. A consulta exibida fica guardada por até 30 minutos na sessão, com limite de cinco consultas. A exportação revalida a autorização do módulo e mantém os valores da consulta, mesmo que os lançamentos sejam alterados depois.

## Bases de cálculo

- Gastos: contas por vencimento; tipos única, parcelada e recorrente, corrigindo a antiga identificação por tipo mensal inexistente.
- Receitas: rendas por data cadastrada; classificação regular/extra.
- Cartões: somente parcelas de cartoes, com compras vinculadas pelo ID e titular. Não soma valor_total da compra nem pagamentos registrados como despesas. Créditos permanecem negativos. Natureza inclui pessoal, conjunta e cliente/reembolsável (código legado unica).
- Efetivado + pendente reconcilia com o total. Recorrente, parcelado e atraso são subconjuntos, não novos valores a somar. Meses sem registros são diferentes de meses com valor zero.
- Situação atual não representa caixa histórico. A data da parcela não comprova vencimento bancário; por isso cartões mostram abertos em datas passadas. Não há emissor/cartão cadastrado para segmentação.
- Seleção de perfis respeita a autorização específica de despesas, receitas ou cartão. Não exige a autorização dos dois módulos, como o relatório financeiro. Perfis sem registros permanecem identificados no consolidado.

## Validação

22 verificações unitárias dos módulos, 81 verificações HTTP e regressão financeira de 34 verificações unitárias + 40 HTTP. Banco fictício isolado em 127.0.0.1:3307/mcf_reports_test. PDFs dos cinco tipos de gráfico gerados; detalhados inspecionados nas 31 páginas, com valores, registros, cabeçalhos repetidos, paginação e snapshot conferidos. Navegador: login fictício autorizado, atualização de gráfico, detalhamento por mês, limpeza, download e largura móvel de 390 px sem extravasamento do documento.

## Continuidade

Investimentos, proventos e day trade permanecem para a próxima etapa. Não houve migração de banco nem importação de planilha. Categorias de finalidade, datas de liquidação e vínculo de duplicatas compartilhadas ainda exigem evolução do modelo de dados. O filtro de perfis da nova tela permite leitores; links antigos de contexto delegado mantêm as regras anteriores.
