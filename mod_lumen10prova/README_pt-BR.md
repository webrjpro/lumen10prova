> **Atualização 09/11/2026:** Moodle 5.1 e 5.2; atividade 1.3.0 e regra complementar 1.2.0. Os textos abaixo preservam o histórico operacional. A documentação consolidada e a instalação atual estão no [README em inglês](README.md).

# Lumen10 Prova — operação e manutenção

Novo conjunto independente, desenvolvido para Moodle 5.1 em 04/10/2026. `mod_lumen10prova` aparece em **Adicionar uma atividade ou recurso**. A regra complementar `quizaccess_lumen10prova` aplica os controles às URLs e endpoints nativos do questionário vinculado. Ambos precisam estar instalados; não substituir arquivos do core nem dos plugins anteriores.

## Arquitetura e notas para certificados

Cada atividade cria e administra um questionário nativo do mesmo curso, acessível pelo link da prova e fora da listagem normal de atividades. O Question Engine preserva respostas, versões das questões, sorteio, correção e tentativas. A camada Lumen10 guarda autorizações, aceite, identificação por candidato, comunicação e auditoria.

Existe **um único item de nota**, nativo do Quiz, com o nome da prova. A atividade principal não cria uma segunda nota, evitando duplicar o peso na média do curso. O plugin Lumen10 Certificados já consulta o Livro de Notas e o total do curso: configure nele o curso/categoria de origem e os critérios institucionais. A instituição define pesos e aprovação no Livro de Notas; o módulo não emite certificados automaticamente nem altera o Certificados ou o Prontuário.

## Configuração e execução

1. No curso, adicionar **Lumen10 Prova**. Definir abertura/encerramento, duração de 1 minuto a 7 dias, tentativas (0 = ilimitadas, ou 1–20), nota máxima/mínima e método: maior, média, primeira ou última tentativa.
2. Pelo botão **Questões e sorteio do banco**, usar o editor nativo para adicionar questões fixas ou sorteadas por categoria. Podem ser usadas as questões e tipos instalados no Moodle, inclusive objetivas, dissertativas, associação, numéricas e calculadas. O professor pode estruturar seções/blocos no editor. As configurações gerais pertencem à atividade principal, e a edição geral do questionário técnico é bloqueada pelo formulário da regra.
3. Navegação livre permite retornar. Sequencial usa uma questão por página sem retorno; por blocos usa várias questões por página, sem retorno ao bloco anterior. O tamanho de cada página/bloco é configurado na atividade. Embaralhamento de alternativas respeita o suporte e as opções do próprio tipo de questão; sorteio individual é o nativo do Moodle.
4. Restrições: senha nativa, redes/IP, grupo autorizado, campos do perfil, aceite de regulamento e código rotativo. A atividade também respeita visibilidade e restrições de acesso do Moodle. Alterar o regulamento exige novo aceite na próxima tentativa; não interrompe uma tentativa já em execução para colher novo aceite.
5. Opcionalmente, **Sala de prova** exige liberação do professor para começar. Liberar individualmente ou todos os participantes autorizados. No modo de prazo comum, a duração conta da liberação: uma entrada tardia não reinicia a duração. A substituição nativa de segunda chamada, com nova abertura, serve de referência para a nova janela.
6. O aluno confirma o início no formulário nativo. O servidor conserva início e prazo; fechar/reabrir a página ou trocar de dispositivo não pausa o relógio. A regra complementar verifica o prazo nas requisições da prova. Após o prazo, somente respostas previamente recebidas podem ser corrigidas. O cron processa tentativas vencidas, inclusive com o navegador fechado.
7. O salvamento é realizado apenas pelo autosave nativo, com debounce reduzido para até 5 segundos nessa página do Moodle 5.1, sem mudar a configuração global. A instituição deve manter o autosave do Quiz habilitado. Mudança de página e entrega também usam o processamento nativo. Respostas já recebidas ficam preservadas; respostas ainda não transmitidas durante uma queda de conexão não têm garantia de recuperação. Não há armazenamento persistente de respostas em navegador compartilhado.
8. O painel nativo fornece progresso e marcação para revisão. Avisos de tempo são configurados em segundos (padrão 900,300,60). A interface da sala acompanha situação, tempo e comunicação do navegador. Ausência de comunicação não é confirmação de desconexão.
9. Tempo individual, janela excepcional e segunda chamada usam **substituições de usuário/grupo do Moodle**. Ampliar o encerramento individual quando o aluno deve continuar além da janela geral; o tempo adicional sozinho não elimina o limite de encerramento. No modo sincronizado, definir a nova abertura para a segunda chamada. Não apagar a tentativa anterior para conceder exceção.
10. Objetivas seguem a correção nativa. Dissertativas aguardam correção humana; a tela Lumen10 apresenta critérios/rubrica de apoio e usa o formulário nativo de nota/comentário. A identificação por candidato oculta nome nessa tela; não anonimiza respostas que contenham nomes, nem remove o acesso de perfis autorizados a relatórios nativos e auditoria.
11. Divulgação: nota após entrega, encerramento geral, correção de todas, data específica ou liberação manual. Feedback e respostas corretas permanecem bloqueados até o encerramento geral, considerando substituições de prazo e tentativas em andamento. Após mudanças de janela ou divulgação, atualizar a política antes de atender consultas; o cron repete a conferência.
12. Conclusão nativa configurável: entregar tentativa, receber nota definitiva ou atingir nota mínima. Conclusão por nota também respeita a divulgação. Configurar conclusão de curso e regras do Certificados de acordo com a política institucional.

## IA e credenciais institucionais

IA começa **desativada** globalmente e por atividade. Administração do site → IA → Provedores: a instituição cadastra credenciais e habilita os provedores nativos, como OpenAI/Azure conforme os disponíveis nessa instalação. Habilitar a ação de geração de texto e a colocação Editor; aceitar a política nativa de IA pelo fluxo oficial do Moodle. Administração do site → Plugins → Módulos de atividade → Lumen10 Prova habilita a assistência; cada atividade decide se a disponibiliza aos professores com permissão.

O professor escreve uma solicitação e confirma o envio ao provedor. A IA pode sugerir questões, revisar ambiguidades/dificuldade ou rascunhar feedback. Nenhuma resposta da IA é inserida no banco nem vira nota automaticamente. Não enviar documentos pessoais ou respostas identificáveis de alunos sem fundamento e autorização institucional. Os campos de credenciais são os do Moodle, não uma cópia na atividade. Nenhum serviço real é contratado ou ativado pelo plugin.

## Auditoria, privacidade e limitações

### Retomada controlada — versão 1.2.0

A opção **Bloquear retomada ao sair da prova** exige aceite antes de começar. Ocultar a prova, trocar de aba/aplicativo, minimizar, sair da janela ou tentar imprimir cria uma restrição persistente. Mais de 90 segundos sem comunicação também impede continuar. Maximizar/redimensionar a janela, por si só, não bloqueia. Navegar entre questões e enviar pela interface nativa permanece permitido. A migração mantém provas antigas sem essa opção; sua ativação exige ausência de tentativas em andamento.

O bloqueio não cancela a tentativa nem atribui zero: preserva o que o Moodle já salvou, mantém o prazo original e recusa novas respostas enquanto bloqueada. Na **Sala de prova**, um professor com permissão de administrar escolhe **Autorizar retomada**, informa o motivo e libera uma nova geração da página. A página antiga não recebe autorização de escrita. No prazo final, o Quiz mantém a correção automática das respostas salvas. O módulo não cria um segundo motor de correção ou item de nota.

Questões protegidas exibem mensagem e QR Code local com orientação para não fornecer gabarito. A referência assinada identifica tentativa, posição e questão, sem incluir nome, e-mail ou CPF. A equipe pode conferir a referência em **Relatórios → Conferir referência de fotografia**; o acesso exige permissão e sessão válida. A assinatura não impede recortar a imagem ou remover a marca. O QR não detecta compartilhamento automaticamente e não obriga uma IA externa a recusar respostas. A chave de assinatura é gerada no servidor e fica na configuração privada do Moodle; incluí-la nos backups institucionais e não publicá-la.

O menu de contexto e a impressão convencional são restringidos; o layout de impressão não exibe as questões. Essas são barreiras do navegador. Não existe garantia contra JavaScript desativado, ferramentas do desenvolvedor, fotografia por outro aparelho ou uma IA externa. Fechamentos abruptos podem impedir o aviso imediato; a ausência de comunicação é a verificação complementar do servidor. Eventos precisam de análise humana e não comprovam fraude. Para restrições no sistema operacional, avaliar a integração nativa do Moodle com Safe Exam Browser e a compatibilidade de acessibilidade/dispositivos.

Controle de acesso por capacidades: visualizar, administrar/liberar, fiscalizar, corrigir e solicitar IA. A regra protege o questionário vinculado; não oferece seus controles para questionários independentes. Não remover a regra enquanto houver provas em uso e não excluir o questionário técnico separadamente da atividade principal.

A auditoria registra início, envio, correção, liberação e ações institucionais. Telemetria adicional é opcional e desligada por padrão: foco, reconexão, alteração de hash de IP e sessão. Nenhum evento gera acusação ou punição automática. Tela cheia e bloqueios de copiar/colar são barreiras de interface, não prova de integridade nem vigilância por câmera; campos de entrada conservam operações necessárias à acessibilidade. Não existe proctoring, detecção de IA em textos ou garantia contra uso de outro aparelho.

Retenção da telemetria: padrão 90 dias; limpeza não apaga questões, respostas ou notas. A API Privacy exporta e apaga dados próprios no contexto autorizado; respostas/notas do questionário são tratadas pelo provedor nativo do Quiz/Question Engine. A exclusão de toda a atividade exige a ação administrativa do Moodle e também exclui seu questionário vinculado. A instituição define a retenção do acervo acadêmico e o procedimento de contestação.

Relatórios de notas, respostas e estatísticas de questões reutilizam o Moodle. A camada acrescenta mediana e auditoria institucional. Não inferir validação psicométrica, confirmação de desconexão ou teste de carga para milhares de alunos.

## Backup, instalação e manutenção

Instalar a regra de acesso antes da atividade pelos ZIPs individuais, ou colocar os dois diretórios no servidor e executar a atualização nativa. Paths: `mod/lumen10prova` e `mod/quiz/accessrule/lumen10prova`. Não enviar o ZIP que contém ambos ao instalador de um único plugin.

Backup **do curso completo**, incluindo o questionário vinculado, conserva o banco/questões pelo mecanismo nativo e reassocia a prova na restauração. O backup apenas da atividade principal leva configuração/introdução e cria um questionário vazio se o vinculado não foi incluído. Autorizações, código rotativo, telemetria e aceite não são reaproveitados na cópia; IA é desativada na restauração. Para recuperação de desastre, preservar banco de dados e Moodledata completos, configurações e chaves. Não anunciar cópia isolada da atividade como cópia integral das tentativas.

Licença GPL-3.0-or-later e certificado técnico Ed25519/SHA-256 para os dois componentes. A chave privada fica fora dos pacotes. O certificado não equivale a INPI, ICP-Brasil ou garantia de ausência de vulnerabilidades. Alterações exigem nova emissão. Verificação administrativa: `/mod/lumen10prova/certificado.php`.

Fontes oficiais usadas: [regras de acesso do Quiz](https://moodledev.io/docs/5.1/apis/plugintypes/quizaccess) e [Question Engine](https://moodledev.io/docs/5.1/apis/subsystems/question), conferidas com o core local Moodle 5.1.

## Validação desta entrega — 04/10/2026

13 testes PHPUnit distintos e 57 assertions: fluxo inicial (11/46), atualização da nota máxima (1/3) e restauração completa de curso (1/8), em base dedicada de testes. Cobrem nota nativa e sua leitura pela função real do Certificados, correção humana, conclusão, grupos/perfis/suspensão, liberação, aceite, prazos, respostas antes do fim, revisão sem gabarito antecipado, privacidade e reassociação do questionário. O teste de atualização usa a API vigente do Moodle 5.1, sem a função obsoleta `quiz_set_grade`.

12 verificações no navegador do ambiente isolado de cliente, usando o núcleo build 20260928: liberação/manual, proteção de URL nativa, autorização do professor, autosave recuperado após recarga, comunicação da sala, envio, uma nota/conclusão, tela de correção, rejeição de resposta modificada após o prazo, aluno impedido de consultar monitoramento, configurações e visitante sem acesso. No AJAX nativo, a recusa desfaz sua transação; o cron finaliza posteriormente apenas com respostas recebidas a tempo. As duas telas foram inspecionadas visualmente. O PHPUnit usa o build sintético 20260916. Não houve chamadas reais ao provedor de IA, Behat completo, ensaio de carga ou homologação institucional.

A edição nativa da nota máxima sincroniza o valor na atividade; alterações feitas pela configuração principal atualizam máximo e aprovação no Livro de Notas. Depois de tentativas, configurações estruturais ficam bloqueadas na atividade: usar substituições nativas para exceções individuais.

## Entrega e futuras melhorias

Dois ZIPs individuais certificados e cópias do fonte em `Desktop/plugins_atual/Lumen10 Prova`. Instalar primeiro `quizaccess_lumen10prova-2026100400.zip` e depois `mod_lumen10prova-2026100410.zip`. A pasta acompanha também este manual. O pacote de código-fonte completo é uma instalação nova para cliente, sem alunos, documentos, dados, chaves ou senhas de produção. Futuras melhorias: paginação mais ampla dos painéis, ensaio de carga institucional, rubricas estruturadas e homologação do provedor de IA da instituição.


## Publicação no Integrator — 04/10/2026

Os dois componentes 1.0.0 / 2026100400 foram instalados pela atualização nativa do Moodle. Módulo visível, capacidade de adicionar atividade e tarefa de manutenção registrados; nenhuma atividade, aluno ou e-mail de teste foi criado em produção. IA permanece desligada. Certificados de assinatura e arquivos conferidos pelo PHP do próprio servidor (45 arquivos principais e 12 da regra). Login e JavaScript público responderam HTTP 200; configuração nginx válida e nenhuma atualização de outro componente pendente.

Antes da instalação, foi realizado dump consistente do banco, teste gzip e cópia fora do host com SHA-256 conferido. Os nove componentes existentes e os 643 arquivos preexistentes do Quiz foram comparados antes/depois e preservados, incluindo versões. Localmente, os 950 arquivos anteriores da amostra (plugins e tema) mantiveram o mesmo SHA-256. Configurações institucionais, cursos, documentos e credenciais continuam particulares a cada instalação.

A validação local `python scripts/validate.py` passou; 42 manuais/avisos próprios e 448 referências locais foram conferidos sem links quebrados ou valores privados fornecidos. Evidências brutas, screenshots sintéticos, backups e logs ficam em `.runtime/prova` e `.runtime/production-release`, ignorados pelo Git e fora dos pacotes. Produção recebeu apenas os diretórios dos novos componentes; os manuais do código-fonte e a cópia de instalação estão no projeto local.


## Correção de cron — 1.0.1 / 2026100401

A atividade passa a 1.0.1 / 2026100401; a regra de acesso continua 1.0.0 / 2026100400. Foram adicionadas as dependências explícitas do Quiz e da biblioteca de conclusão, para que a manutenção funcione em um processo PHP recém-iniciado, sem depender de páginas anteriores. Falhas individuais da manutenção são registradas e também provocam falha da tarefa no agendador nativo, permitindo nova tentativa em vez de concluir silenciosamente.

Ensaio adicional em processo CLI isolado, com fixture sintética no ambiente de cliente: tarefa sem erros e tentativa encerrada; conferência de todas as provas sintéticas sem exceções. O teste identificou a dependência após a instalação inicial 1.0.0, antes de qualquer prova existir em produção. A correção não muda tabelas, questões, respostas, cálculo de nota nem a regra de acesso. Totais anteriores de PHPUnit e navegador referem-se aos cenários de negócio; este é um ensaio separado de execução do cron. Em atualização, usar o novo ZIP principal e executar o upgrade nativo; não reinstalar/desinstalar a atividade nem remover dados.

Entrega recomendada: `mod_lumen10prova-2026100401.zip` e `quizaccess_lumen10prova-2026100400.zip`. Cópia antiga 1.0.0 conservada na subpasta `historico/1.0.0`, fora da seleção principal de instalação.


A versão **1.0.1 / 2026100401 foi publicada no Integrator** pela atualização nativa em 04/10/2026. Regra 1.0.0 / 2026100400 preservada; assinaturas e arquivos dos dois componentes conferidos novamente, execução isolada da manutenção sem erros, IA desligada e login HTTP 200. Nova cópia verificada do banco e diretório principal certificado anterior preservados antes da troca. Nove componentes anteriores e 643 arquivos preexistentes do Quiz continuaram iguais. Nenhuma atividade ou conta de teste foi criada no servidor. A validação local foi repetida após a correção e passou.

Código-fonte completo atual: `dist/lumen10-source-prova-1.0.1.zip` e seu SHA-256, contendo fonte novo de cliente e manifesto de arquivos, sem o banco/Moodledata ou credenciais da produção. A versão anterior no histórico não é a recomendada para novas instalações.


## Prova em modal e criação de questões — 1.1.0 / 2026100410

Esta atualização acrescenta a janela de prova ao módulo existente. A regra de acesso permanece 1.0.0 / 2026100400. Executar a atualização nativa; não desinstalar o módulo. Os campos novos têm valores padrão, sem apagar tentativas ou notas existentes.

**Professor:** usar “Questões e sorteio do banco” para escrever enunciados, alternativas, respostas corretas e feedback no editor oficial do Moodle. “Importar CSV / Excel” recebe CSV UTF-8 ou XLSX, apresenta uma prévia e só acrescenta questões ao banco e ao questionário após confirmação. As questões importadas ficam em uma categoria própria abaixo da raiz do banco nativo. A importação exige capacidades do professor no módulo, no Quiz e no banco; é bloqueada após tentativas reais. Cancelar a prévia não altera a prova.

Modelo de colunas, uma questão por linha:

```text
tipo;nome;enunciado;alternativa_a;alternativa_b;alternativa_c;alternativa_d;alternativa_e;resposta;feedback;pontos
```

Tipos da planilha: `multichoice` (uma correta, A–E), `truefalse` (V/F), `shortanswer` (resposta curta sem distinção de maiúsculas) e `essay` (dissertativa, resposta vazia, correção humana). Cada questão exige pontos positivos até 1000. O professor pode baixar o modelo na própria tela, abrir no Excel e salvar CSV UTF-8 ou XLSX. Para múltiplas respostas corretas, tipos adicionais, imagens ou matemática, usar o banco/importador nativo.

Limites: 5 MB por planilha, 200 questões, 11 colunas; XLSX até 300 partes e 20 MB descompactados. Usa a primeira aba e recusa fórmulas, macros, objetos embutidos, links externos no relacionamento do workbook e XML com entidades. Não executa conteúdo da planilha. `.xls` binário não é aceito; salvar como `.xlsx` ou CSV UTF-8. A prévia fica na sessão do professor por até 30 minutos; o arquivo bruto não é conservado pelo módulo.

**Aluno:** “Abrir prova” apresenta modal amplo, cabeçalho institucional, questões na área central, relógio e quadro “Gabarito — suas respostas” à direita. As linhas acompanham a quantidade real de questões, incluindo as dez linhas do modelo solicitado. A–E seguem a ordem exibida ao aluno, inclusive com alternativas embaralhadas. Este quadro apresenta escolhas do aluno; não fornece o gabarito correto. Verdadeiro/falso usa V/F, e questões discursivas mostram situação de preenchimento. As alternativas da questão atual podem ser marcadas pelo quadro; para outra página, navegar pelo número, quando permitido. Navegação sequencial e por blocos conservam as restrições nativas.

O modal usa o formulário, o relógio, a navegação, a revisão de envio e o autosave originais do Quiz. Fechar pede confirmação, tenta concluir o salvamento pendente e não envia a prova nem pausa o prazo. Reabrir permite continuar a mesma tentativa. Respostas ainda não recebidas pelo servidor durante queda de conexão não têm garantia de recuperação. O link de página completa continua disponível. O quadro mostra até 200 questões; acima disso, a navegação original fica disponível. Em celular, a coluna lateral fica abaixo das questões, com rolagem, sem reduzir os campos de resposta.

Em “Janela e identidade da prova”, a instituição pode ativar/desativar modal, fundo claro e quadro de respostas; adicionar logo e marca-d'água PNG/JPG/WebP reais, até 5 MB e 8000 pixels por dimensão, e escolher opacidade de 0.02 a 0.15. Na ausência de imagem própria, usa o logo configurado no tema como referência. Arquivos ficam no File API do Moodle e exigem acesso à atividade. Backup completo de curso inclui logo/marca-d'água e questões; a ressalva sobre backup isolado da atividade continua válida.

Foi corrigida também a abertura da correção humana de uma tentativa específica: os slots nativos são comparados como inteiros e a tentativa não carrega as questões duas vezes. O formulário e a nota continuam nativos do Moodle. A mudança não altera os plugins de Certificados ou Prontuário.

IA permanece opcional e desligada. O módulo não faz chamadas reais a serviços externos durante estes testes nem conserva uma cópia da chave de API na atividade.

Entrega principal atual: `mod_lumen10prova-2026100410.zip`, com `quizaccess_lumen10prova-2026100400.zip`. As cópias anteriores ficam em `historico/1.0.0` e `historico/1.0.1`. Código-fonte completo desta atualização: `dist/lumen10-source-prova-1.1.0.zip`, com manifesto e SHA-256, sem dados privados de produção.

Manutenção futura: homologar com professores e alunos da instituição, validar uso por teclado/leitor de tela no tema escolhido, ampliar importação apenas com novos formatos definidos e testados, medir carga real e conservar versões de questões e tentativas. Não alterar o core para mudar este layout; os estilos são restritos ao Quiz vinculado dentro do modal.


### Validação automatizada da versão 1.1.0

Suite nativa isolada: **19 testes PHPUnit e 79 assertions**, sem falhas. Inclui os 12 testes de fluxo/notas/Certificados, seis de autoria/planilha/quadro de respostas/logo real e um de backup completo, agora com questões e logo restaurados. O teste do logo também recusa conteúdo disfarçado de imagem. Evidência privada: `.runtime/prova/phpunit-suite-v110-final.log`. Validação local `python scripts/validate.py` passou. Os totais históricos acima pertencem às versões anteriores e não devem ser somados como cenários novos.


### Validação pela interface — 1.1.0

No ambiente de cliente isolado, o professor importou uma XLSX de dez questões com prévia e confirmação. O aluno abriu pelo botão do módulo e continuou a tentativa nativa. Oito verificações do modal passaram: dez linhas; escolha preservada após recarga pelo autosave; avanço; retorno livre; cancelamento do fechamento; fechamento/retomada da mesma tentativa; celular de 390 px sem transbordamento horizontal; botão Finalizar abrindo revisão/confirmacão de envio nativa. Sem erros JavaScript observados. A navegação com restrições conserva o controle do Quiz e não foi objeto de um novo ensaio de carga.

Teste separado pela tela de correção: professor corrigiu dissertativa para 1,5/2, e o Livro de Notas recebeu 7,5/10. O teste identificou e corrigiu os tipos dos slots na entrada da correção. No modal, o fechamento foi ajustado para permitir cancelamento antes de destruir a janela; os drawers ocultos são fechados pelo controlador nativo do Boost, evitando um backdrop sem painel sobre o conteúdo após redimensionar para celular. Isso não muda a preferência de drawer do aluno.

Screenshots desktop/celular, planilhas, contas e cursos sintéticos e logs ficam na área privada `.runtime`, sem inclusão no código-fonte de cliente. Nenhum aluno ou documento real foi usado.
