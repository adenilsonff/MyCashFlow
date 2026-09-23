# Multiusuário — 21/09/2026

## Resultado e limite do escopo

Cada conta tem proprietário fixado pela sessão autenticada. Não há compartilhamento,
personificação, troca de identidade nem consolidação de duas pessoas. A classificação
“Conjunta” continua sendo somente uma categoria privada da própria conta.

Os dados preexistentes foram preservados. Durante esta tarefa, o responsável esclareceu
que eram dados fictícios de desenvolvimento e autorizou sua reorganização. Os registros
legados sem proprietário foram organizados na conta existente, ID 1. Esse procedimento
não deve ser repetido em outro banco com dados reais sem confirmar a titularidade.

## Módulos, tabelas e pontos de acesso

| Área | Tabelas privadas | Páginas e endpoints revisados |
|---|---|---|
| Autenticação | usuarios, recuperacao_senha | index.php; views/login/login.php, register.php, logout.php, esqueci_senha.php, redefinir_senha.php |
| Contas a pagar | contas | views/contas.php; dashboard; relatórios financeiro/gastos |
| Receitas regulares e extras | rendas | views/rendas.php; extras.php como atalho; dashboard; relatórios financeiro/receitas |
| Cartão e importação | compras, cartoes, ofx_importacoes, cartao_nomes_recorrentes | views/cartao.php; dashboard; relatório cartao |
| Daytrade e taxas | corretoras, corretora_taxas, operacoes | views/daytrade.php; daytrade/listar_operacoes.php, editar_operacao.php, ajustar_operacao.php, salvar_operacao.php, salvar_corretora.php, editar_corretora.php; relatório daytrade |
| Saldos e transferências | contas_financeiras, movimentacoes_financeiras | views/saldos.php; includes/saldos_consulta.php; patrimônio |
| Investimentos nacionais e internacionais | investimentos_nacionais, investimentos_internacionais | views/investimentos.php, investimentos_nacionais.php, investimentos_internacionais.php; patrimônio; dashboard; relatório investimentos |
| Dividendos/proventos | div_datacom e posições privadas dos investimentos | views/dividendos.php, div_datacom.php, div_compra.php, div_valor.php; relatório proventos |
| Análise | analise_acompanhamento, analise_marcacoes e investimentos_nacionais | views/analise.php; views/analise/api.php; includes/analise.php |
| Configuração individual | usuarios e preferências locais da Análise | views/configuracao.php; assets/js/analise-preferences.js |
| Clientes | clientes | Não existe CRUD implementado nesta versão; tabela já tem usuario_id e FK para usuarios. Nenhum endpoint novo foi criado. |

Os sete relatórios ativos usam os mesmos filtros de proprietário, inclusive seletores
de anos, somas e consultas correlacionadas. A exportação existente é a impressão do
próprio relatório (`window.print`); não foram encontrados endpoints ativos de CSV/XLSX.
A importação existente é OFX. Hash do arquivo, FITID, correspondências e nomes
recorrentes são verificados dentro da conta. A trava de importação é por usuário.

`views/taxas.php` redireciona ao cadastro funcional de Daytrade. O relatório antigo
`rel-acoes.php`, que consultava uma tabela inexistente, redireciona ao relatório de
investimentos. Os helpers `relatorio-funcao.php` e `relatorio-template.php` não são
endpoints. Código de `views/BKP`, ferramentas CLI, documentação, SQL, backups,
models/controllers internos e `.git` não são públicos. Os antigos models/controllers
estavam vazios, e não constituem uma camada ativa de persistência.

O inventário de locais de consultas está em `multiusuario-consultas.txt`.

## Autorização e regras para desenvolvimento

`config.php` carrega `includes/seguranca.php` antes das operações. O bootstrap inicia
uma sessão com cookie HttpOnly e SameSite=Lax (Secure quando HTTPS), valida o usuário
no banco e verifica assinatura e validade em cada requisição privada. O login regenera
o identificador, limpa o estado anterior e renova CSRF. A impressão digital da senha
guardada na sessão permite revogar sessões depois de uma redefinição local.

As consultas existentes que já usavam `usuario_id = ?` continuam parametrizadas.
Nos módulos legados, `@mcf_usuario_id` é fixado por `SET @mcf_usuario_id = ?` em uma
conexão nova, com o inteiro validado da sessão. As consultas de leitura têm filtros
explícitos na tabela derivada; UPDATE/DELETE filtram o alvo; INSERT usa esse mesmo
contexto. Não existe reescrita de SQL em tempo de execução, nem aceitação do proprietário
por formulário, URL ou JSON. Sem contexto, os filtros não retornam registros e as
escritas não conseguem satisfazer os campos obrigatórios.

Isso não é Row Level Security nativa do MySQL. Novas consultas também precisam de
filtro explícito e teste de isolamento. Não usar conexões persistentes, alterar a
variável de conexão por entrada do cliente ou passar IDs de terceiros aos helpers.
Não implementar compartilhamento mudando `$_SESSION['usuario_id']`: na próxima etapa,
o ator autenticado e a autorização de acesso ao proprietário deverão ser tratados
separadamente. Hoje a única política permitida é acesso ao próprio proprietário.

Todas as alterações HTTP exigem POST e CSRF. Formulários POST recebem `mcf_csrf`;
a API JSON de Análise preserva `X-CSRF-Token` e seu token de sessão. Os controles CSRF
específicos preexistentes de Cartão, Saldos e Investimentos foram mantidos. Filtros GET
não transportam o token comum na URL. Sair por GET apenas apresenta o formulário;
a sessão é encerrada por POST protegido.

Não há perfil administrador web com acesso financeiro irrestrito. Administração local
de credenciais é feita por ferramenta CLI, fora dos endpoints HTTP. As credenciais de
BRAPI e banco continuam no servidor; configurações internas são bloqueadas no Apache.
Cotações públicas e seu cache podem ser compartilhados. Lançamentos, regras OFX,
taxas, marcações, nomes, preferências e dados de conta são individuais.

## Migração aplicada

Ferramenta: `tools/migrar_multiusuario.php`, migração `20260921_multiusuario`.

- Adiciona `usuario_id NOT NULL`, índice e FK de proprietário às nove tabelas legadas:
  contas, rendas, compras, cartoes, corretoras, corretora_taxas, operacoes,
  ofx_importacoes e cartao_nomes_recorrentes.
- Atribui os registros legados ao proprietário explicitamente informado na CLI.
- Torna a chave dos nomes recorrentes `(usuario_id, chave_descricao)` e a unicidade
  de OFX `(usuario_id, hash_arquivo)`.
- Cria chaves compostas de proprietário em compras, corretoras e contas_financeiras.
- Cria FKs compostas que impedem parcela/compra, taxa/corretora, operação/corretora e
  movimentação/conta de pertencerem a pessoas diferentes.
- Acrescenta FKs de usuário às duas tabelas da Análise, sem alterar as marcações.
- Verifica órfãos e vínculos inconsistentes antes do DDL. Uma segunda execução é
  recusada. DDL de MariaDB faz commit implícito; uma falha exige inspeção/restauração,
  não basta executar ROLLBACK nem repetir o script sem conferir o estado.

O banco de desenvolvimento `financas` foi migrado somente após backup recuperável.
A base `mcf_test_20260921` é descartável e exclusiva dos testes. A base
`mcf_restore_20260921` foi usada para comprovar a restauração e comparar os valores das
19 tabelas originais. Os testes de criação/alteração/exclusão não usam `financas`.

## Criar a segunda conta

1. Acesse `http://localhost/MyCashFlow/` e saia da sessão atual se necessário.
2. Em **Cadastre-se aqui**, informe outro e-mail e uma senha. O cadastro existente
   mantém a regra de acesso inicial por 30 dias.
3. Entre com essa conta. Ela começa sem dados financeiros. Cadastre seus próprios
   saldos, corretoras/taxas e lançamentos; importe seu próprio OFX, se desejar.
4. Use outro perfil do navegador para manter as duas contas conectadas simultaneamente.
   Abas do mesmo perfil compartilham o cookie de sessão. Preferências de Análise são
   separadas por usuário mesmo quando o navegador é o mesmo.

As credenciais atuais da conta ID 1 não foram alteradas. As contas alfa/beta/cadastro
usadas nos testes existem apenas na base descartável. Após a implantação, sessões
antigas precisam fazer login novamente.

## Recuperação de senha

O fluxo anterior expunha o link de redefinição na própria resposta. Como não existe
entrega de e-mail configurada, o endpoint de recuperação agora informa a indisponibilidade,
e links antigos são recusados. Não há token secreto enviado ao solicitante.

O responsável local pode usar `tools/redefinir_senha_local.php ID`, definindo
`MCF_DB_NAME` e fornecendo a nova senha pela entrada padrão, nunca pela URL nem pelos
argumentos do processo. A ferramenta exige 12 a 72 bytes, gera hash e invalida sessões
anteriores. O teste desse fluxo foi feito somente com usuário fictício. Configurar
entrega verificada de e-mail permanece uma pendência explícita.

## Testes reproduzíveis

Com MySQL do XAMPP iniciado e Node disponível:

```powershell
Set-Location C:\xampp\htdocs\MyCashFlow
node tests/multiusuario/run.cjs
```

O comando reinicializa **somente `mcf_test_20260921`**, cria duas contas fictícias,
aplica a migração, inicia PHP isolado em `127.0.0.1:8098`, executa os testes e encerra
esse servidor. Não execute com outro processo usando essa base. Sessões, uploads e
resultados ficam em diretório temporário externo à pasta pública. A BRAPI pode limitar
ou ficar indisponível; o teste do contrato aceita a resposta explícita de indisponibilidade.
Na validação desta entrega houve retorno real de 64 candles.

Resultados: 290 verificações de leituras/autorizações/CSRF/IDs/totais/CRUD; 74 de fluxos
adicionais, vínculos, OFX e cadastro; 12 de sessão, recuperação local, configuração e
rotas internas. Total: **376 aprovadas**. Também passaram os testes PHP e JavaScript
existentes da Análise e a validação sintática PHP.

Verificação de navegador: login/logout das duas contas, dashboards com totais distintos,
posições e marcações próprias, gráfico BRAPI, bloqueio/ocultação persistidos e controles
de médias. Não foi repetida nesta entrega toda a matriz manual de arraste, teclado,
conflitos de abas e dispositivos móveis documentada em `analise.md`. Os arquivos JS,
CSS e vendor da Análise foram preservados; seus testes computacionais passaram.

## Backup e reversão

Os arquivos desta implantação estão fora do Apache, em:

`C:\Users\IFSP\Documents\Codex\2026-09-21\vamos-implementar-o-uso-multiusu-rio\outputs`

- `backup-multiusuario/financas-antes.sql`: dump completo com estrutura, dados,
  triggers, rotinas e eventos.
- `backup-multiusuario/codigo-antes.zip`: código anterior, inclusive alterações locais
  que já existiam antes desta tarefa.
- `backup-multiusuario/SHA256.txt`: integridade dos arquivos.
- `preservacao-dados.json`: comparação por hash dos valores de todas as colunas
  originais das 19 tabelas, sem expor seus valores.
- `arquivos-alterados.json`: arquivos novos e alterados da implantação.
- `recuperar-backup.ps1`: restaura em uma nova base e extrai o código para uma nova
  pasta privada; não sobrescreve a instalação em uso.

Para reverter, interrompa o Apache e faça primeiro um novo backup do estado atual se
houver registros posteriores à implantação. Execute o script de recuperação; confira
o código extraído e a base recuperada. Aponte a configuração do código recuperado para
a nova base, mantenha o bloqueio de backups/arquivos internos da `.htaccess`, e substitua
a pasta pública somente depois de mover a instalação atual para um diretório privado
fora de `htdocs`. Reinicie o Apache e faça login novamente. O código anterior não tem
as garantias multiusuário desta entrega e não deve ser usado por duas contas isoladas.

Restaurar o snapshot perde alterações feitas depois dele se forem descartadas sem
reconciliação. Não remova simplesmente as colunas de proprietário depois de começar a
usar duas contas: isso mistura os dados. O caminho de reversão é o par código+banco
consistente do backup.

## Extensão de compartilhamento (22/09/2026)

A descrição histórica acima considera o uso individual. O compartilhamento agora permite autorização explícita por módulo, com visualização ou edição, e visão conjunta sem alterar a propriedade dos registros. A identidade da sessão continua sendo do ator; apenas uma requisição delegada validada usa o titular autorizado como escopo financeiro. Veja [Compartilhamento](compartilhamento.md) para regras, limites e testes atuais.
