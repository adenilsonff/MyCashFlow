# Perfil individual — 22/09/2026

## Uso e regras

Acesse Configuração → Meu perfil, o link no cabeçalho ou `/MyCashFlow/views/perfil.php`.
O formulário de dados altera nome e e-mail conjuntamente; qualquer erro impede ambos.
A alteração de e-mail exige a senha atual. O formulário separado de senha exige senha
atual, nova senha e confirmação. Nenhuma operação aceita um proprietário do cliente.

O nome tem 2 a 100 caracteres UTF-8, sem controles nem delimitadores HTML. Nomes
iguais são permitidos. Toda saída é escapada, inclusive nomes legados. Contas antigas
ficam com `nome=NULL`, continuam entrando e exibem o e-mail como alternativa.

E-mails têm até 100 bytes e formato validado pelo PHP. Login, cadastro e atualização
removem espaços externos e ignoram caixa. Novos valores são gravados em minúsculas.
O índice único gerado usa `LOWER(TRIM(email))`, mantendo os valores antigos intactos.
A comparação utiliza a collation existente `utf8mb4_general_ci`; não aceita e-mail
internacionalizado pelo validador atual. A migração recusa duplicidades e espaços de
controle legados antes de alterar a estrutura. Não há declaração de e-mail verificado,
envio de mensagem ou nova dependência de serviço de e-mail.

Cadastro e troca exigem 12 a 72 bytes de senha, sem NUL, usando `password_hash` e
`password_verify`. Senhas antigas continuam válidas no login. A ferramenta CLI local
existente permanece compatível. Ao trocar a senha, preserva-se a impressão digital
`auth_version` já utilizada pelo sistema: outros acessos falham na próxima requisição
protegida. A sessão atual recebe novo ID, novo CSRF e nova impressão digital. Tokens
específicos dos módulos são descartados e recriados ao reabrir as páginas. Formulários
já abertos podem precisar ser recarregados. Se renovar o ID falhar, exige novo login.
Requisições já autorizadas e em execução não podem ser desfeitas retroativamente.

As atualizações usam transação, trava de linha e revalidação da autenticação sob a
trava. Só `nome`, `email` ou `senha` entram nos UPDATEs parametrizados. ID, assinatura,
validade e criação não podem ser alterados. A identidade financeira permanece estável.

## Arquivos

- `includes/perfil.php`: validação e atualização transacional.
- `views/perfil.php`, `assets/css/perfil.css`: página integrada ao layout.
- `includes/seguranca.php`: atualiza nome/e-mail da sessão a cada acesso protegido.
- `includes/header.php`, `includes/menu.php`, `views/dashboard.php`,
  `views/configuracao.php`: identificação consistente e navegação.
- `views/login/login.php`, `views/login/register.php`: login normalizado e cadastro com nome.
- `tools/migrar_perfil.php`, `migrations/20260922_perfil.sql`: migração restrita a usuários.
- `tests/multiusuario/perfil.cjs`, `reset.cjs`, `run.cjs`, `extended.cjs`: testes e fixtures.

Não há alterações no código, vendor, estilos ou preferências locais da Análise.
BRAPI, Lightweight Charts 5.2.0, preço médio, operações, linhas/notas, bloqueios,
visibilidade e médias continuam vinculados ao mesmo ID.

## Migração e verificação

Faça backup fora de `htdocs` antes de executar. Com `MCF_DB_NAME` explicitamente
definido, execute `php tools/migrar_perfil.php --check` e depois `--apply`.
A migração acrescenta nome anulável, e-mail normalizado gerado e índice único.
Não executa a migração multiusuário, nem modifica registros existentes. A segunda
execução é recusada. DDL MariaDB faz commit implícito: inspecione em caso de falha.

Testes: `node tests/multiusuario/run.cjs`, em banco descartável `mcf_test_20260921`.
Esse comando REINICIALIZA apenas essa base e testa as migrações a partir do esquema
histórico. A migração multiusuário é executada somente nessa fixture descartável.
Nunca altere o nome da base de teste para `financas`.

Resultado: 376 verificações existentes e 85 novas aprovadas (461). As novas cobrem
duas contas, conta legada, cadastro, autenticação, CSRF, IDs manipulados, campos internos,
XSS, e-mail inválido/duplicado, ausência de atualização parcial, senha atual, confirmação,
limites de senha, rotação/revogação de sessão e comparação integral das tabelas privadas.
Passaram também testes PHP/JavaScript da Análise e validação sintática PHP.
Navegador: login, identificação no dashboard e página de perfil conferidos na base
fictícia. A matriz manual completa de arraste, dispositivos móveis e conflitos da
Análise não foi repetida; seus arquivos permanecem idênticos.

## Backup e recuperação

Pasta privada: `C:\Users\IFSP\Documents\ChatGPT\MYCASHFLOW\backups\perfil-20260922`.
Contém `codigo-antes.zip`, `financas-antes.sql`, `SHA256.json`, hashes dos dados e
registro de restauração verificada. O dump inclui estrutura, dados, triggers, rotinas
e eventos. Foi restaurado em base separada, com igualdade dos valores das 19 tabelas.
Não copie os backups para a pasta pública; eles contêm dados e configuração privada.

Para uma reversão de código, interrompa temporariamente o acesso no Apache e faça
novo backup. Extraia o ZIP em pasta privada, compare com a instalação e restaure
somente os arquivos substituídos listados no manifesto da implantação. Remova do
diretório público a nova página de perfil e seu helper/estilo/ferramenta de migração.
Mantenha as colunas novas no banco: o código anterior tolera essas colunas adicionais,
e isso preserva nomes e credenciais atualizados depois da implantação. Não restaure
senhas antigas inadvertidamente. Retome o Apache após conferir os arquivos.

Para restauração completa do snapshot, crie uma NOVA base vazia, por exemplo
`mcf_recuperacao_perfil_20260922`, e importe `financas-antes.sql` pelo cliente MySQL
(`mysql -u root NOME_DA_NOVA_BASE`, depois `source C:/.../financas-antes.sql`). Extraia
o ZIP para uma NOVA pasta privada e configure essa cópia para a base restaurada.
Confira os dados antes de substituir a instalação. Preserve a `.htaccess` do ZIP.
O snapshot não inclui alterações posteriores: reconcilie-as antes da troca.
Não execute DROP/DELETE em `financas`, nem reaplique `migrar_multiusuario.php`.

O registro da implantação e a comparação de arquivos ficam na pasta privada do
projeto em `implantacao-perfil.json`; consulte-o para saber se houve instalação
efetiva no XAMPP. A presença desta documentação na cópia de trabalho não comprova
implantação. Testes mutáveis nunca são executados no banco principal.
