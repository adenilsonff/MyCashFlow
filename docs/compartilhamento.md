# Compartilhamento e visão conjunta — 22/09/2026

## Como usar

1. O titular abre **Configuração → Compartilhamento** em sua própria conta.
2. Informa o e-mail da outra conta, marca os módulos e escolhe, em cada um, **Somente visualizar** ou **Visualizar e alterar**. Módulo desmarcado não é compartilhado. A autorização inclui seu histórico.
3. O destinatário aceita o convite em **Compartilhados comigo**, usando seu próprio login. O convite é interno, sem envio de e-mail.
4. **Consultar** abre a consulta autorizada. **Gerenciar**, disponível com alteração, abre as telas operacionais do titular. Um aviso identifica de quem são os dados e quem está conectado.
5. **Visão conjunta** permite selecionar uma pessoa ou reunir as contas autorizadas naquele módulo, incluindo a própria conta.
6. O titular pode revogar o acesso. Revisar módulos ou níveis suspende o acesso até nova aceitação. O destinatário também pode deixar de acessar.

Para administrar os investimentos da sua noiva, ela deve convidar sua conta e liberar **Investimentos e proventos → Visualizar e alterar**. Você aceita e usa **Gerenciar**. Compartilhar nos dois sentidos exige um convite de cada pessoa. A instalação não cria convites entre as contas existentes.

## Módulos e limites

| Módulo | Conteúdo autorizado |
|---|---|
| Despesas | Lançamentos, vencimentos e pagamentos |
| Receitas | Receitas regulares/extras e recebimentos |
| Cartão | Compras, parcelas e pagamentos |
| Contas, saldos e movimentações | Contas e movimentos financeiros |
| Investimentos e proventos | Posições nacionais/internacionais, operações e calendário de proventos |
| Day trade | Corretoras, operações, taxas e resultados |
| Análise | Ativos acompanhados, linhas e notas |

O nível de alteração habilita as operações das telas mapeadas para o módulo. Em investimentos, **Correções de operações** permite corrigir ou excluir lançamentos, validando o histórico para impedir vendas superiores às posições. São registros no MyCashFlow, não ordens enviadas à corretora.

Perfil, senha, administração de usuários, regras privadas de importação e configurações gerais não são compartilhados. Relatórios individuais mapeados respeitam o módulo; telas gerais que misturam módulos permanecem pessoais. Links do menu principal retornam à própria conta; links dentro do módulo mantêm o titular autorizado.

Consultas tabulares têm 50 registros por página; totais cobrem o período filtrado completo. Posições e saldos consideram o histórico. Análise somente leitura apresenta linhas/notas em tabela. Com edição, usa o gráfico existente, com preferências locais separadas por ator e titular. Operações e preço médio da carteira só aparecem na Análise delegada quando também há permissão de investimentos.

## Visão conjunta e investimentos

A seleção inclui somente a própria conta e titulares com autorização ativa para o módulo. Lançamentos nunca são transferidos ou fundidos. Totais de despesas, receitas, cartão, saldos e day trade respeitam seus filtros; notas não possuem total numérico.

Investimentos são calculados por titular antes da soma por ativo, tipo e moeda. A página apresenta posições individuais e reunidas, quantidade, custo, preço médio, cotação, valor de mercado e lucro/prejuízo **não realizado das posições abertas**. O resultado não inclui posições encerradas, proventos ou impostos. Reais e dólares permanecem separados, sem conversão cambial implícita.

As cotações vêm do serviço de mercado já utilizado pelo projeto. Fonte, horário e eventual cache desatualizado são indicados. Cotação ausente, não positiva ou com moeda incompatível torna valor de mercado e resultado indisponíveis; um total incompleto não é apresentado como completo. A exibição depende da disponibilidade e cobertura do provedor, inclusive para ativos internacionais.

## Autorização e identidade

`mcfUsuarioId()` permanece sendo o ator autenticado. `mcfDonoId()` fornece o proprietário autorizado apenas na requisição atual. A sessão nunca troca de usuário. No contexto delegado, `@mcf_usuario_id` recebe o titular para consultas existentes que usam esse escopo; sem contexto explícito, continua sendo o próprio ator.

O ID do convite não concede acesso sozinho. `mcfCompartilhamentoExigir` verifica a cada requisição destinatário, titular ativo, estado, módulo e nível. O proprietário vem da autorização no banco, não de um ID arbitrário do navegador. Existe uma lista fechada de rotas delegáveis. Escritas exigem CSRF, contexto e versão atual da permissão; formulários antigos ou sem contexto são rejeitados. IDs de lançamentos/corretoras continuam restritos ao proprietário autorizado.

Convites começam pendentes e só o destinatário aceita. A gestão usa transações, bloqueio de linha, validação do ator e versão. Revisar exige nova aceitação. Não há concessões transitivas nem repasse dos dados recebidos. Vínculos usam IDs estáveis; mudanças de e-mail não transferem permissões.

Revogar bloqueia a próxima requisição. Dados já exibidos ou impressos não podem ser apagados remotamente; requisições previamente autorizadas em andamento podem concluir. Respostas usam a proteção de cache privada do bootstrap existente.

## Banco, implantação e recuperação

A migração `20260922_compartilhamento` cria somente `compartilhamentos` e `compartilhamento_modulos`, com FKs, par único titular/destinatário, módulos enumerados e nível leitura/edição. Não altera usuários nem registros financeiros. Não reaplica migrações antigas no principal.

Após backup privado, defina `MCF_DB_NAME` e execute a ferramenta CLI `tools/migrar_compartilhamento.php --check` e depois `--apply`. Estrutura existente/parcial impede execução. DDL do MariaDB faz commit implícito; em falha parcial, inspecione antes de repetir.

Backup privado: `C:\Users\IFSP\Documents\ChatGPT\MYCASHFLOW\backups\compartilhamento-20260922`. Contém código anterior, dump, hashes, snapshots e comprovante de restauração em base separada. Manifesto e registro de instalação ficam na pasta privada do projeto. Os hashes das 19 tabelas anteriores incluem todos os campos de perfil.

Para reverter código, bloqueie temporariamente o acesso, prepare novo backup e extraia o ZIP em uma pasta privada. Restaure os arquivos alterados identificados no manifesto e retire os arquivos novos listados nele, inclusive rotas de compartilhamento, visão conjunta e correção de operações. As duas tabelas adicionais podem permanecer; não remova tabelas financeiras nem reaplique a migração multiusuário. Preserve a `.htaccess`.

Para recuperação integral, importe o dump em uma NOVA base e configure uma NOVA cópia do código para ela. Confira os dados antes de substituir a instalação. Mudanças posteriores ao backup exigem reconciliação; não sobrescreva diretamente o banco principal.

## Validação

`node tests/multiusuario/run.cjs` reinicializa exclusivamente `mcf_test_20260921`, com servidor e sessões separados. Migrações antigas só são aplicadas à fixture histórica de testes.

Passaram 722 verificações de integração (multiusuário, CRUD, ciclo de conta, consentimento, consulta, edição delegada e perfil), 17 verificações de cálculo conjunto e as regressões PHP/JavaScript da Análise. Cobrem terceiros sem acesso, somente leitura, módulo incorreto, CSRF, IDs adulterados, contexto perdido, versão antiga, revogação, titular inativo, XSS, identidade preservada, alterações em cada módulo, correções de investimentos, seleção conjunta e moedas/cotações ausentes.

A interface de permissões, aviso de titular delegado e visão conjunta foram conferidos com contas fictícias. Não foi repetida uma matriz manual completa do gráfico. No principal, a validação compara arquivos, schema e dados e verifica rotas sem autenticação; não altera credenciais nem cria permissões reais.

## Melhorias de uso e rastreabilidade (22/09/2026)

O seletor **Conta neste módulo** permite ir à própria conta, a um titular autorizado ou à visão conjunta. A seleção é validada no servidor e não troca a identidade da sessão. O menu principal continua sendo pessoal. Para leitores, o seletor abre a consulta; para quem possui ações de alteração, abre o gerenciamento.

Ao escolher **Visualizar e alterar**, marque separadamente **Cadastrar**, **Editar** e **Excluir**. Nenhuma ação vem marcada em um novo convite. Revisar mantém os valores atuais e exige nova aceitação. Permissões de edição já existentes antes desta atualização preservam as três ações; somente leitura continua bloqueando qualquer escrita. Operações compostas, como importar OFX ou alterar regras recorrentes, podem exigir mais de uma ação.

**Histórico dos meus dados** registra as alterações feitas pelo aplicativo nos sete módulos desde a instalação, por autor e titular, com valores antes/depois, data e filtro por módulo. Somente o titular lê o histórico; não há função de editar/apagar ou restaurar automaticamente registros. Não inclui senhas nem alterações retroativas anteriores à instalação. Administradores com acesso direto ao banco continuam fora do controle da interface.

O bootstrap define variáveis de conexão confiáveis para ator, titular, convite e versão. A migração `tools/migrar_melhorias.php` adiciona três colunas à tabela de permissões, cria `historico_alteracoes` e `compartilhamento_vistos` e instala 90 triggers em 15 tabelas. Os triggers verificam a ação de escrita e gravam a auditoria na mesma transação; rollback desfaz ambos. Exclusões em cascata de compras/corretoras registram os filhos antes da exclusão. Conexões CLI de manutenção sem ator não são auditadas. Não há registro fictício de eventos antigos.

**Avisos de compartilhamento** apresenta vínculos cujo estado/permissões mudaram desde a última versão marcada como lida. O contador no cabeçalho atualiza a cada carregamento; não envia e-mail nem notificações externas. Marcar como lido não aceita o convite. O painel representa a última versão de cada vínculo, não um feed de todos os eventos intermediários.

O comparativo em **Visão conjunta → Investimentos** apresenta participação por titular no custo e no valor de mercado (quando completo), distribuição por ativo ao custo, e gráfico/tabela de 12 meses. A evolução é do **custo das posições abertas** calculado com o histórico de cada titular: não é rentabilidade, aportes líquidos nem valor de mercado histórico. Moedas continuam separadas; a seleção respeita autorizações atuais.

Validação desta extensão: 769 verificações de integração, 17 de cálculo conjunto, 11 de comparativo, 24 de guardas SQL/rollback/cascata, além das regressões PHP/JS da Análise. Dados fictícios exclusivamente em `mcf_test_20260921`. Interface conferida em navegador, sem refazer toda a matriz manual de gestos do gráfico de Análise.

Backup da extensão: `C:\Users\IFSP\Documents\ChatGPT\MYCASHFLOW\backups\melhorias-compartilhamento-20260922`, restaurado em base separada e comparado às 21 tabelas anteriores. A preservação compara todas as colunas que já existiam, incluindo vínculos e níveis; novas colunas recebem os padrões de compatibilidade descritos acima. Manifesto e registro de instalação ficam na pasta privada do projeto.

Em uma reversão, não publique o código anterior de compartilhamento com permissões granulares ativas: ele não conhece as restrições por ação. Bloqueie o acesso e prepare novo backup; restaure primeiro em banco/pasta separados, reconcilie mudanças posteriores e só reabra com uma combinação de código/schema validada. Preserve o histórico novo. DDL não tem rollback automático; migração parcial deve ser inspecionada antes de qualquer nova tentativa.
