# Comunicação visual — 06/10/2026

Receitas e despesas usam etiquetas textuais: única (cinza), parcelada (âmbar, preservando número da parcela) e recorrente (roxo). Despesas também distinguem pessoal (azul) e conjunta (verde). Receitas identificam regular (azul) e extra (verde). Não são novas categorias nem alterações nos dados.

O painel, as duas carteiras e patrimônio exibem um aviso quando as cotações usadas pela tela estão indisponíveis ou desatualizadas. O detalhamento identifica cada código/moeda e a consulta bem-sucedida dos preços salvos. O link Tentar novamente faz GET da mesma tela, preservando filtros e contexto compartilhado; não repete uma operação POST. Não exibe mensagens técnicas ou credenciais do provedor. Mantém o cache e os intervalos de nova tentativa existentes: clicar rapidamente pode continuar mostrando o mesmo aviso.

Em Análise, um aviso destacado diferencia histórico indisponível, erro de conexão e histórico salvo. O botão reutiliza o fluxo de consulta existente; o aviso some após recuperação. A data é exibida no fuso de Brasília. A aplicação não transforma falha em zero nem cria preços fictícios.

O gráfico do início usa Chart.js 4.5.1 distribuído localmente, com licença MIT preservada em assets/js/vendor/chartjs-4.5.1/LICENSE.md. Fonte: https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.min.js, distribuição indicada pela documentação oficial https://www.chartjs.org/docs/latest/getting-started/installation.html. Cotações continuam dependendo da rede; a alteração permite renderizar o gráfico de receitas/despesas com os dados locais sem acessar CDN. Não altera os relatórios clássicos.

Validação: php tests/comunicacao.test.php; sintaxe PHP e JS; testes de navegador no pacote privado etapa2-comunicacao, com banco descartável na porta 3307 e rede externa bloqueada. Testados desktop, 390 px, falha de histórico, falha de rede, cache, recuperação, preservação de parcelas e cotações ausentes.

Não há migração de banco. Implantar apenas a lista do manifesto; nunca copiar configuração, cache fictício, sessões ou banco de teste. Backups dos arquivos anteriores ficam fora da pasta pública do site.
