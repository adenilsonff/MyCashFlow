# Menu Análise

Versão inicial: 19/09/2026. Mercado nacional (ações, FIIs, ETFs e BDRs).

## Instalação

Execute `php tools/instalar_analise.php` com o banco configurado em `config.php`.
A migração adiciona `analise_acompanhamento` e `analise_marcacoes`, sem alterar
os lançamentos existentes. Não executar o instalador pelo navegador.

O menu fica em `views/analise.php`. A biblioteca local é Lightweight Charts 5.2.0;
os arquivos LICENSE e NOTICE acompanham a distribuição e a atribuição é exibida.

## Comportamento

- Carteiras e operações são lidas do histórico nacional do usuário autenticado.
- Custo médio usa as mesmas regras de `carteiras_posicoes.php` e é recalculado ao atualizar.
- Linhas horizontais e notas são salvas por usuário e ativo; edição usa versão para
  impedir sobrescrita silenciosa por outra aba.
- Notas são texto puro. Nenhum HTML do usuário é executado.
- Adicionar acompanhamento não cria investimento; removê-lo preserva as notas e operações.
- Semanal e mensal são agregações OHLCV dos pregões disponíveis. Períodos nas pontas
  podem ser parciais. Não são criados pregões artificiais.
- Intervalos de minutos consultam a fonte e podem ser bloqueados pelo plano.
  Cada intervalo bloqueado é desabilitado para o ativo durante a visita.
- A aplicação só conhece a data das operações; marcadores intradiários não indicam
  o horário real de execução. A tabela mantém os preços e datas originais.
- Preços são OHLC fornecidos pela API, sem misturar `adjustedClose` no candle.
  Não há reconciliação automática de eventos corporativos, mudanças de código ou custo fiscal.
- O histórico disponível não é garantia de feed em tempo real ou aptidão para execução de ordens.

## Marcações no gráfico (20/09/2026)

- Clique em **Desenhar linha** e depois no gráfico de preços. Confirme o preço,
  título e cor; novas linhas começam amarelas. A gravação ocorre ao salvar o formulário.
- Arraste o botão **↕** da linha e solte para salvar automaticamente o novo preço,
  arredondado a centavos. **Esc** cancela o movimento antes de soltar.
- Use **Editar** na lista ou Enter no botão da linha para informar um preço exato.
  O formulário mantém o suporte a quatro casas decimais.
- Cada linha informa a diferença em reais e porcentagem entre seu preço e o último
  fechamento exibido. A comparação não usa uma cotação em tempo real.
- A linha de preço médio é automática e não pode ser arrastada. Notas continuam na lista.
- Em conflito com outra aba, recupera-se a versão salva sem sobrescrevê-la.
  Em falha de conexão, a interface informa que o salvamento não foi confirmado.
- Testados clique, arraste, persistência após recarregar, cancelamento, teclado,
  conflito entre abas, falha de rede, diferenças positivas/negativas e layout móvel.

## Preferências e visibilidade (20/09/2026)

- **Bloquear / Desbloquear** em cada linha impede ou libera o arraste. O formulário
  **Editar** continua disponível para alterações intencionais. Não é uma permissão de acesso.
- **Ocultar / Mostrar** controla uma linha; **Minhas linhas** controla o conjunto.
  As marcações permanecem na lista e no banco de dados. O preço médio usa sua própria opção.
- Intervalo, período de histórico, tipo de gráfico e opções de exibição são restaurados
  ao abrir a página. Intervalos intradiários mantêm o período de cinco dias.
- Preferências, bloqueios e visibilidade usam armazenamento local por ID de usuário.
  São específicos deste navegador e não sincronizam entre dispositivos. Limpar os
  dados do navegador restaura os padrões; preços e notas permanecem salvos na conta.
- Armazenamento bloqueado ou inválido não interrompe o gráfico. Se não for possível
  persistir uma escolha, a página informa que ela só será mantida enquanto estiver aberta.
- Validados em navegador: recarga, bloqueio/desbloqueio, edição manual de linha bloqueada,
  ocultação individual/conjunta, isolamento entre contas, armazenamento inválido/bloqueado,
  ausência de mudanças nas marcações do banco e tela móvel sem rolagem horizontal.

## Segurança e cache

`views/analise/api.php` exige sessão válida e assinatura ativa, sempre obtém o usuário
da sessão e aplica seu ID em todas as operações privadas. Escritas usam JSON e token CSRF.
Nenhum token BRAPI é enviado ao navegador. Não aceita URL de provedor enviada pelo cliente.
Preço, texto, ticker, cor, categoria e intervalo são validados no servidor.

O cache em diretório temporário do servidor armazena somente histórico público,
com chave por token/símbolo/intervalo/janela. Consultas diárias duram 30 minutos em cache;
intradiárias, cinco minutos. Falhas aguardam um minuto antes de nova tentativa.
Em falhas temporárias, o último resultado com até 48 horas pode ser exibido com aviso.
Autenticação e bloqueio de plano não reutilizam esse fallback.

O servidor precisa de mysqli, cURL, mbstring e permissão de escrita no diretório temporário.
A aba libera a sessão antes de buscar o provedor e ignora respostas de seleções antigas.

## Verificações reproduzíveis

- `php tests/analise.test.php`
- `node tests/analise-core.test.cjs`
- `php -l includes/analise.php`
- `php -l views/analise/api.php`
- `node --check assets/js/analise.js`

Os testes HTTP e de navegador de implantação foram executados em banco separado
com dois usuários fictícios: autenticação, CSRF, isolamento, criação/edição/exclusão,
conflito de versão, acompanhamento, dados reais, restrição de plano, troca rápida de
ativo, tela desktop/mobile e ausência de erros JavaScript.

## Evolução

Ficam para etapas futuras: linhas de tendência por dois pontos, carteiras internacionais,
histórico além do plano, compartilhamento e consolidação do casal. As marcações desta
versão são privadas; ter a página aberta não concede acesso à carteira de outro usuário.

## Médias móveis (20/09/2026)

O painel **Médias móveis** oferece três médias independentes, inicialmente ocultas:
MME 9, MME 21 e MMS 50. Cada uma permite escolher tipo, período inteiro entre 1 e 500,
cor e visibilidade. As escolhas usam as preferências locais por usuário.

Os cálculos usam os fechamentos dos candles exibidos após a agregação do intervalo.
MMS é a média aritmética da janela; MME usa fator 2/(n+1), iniciada pela MMS dos
primeiros n fechamentos. Nenhum valor é desenhado antes de completar n candles.
A MME depende do início do histórico e pode diferir de plataformas com mais dados.
Não há consulta adicional à API, preenchimento artificial de candles ou alteração de
operações. Ao trocar o ativo ou falhar o histórico, os indicadores antigos são limpos.

Validação: valores conhecidos de MMS/MME, janela inicial, período 1, parâmetros
inválidos, lacunas, troca diário/semanal, visibilidade, cores, persistência, histórico
insuficiente, limpeza em falha, desktop/mobile e regressão das marcações por arraste.

## Análise delegada (22/09/2026)

Com autorização de edição em Análise, o destinatário pode gerenciar linhas e notas do titular. As preferências locais usam uma chave específica para ator e titular. Operações e preço médio só são incluídos quando investimentos também estiver autorizado. A consulta somente leitura apresenta linhas/notas em tabela. Veja [Compartilhamento](compartilhamento.md).
