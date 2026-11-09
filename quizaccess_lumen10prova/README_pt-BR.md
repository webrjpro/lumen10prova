> **Atualização 09/11/2026:** Moodle 5.1 e 5.2; atividade 1.3.0 e regra complementar 1.2.0. Os textos abaixo preservam o histórico operacional. A documentação consolidada e a instalação atual estão no [README em inglês](README.md).

# Lumen10 Prova — regra de acesso

Componente `quizaccess_lumen10prova`, versão `2026100400`, release 1.0.0, Moodle 5.1. Instalar este ZIP primeiro em `mod/quiz/accessrule/lumen10prova`; instalar em seguida o módulo `mod_lumen10prova` em `mod/lumen10prova`, e executar a atualização nativa do Moodle.

Esta regra aplica autorização e tempo do servidor apenas aos questionários vinculados à atividade Lumen10 Prova. Não altera questionários independentes nem arquivos do Moodle Core. O módulo principal exige a regra; não removê-la enquanto houver provas em uso. Questions, tentativas, autosave e notas pertencem ao Quiz nativo. Nenhuma chave de IA é mantida nesta regra.

Manual completo: README.md do módulo Lumen10 Prova e `todos-os-md/PROVA.md` do projeto. GPL-3.0-or-later, autoria indicada e certificado técnico Ed25519/SHA-256; não equivalem a registro jurídico. Verificação administrativa: `/mod/quiz/accessrule/lumen10prova/certificado.php`.
