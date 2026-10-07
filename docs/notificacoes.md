# Central de avisos — etapa 3

Acesse **Avisos**, no topo do sistema, ou **Configuração → Central de avisos**.

## Regras de funcionamento

| Aviso | Quando aparece | Quando sai da lista |
|---|---|---|
| Despesa | Não paga, até 7 dias antes do vencimento; inclui atrasadas | Ao pagar, excluir ou mudar a data para fora da janela |
| Cartão | Nos 3 dias anteriores e no dia previsto de fechamento | Após o fechamento, até a próxima janela mensal |
| Provento | Pagamento cadastrado para hoje ou os próximos 7 dias e posição positiva na data com | Depois da data prevista, exclusão ou posição sem direito |
| Assinatura | Acesso ativo, até 15 dias antes de expirar | Renovação para fora da janela |
| Compartilhamento | Convite ou nova versão das permissões | Ao marcar essa versão como lida |

O contador reúne avisos financeiros não lidos e avisos de compartilhamento. As consultas são refeitas ao navegar, usando a data de Brasília. Não há serviço em segundo plano, e-mail, notificação do navegador nem consulta externa para gerar estes lembretes.

Os avisos financeiros são exclusivamente da pessoa conectada; não agregam dados de outros titulares. Módulos desativados, inclusive pelos módulos pais, não geram avisos financeiros. Compartilhamento conserva o comportamento anterior.

Marcar como lido não paga despesas, aceita convites nem confirma recebimentos. O filtro **Lidos** mostra apenas ocorrências ainda atuais. Não é um histórico permanente de notificações resolvidas. Uma mudança relevante no lançamento ou passagem de conta a vencer para atrasada gera nova ocorrência. Uma simples visita ou mudança do número de dias restantes não gera duplicata.

## Cartões

Em **Lembretes de fechamento dos cartões**, informe nome e dia habitual de fechamento (1–31). É possível editar e remover o lembrete. Há limite de 30 cartões por pessoa; nomes repetidos na mesma conta são recusados.

Este cadastro não vincula compras a cartões físicos e não calcula faturas. A data é estimada a partir do dia informado; confirme-a no banco. Em mês menor, dias 29–31 usam o último dia disponível. O aviso liga à tela de lançamentos do mês correspondente. As compras existentes não são alteradas.

## Proventos

Usa eventos de `div_datacom` e posição de `investimentos_nacionais` por titular, ticker e tipo de ativo na data com, incluindo compras e vendas até essa data. Não importa novos eventos de uma API. O valor mostrado é uma estimativa bruta, sem confirmar recebimento ou deduzir impostos. Lançamentos futuros podem mudar a estimativa; mantenha os dados atualizados.

## Dados, instalação e testes

Migração aditiva: `migrations/20261006_notificacoes.sql`. Cria somente `notificacao_cartoes` e `notificacao_lidos`. O utilitário CLI `tools/migrar_notificacoes.php` exige `MCF_DB_NAME` e `--check` ou `--apply`; recusa estrutura existente/parcial. Prepare um backup antes de aplicar. DDL não é revertido automaticamente.

Configurações de cartões entram no backup individual, com remapeamento de IDs na restauração. Backups anteriores reconhecidos continuam aceitos e ganham lista vazia de lembretes. Estados de leitura são pessoais, ficam fora do backup e não são necessários para reconstruir lembretes a partir dos lançamentos.

As gravações usam POST, CSRF e titular autenticado. Marcar como lido valida novamente se a ocorrência pertence ao usuário e ainda existe. Chaves SHA-256 estáveis identificam ocorrências; não contêm valores financeiros em texto. A consulta é reutilizada no mesmo carregamento entre central e cabeçalho. A central pagina os lembretes em grupos de 30.

Testes de regras e backup: `tests/notificacoes.test.php`, restritos ao banco sintético `mcf_notifications` na porta 3307, preparado com estrutura atual e migração. A verificação de interface também cobre leitura, filtros, contador no contexto compartilhado, CSRF, isolamento, cadastro/edição/remoção e layout móvel.
