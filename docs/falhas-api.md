# Falha de APIs e última cotação salva

As telas que usam o aviso de mercado (Início para o câmbio utilizado, carteiras nacionais/internacionais e Patrimônio) destacam quando não foi possível atualizar cotações. O aviso mostra quais dados falharam, último valor válido e horário da consulta bem-sucedida. Valores calculados com preços salvos são estimativas desatualizadas, não valores atuais garantidos.

- Preços válidos são guardados em `includes/cache/mercado`, protegido contra acesso pela web pelas regras existentes de `includes`.
- Falhas de conexão, autorização, limite de consultas, servidor ou resposta sem preço válido não substituem uma cotação salva por zero.
- O último preço não desaparece por completar sete dias. Acima desse prazo, ganha um destaque adicional de antiguidade. Continua identificado como desatualizado.
- Sem preço válido anterior, a cotação permanece indisponível. Não há invenção de preço e os cálculos existentes continuam tratando ausência como ausência.
- O horário da consulta bem-sucedida não é renovado por uma falha. Uma nova resposta válida atualiza a cópia e remove o alerta.
- O intervalo normal continua em três minutos e a espera após falhas em um minuto, para não sobrecarregar as fontes. “Tentar novamente” respeita essa espera.
- A implementação não troca credenciais, não modifica lançamentos e não registra falhas como notificações financeiras persistentes.

O histórico do menu Análise também fica nessa pasta, em `analise-v1`. Para a mesma combinação de ativo, período e intervalo, uma indisponibilidade permite mostrar os últimos candles salvos, identificados com data e aviso, inclusive após dois dias. Restrições explícitas de plano/período e ausência de cobertura mantêm suas mensagens específicas.

Na implantação, arquivos JSON válidos da antiga pasta temporária são copiados para a pasta permanente. A pasta temporária é preservada. Essa cópia é de mercado, separada do backup individual de lançamentos. Para uma mudança de máquina, preserve também a pasta de cache se quiser levar as últimas cotações.

Teste sem rede: `php tests/mercado-falhas.test.php`. Abrange falha de conexão, HTTP 401/403/429/500/503, cache antigo, primeira consulta sem dados, moedas, cripto, recuperação e histórico salvo. A persistência exige permissão de escrita na pasta pelo processo PHP.
