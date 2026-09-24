# Testes de criptomoedas e gráfico anual — 24/09/2026

## Execução

Com o MySQL ativo, execute `node tests/multiusuario/run.cjs`.
O comando reinicializa somente `mcf_test_20260921`; não executar se outra tarefa estiver usando essa base descartável.

A preparação agora também aplica `tools/migrar_cripto.php` à fixture histórica. A migração do banco principal não é executada pelo teste.

## Cobertura adicionada

- `tests/cripto.test.php`: cálculos fracionários, oito casas decimais, venda parcial/integral, venda excedente, preço pequeno, ETF e cripto com ticker igual, consolidação por titular, moeda incompatível, cotação ausente/inválida e cache. Usa respostas simuladas para os cenários de mercado, sem depender do preço real.
- `tests/multiusuario/cripto.cjs`: compra/venda pela interface HTTP, persistência decimal no MySQL, validação, CSRF, correções e versões antigas, isolamento, relatórios, visão conjunta, permissões granulares, auditoria e revogação. As consultas do sistema ao provedor continuam sujeitas à disponibilidade da API; os testes não exigem uma cotação específica para registrar as operações.
- `tests/multiusuario/dashboard-anual.cjs`: janeiro e dezembro, meses vazios, limites entre anos, isolamento entre contas e entrada inválida no filtro.

O cenário de cadastro sem permissão de cadastrar também confere a ausência de nova linha no banco. Atualmente esse caminho pode ser negado pelo trigger e renderizar erro na página com HTTP 200, enquanto outras negações retornam 403. O teste distingue falha apresentada de cadastro efetivamente gravado.

## Gráfico Receitas x Despesas

O gráfico da página inicial passa a mostrar janeiro a dezembro, começando pelo ano atual. O campo **Ano do gráfico** permite consultar outros anos. O filtro afeta apenas esse comparativo; os cartões de resumo e a composição da fatura mantêm seus períodos existentes.

O cálculo permanece baseado nos lançamentos de receitas e contas/despesas, incluindo valores registrados em meses futuros; não é um indicador exclusivamente de valores recebidos/pagos. Meses sem lançamentos aparecem com zero. Não altera a inclusão de cartões no cálculo anterior.

Não há mudança de schema necessária para o gráfico anual. Esta entrega instala testes e o ajuste da página, sem executar migração no banco principal.

## Resultado da validação

Passaram 47 verificações HTTP/SQL de cripto, 25 de cálculo/cotações/cache e 16 do gráfico anual. A suíte completa também passou nas verificações anteriores de multiusuário, compartilhamento, perfil, cálculos, auditoria e Análise.

O gráfico foi renderizado no Edge com Playwright em desktop e tela de 390 px, verificando 12 meses, troca de ano e ausência de erros JavaScript/rolagem horizontal. Para contornar o bloqueio de rede do navegador de teste, a requisição à Chart.js foi atendida com uma cópia obtida do mesmo URL do CDN. A instalação continua usando o CDN existente.
