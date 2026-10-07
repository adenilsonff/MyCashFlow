# Revisão funcional para a apresentação — 06/10/2026

## Correções

- **Saldo disponível:** inclui saldo inicial e movimentos somente até a data atual em Brasília. Lançamentos futuros continuam cadastrados, mas não financiam reservas hoje. Movimentos anteriores à data do saldo inicial continuam excluídos da soma. A quantidade de movimentos continua incluindo todos, preservando verificações de exclusão.
- **Valores monetários:** despesas, receitas e contas/saldos validam o formato antes de converter. Letras, valores em lista, notação científica, casas excedentes e montantes fora da capacidade do cadastro são rejeitados sem gravação. São aceitos `1234.56`, `1234,56` e `1.234,56`. Zero permanece permitido onde já era válido; saldo inicial negativo continua permitido. A interface mostra o erro.
- **PDFs e módulos:** os dois endpoints de exportação verificam se Relatórios está habilitado, mesmo quando a consulta foi criada antes da desativação. Autenticação, CSRF e revisão de permissões do titular continuam obrigatórios.
- **Saída:** abrir a página Sair por GET não destrói mais a sessão. Há confirmação por POST com CSRF. O link existente no menu leva à confirmação.
- **Compartilhamento:** o seletor de conta voltou às telas compatíveis quando há compartilhamentos ativos. Respeita consulta/edição, titular e revogação. Não aparece sem compartilhamentos, exceto nas próprias telas de consulta conjunta.

## Validações em ambiente isolado

Foram usadas contas fictícias e bases na porta 3307. As fixtures de relatórios foram atualizadas somente na cópia de testes para usar a estrutura atual; não houve adaptação das expectativas financeiras para ocultar falhas.

- 26 verificações de normalização monetária, datas de saldos, transferências e cobertura das reservas.
- 44 verificações HTTP das correções, incluindo POSTs inválidos, proteção da sessão e PDFs após desativação do módulo.
- 203 verificações HTTP dos relatórios financeiro, gastos, receitas, cartão, investimentos, proventos e day trade.
- 50 verificações de backup/recuperação, incluindo isolamento, vínculos, precisão e rollback.
- 9 verificações de interface: seletor, visual móvel, erro monetário, confirmação de saída e gráfico anual com recursos externos bloqueados.
- Conferência textual/estrutural de sete PDFs de módulos: totais esperados, registros, moedas e paginação. Amostras visuais e telas móveis foram inspecionadas. Isso não equivale a inspeção de cada página ou carga real de três anos.

O gráfico inicial já usa Chart.js local. Avisos de API, cotações salvas, notificações, popup e módulo Reservas independente foram preservados.

## Pendências para encerrar a preparação

1. **Data da banca:** acesso atual válido até 04/11/2026. A validade não foi alterada; comparar com a data da apresentação antes do ensaio.
2. **Acabamento de PDFs:** há espaço para reduzir áreas em branco e melhorar as quebras em relatórios com vários anos. Exportação e totais passaram, mas o acabamento ainda merece uma etapa própria.
3. **Dados reais:** importar os três anos e reconciliar totais mensais com a fonte original. Os testes desta rodada usaram dados fictícios.
4. **Documentação:** preparar manual do usuário e roteiro da apresentação após estabilizar os PDFs.
5. **Recuperação por e-mail:** depende da configuração do serviço de envio; não foi configurada nesta revisão.

Esta rodada corrige falhas reproduzidas e verifica os fluxos descritos. Não representa garantia de ausência de qualquer bug, teste de longa duração ou auditoria completa das bibliotecas externas.
