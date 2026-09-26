# Central de Relatórios - primeira entrega financeira

Atualizado em 26/09/2026. Escopo desta entrega: `views/relatorios/financeiro.php` e PDF. Os demais relatórios existentes permanecem disponíveis; a ampliação deles é a próxima etapa.

## Diagnóstico e decisões

O sistema PHP 8.2/XAMPP já tinha sete relatórios independentes, filtro anual, Chart.js carregado por CDN e impressão do navegador. A visão financeira somava `rendas` e `contas`; não existia um serviço comum para cálculo e exportação. A autorização de compartilhamento já era separada por módulo.

A interface de filtros foi inspirada funcionalmente em [Actual Budget Custom Reports](https://actualbudget.org/docs/reports/custom-reports/), sem copiar código. Optamos por manter PHP e a autenticação existente, em vez de adicionar um serviço externo.

- `includes/relatorios_core.php`: datas, intervalos, centavos inteiros, agrupamento, situação e diferenças. Sem banco e sem dependência de sessão, reutilizável em outros relatórios.
- `includes/relatorios_dados.php`: consultas preparadas, seleção de titulares, transação somente leitura com snapshot consistente, limite explícito de 6.000 registros consultados incluindo comparações. Nunca retorna um resultado truncado como completo.
- `includes/relatorios_view.php`: indicadores, tabelas, SVG e detalhamento compartilhados entre tela e PDF.
- `includes/relatorios_pdf.php`: documento A4 paisagem, Dompdf, cabeçalhos repetidos, numeração de páginas e gráficos vetoriais.
- `financeiro-pdf.php`: POST com CSRF, consulta armazenada na sessão e revalidação das permissões. Não aceita HTML ou totais enviados pelo navegador.

## Dependências e licenças

[Dompdf](https://github.com/dompdf/dompdf) 3.1.6, pacote oficial publicado no GitHub, licença LGPL-2.1. Incluído em `includes/vendor/dompdf`, com arquivos de licença e manifesto Composer de suas dependências. Não exige assinatura, Composer instalado em produção ou serviço remoto. PHP DOM e mbstring estão presentes no XAMPP; os gráficos SVG foram testados sem GD. Novas imagens raster poderão exigir GD.

Dependências do pacote: php-font-lib 1.0.2, php-svg-lib 1.0.2, masterminds/html5 2.10.1 e sabberworm/php-css-parser 8.9.0. As licenças originais estão preservadas no pacote. A versão distribuída não foi modificada.

Foram avaliados Dompdf e [jsPDF](https://github.com/parallax/jsPDF). Dompdf foi escolhido por gerar no servidor, reutilizar HTML/tabelas e concentrar a revalidação de acesso. SVG gerado localmente evita depender de um navegador headless, de captura raster do gráfico ou de CDN. Recursos remotos, PHP e JavaScript dentro do PDF estão desabilitados, e o acesso local do renderizador está limitado ao diretório do pacote. A regra existente do Apache bloqueia acesso HTTP a `includes`.

## Uso e semântica

- Semana inicia na segunda-feira. Mês, trimestre, semestre e ano usam a data de referência. Personalizado permite atravessar anos. O intervalo máximo é dez anos; agrupamento diário é limitado a 367 dias.
- 36 meses inclui o mês da referência e os 35 anteriores, encerrando na data de referência. Acumulado anual começa em 1º de janeiro e encerra na referência.
- Período anterior é uma janela imediatamente anterior com a mesma quantidade de dias, identificada na tela. Ano anterior e três anos mantêm o mesmo intervalo de calendário; 29/02 é ajustado a 28/02 nos anos não bissextos.
- Filtros: situação atual, tipo único/parcelado/recorrente, natureza pessoal/conjunta das despesas e classificação regular/extra das receitas.
- Pendentes inclui vencidos. Vencidos significa ainda não pago/recebido e data anterior ao dia da consulta.
- Barras verticais, horizontais, empilhadas (incluindo sinal), linhas e área. Linhas/área com comparação entre três anos em uma janela dentro do mesmo ano também mostram saldo mensal com uma linha por ano. Não conecta lacunas sem registros.
- Gráfico, tabela ou ambos. Os lançamentos estão em uma seção expansível. Links nos agrupamentos abrem essa seção filtrada; “Mostrar todos” limpa o filtro de detalhe. Esse filtro de navegação não altera o relatório nem o PDF.
- Diferenças são selecionado menos comparado, em cada um dos seis indicadores. Percentual usa o módulo da base; base zero ou ausência de dados é explicitamente indisponível. Ausência de registros não é apresentada como zero registrado.
- A consolidação exige autorização ativa de receitas E despesas de cada titular. A participação individual é mostrada junto do consolidado. Perfil com apenas um módulo não aparece nesta visão financeira, mas continua acessível na consulta autorizada do respectivo módulo.

## PDF e proteção

Resumido inclui indicadores, comparações, participação individual e a apresentação escolhida. Detalhado acrescenta todos os lançamentos dos períodos incluídos. Gráficos extensos são divididos em blocos, mantendo rótulos legíveis.

Até cinco consultas ficam na sessão por até 30 minutos. Exportar reproduz o snapshot exibido, incluindo sua data de geração, sem recalcular com registros alterados depois. A revogação é verificada novamente no POST; snapshots não são intercambiáveis entre sessões. Relatório expirado exige atualização. Respostas são privadas e `no-store`; não há arquivos financeiros em diretório público. Um PDF já baixado pelo usuário não pode ser revogado remotamente.

## Limitações dos dados atuais

1. `contas.categoria` contém pessoal/conjunta; não existem categorias de finalidade como alimentação/moradia nesta fonte.
2. Vencimento da despesa e data cadastrada da receita são as bases temporais. Os flags paga/recebido representam o estado atual, sem data efetiva. Portanto, “realizado” por mês não é fluxo de caixa histórico na data de liquidação. Competência não é inventada.
3. Cartões e movimentações financeiras não são somados. Isso evita introduzir dupla contagem de compra e fatura ou transferências como receitas por uma junção automática. Registros manuais duplicados ou transferências cadastradas como renda não podem ser identificados confiavelmente sem metadados.
4. Despesas conjuntas têm apenas uma classificação, sem vínculo entre registros de titulares distintos. A consolidação soma registros, não deduplica por nome/valor. A limitação aparece na tela e no PDF; identificação segura de uma despesa compartilhada depende de evolução do cadastro.
5. Valores desta fonte são tratados como BRL. Não se misturam posições e moedas de investimentos nem se infere patrimônio histórico.

## Validação reproduzível

- `php tests/relatorios.test.php`: 34 verificações de datas, virada de ano, três anos, corte anual, bissexto, meses vazios, zero registrado, sinal, base zero, precisão e filtros inválidos.
- `node tests/relatorios-http.cjs`: usa SOMENTE `127.0.0.1:3307/mcf_reports_test`, em instância descartável com diretório próprio, reinicializada por `tests/relatorios-reset.cjs`. 40 verificações de autenticação, CSRF, permissões parciais, leitura compartilhada, revogação, isolamento de snapshots, XSS, filtros e PDF em cinco gráficos e três apresentações. `--serve` mantém o servidor de QA na porta 8099. O reset recusa implicitamente qualquer banco/porta externo: os destinos são literais e não aceitam substituição por argumentos.
- `php tests/relatorios-pdf.php`: gera seis PDFs fictícios com três anos e 576 lançamentos; nenhum dado real.
- `python tests/relatorios-pdf-qa.py`: confere totais, todos os lançamentos, cabeçalhos repetidos, páginas e snapshot antes de uma alteração; renderiza páginas com Poppler para inspeção visual.
- `node tests/multiusuario/run.cjs`: regressões completas existentes passaram. A verificação de isolamento financeiro foi ajustada para olhar os indicadores, pois valores dos eixos podem coincidir numericamente com o total de outra fixture sem representar vazamento.
- Sintaxe PHP e JavaScript verificada. PDFs renderizados e inspecionados, inclusive o detalhado multipágina. Após autorização explícita do usuário, a interface autenticada foi conferida em desktop e em 390 × 844: filtros, três anos, abertura/limpeza do detalhamento, download pelo botão, ausência de erros JavaScript e contenção de tabelas/gráficos. Corrigida largura móvel; gráficos usam rolagem interna para manter rótulos legíveis.

## Próximas etapas

1. Aplicar os serviços comuns a gastos/receitas/cartões; reconciliar compra, parcela e pagamento antes de consolidar módulos. Criar categorias de finalidade e datas efetivas opcionais com migração preservadora.
2. Categoria × mês/ano, agenda, parcelas futuras, previsto × realizado e participação familiar com identificador de despesa compartilhada e regras explícitas de rateio.
3. Distribuições não negativas podem oferecer pizza/rosca; categoria × mês permite mapa de calor; dispersão só para pares de métricas com significado. Não oferecer gráficos inadequados apenas para completar uma lista.
4. Investimentos/proventos/day trade: agregar por titular e moeda, ativo × proventos, proventos × despesas, corretora × resultado × custos; patrimônio histórico apenas com fontes suficientes. Não usar posição atual como rentabilidade histórica.
5. Futuras planilhas: área de pré-importação, mapeamento separado de finalidade/natureza e datas, moeda explícita, identificador externo por origem/titular, hash do arquivo e chave idempotente por linha, prévia de duplicatas e confirmação após reconciliação. Não descartar registros distintos só por terem mesmo valor/data. Nenhuma importação real foi executada.

## Implantação e reversão

Sem migração de banco nesta etapa. Copiar somente os arquivos listados no manifesto da entrega e o pacote Dompdf, após backup do financeiro anterior. Configuração, credenciais, dados e regras de compartilhamento existentes permanecem preservados. Para reverter a interface, restaurar `views/relatorios/financeiro.php` do backup; os arquivos novos podem permanecer inacessíveis por navegação até serem retirados em manutenção. Nunca restaurar banco para reverter esta entrega de código.

### Estado da entrega em 26/09/2026

Implementação e testes na cópia `work-reports`; não aplicada em `C:\xampp\htdocs\MyCashFlow`. Durante a verificação final, o MySQL principal da porta 3306 deixou de estar disponível. Uma tentativa de inicialização com a configuração existente também falhou; o log registrou LSNs de páginas InnoDB à frente do log e uma assertion/crash. A causa não foi determinada. Não foram executados reparos, restaurações, remoções ou cópias sobre os arquivos de dados principais.

Para concluir a validação sem depender do servidor principal, foi inicializada uma instância nova na porta 3307, vinculada apenas a 127.0.0.1, com diretório `qa-reports-mysql` no workspace. Os testes HTTP finais passaram nessa instância. O banco real não foi importado para os testes. A recuperação do servidor principal é uma atividade distinta, que precisa preservar os arquivos existentes e ser tratada antes da implantação.

O pacote está em `instalacao/relatorios-20260926`; `manifesto-relatorios.json` registra hashes atuais e anteriores. `deploy-relatorios.ps1` sem parâmetros apenas confere o pacote e detecta mudanças concorrentes. `-Apply` exige o MySQL principal operacional, cria backup privado dos arquivos substituídos e verifica cada cópia. Não contém alterações de banco.

### Retomada após restabelecimento do serviço

O usuário informou que o serviço voltou e autorizou o login fictício. MySQL principal, estrutura necessária e hashes anteriores foram conferidos. Os 40 testes HTTP foram repetidos na instância isolada; a conferência visual foi concluída e o CSS móvel corrigido. `implantacao-relatorios.json` registra a aplicação e o backup; `resultados/relatorios/dados-principal-verificacao.json` registra a comparação das 23 tabelas antes/depois. Consulte `ENTREGA-RELATORIOS.md` no workspace para o estado final.
