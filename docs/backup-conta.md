# Backup da minha conta

Abra Configuração > Backup da minha conta e clique em Baixar backup da minha conta. O arquivo JSON contém os registros das 18 tabelas de cadastros, finanças, análises e preferências de módulos, filtrados pelo usuário autenticado. O titular não é recebido de um campo do formulário. A leitura usa uma transação consistente, sem alterar o banco.

Guarde o arquivo em local privado, preferencialmente em outra unidade/dispositivo. Ele contém dados pessoais e financeiros sem criptografia. Não publique no Git nem coloque na pasta pública do site. O servidor não mantém um arquivo de download público. Este backup não substitui o backup administrativo completo da instalação.

Não inclui credenciais, sessões, permissões de compartilhamento, histórico de alterações, arquivos OFX originais, código do sistema, cotações em cache ou preferências salvas apenas no navegador. O controle de importações OFX é incluído. Nome/e-mail de origem são metadados; não substituem os dados de login do destino.

## Recuperação

1. Instale a mesma versão do MyCashFlow e crie uma conta nova.
2. Entre nessa conta sem cadastrar dados nem salvar preferências de módulos.
3. Abra Backup da minha conta, escolha o JSON e clique em Conferir arquivo.
4. Confira as contagens, informe a senha atual e confirme a recuperação na conta conectada.

A conferência expira após dez minutos. A confirmação revalida a estrutura e exige conta vazia. Os IDs são gerados novamente; relações com compras, corretoras, contas e origens conhecidas são remapeadas. A importação inteira ocorre em transação serializável; qualquer falha desfaz todos os registros importados. Dados existentes nunca são substituídos. O histórico de auditoria criado pelo sistema descreve a nova importação, sem falsificar autores anteriores.

Limites: 10 MB e 100.000 registros; o upload também depende dos limites do PHP do servidor. Arquivos com estrutura de outra versão ou vínculos de origem ambíguos/não reconhecidos exigem recuperação assistida. Em particular, origem_id de investimentos não identifica a carteira nacional/internacional; a recuperação recusa essa ambiguidade. As telas atuais de movimentação criam origens manuais sem esse vínculo.

## Validação

41 verificações HTTP com dados exclusivamente fictícios em 127.0.0.1:3307/mcf_reports_test: autenticação, CSRF, isolamento, ausência de credenciais, precisão decimal, validação de arquivo, proteção da conta com dados, rollback integral, isolamento da prévia por sessão, recuperação, remapeamento dos vínculos, contagens das 18 tabelas e preservação da origem e de terceiros. Preferências de módulos incluídas no teste. Tela inspecionada em desktop e largura móvel de 390 px, sem erros JavaScript observados.
