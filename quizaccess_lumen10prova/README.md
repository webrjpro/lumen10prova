# Lumen10 Prova quiz access rule

Required companion for [Lumen10 Prova](https://github.com/webrjpro/lumen10prova), version 1.2.0 (`2026110900`). Supports Moodle 5.1 and 5.2.

Install in `mod/quiz/accessrule/lumen10prova` (under `public/` where applicable), then install activity 1.3.0 or later in `mod/lumen10prova`. Run the native Moodle upgrade after staging both components. Standard component ZIPs are available in the [releases](https://github.com/webrjpro/lumen10prova/releases).

This rule applies institutional authorisation, accepted rules, rotating access codes, deadlines and controlled-resumption policies to quizzes linked to the activity. It does not affect ordinary quizzes and stores no independent personal-data tables. Native Quiz owns responses and grades.

Back up the database and Moodledata before updates. Avoid changing settings during active attempts. Use native user/group quiz overrides for accommodations.

[Public issue tracker](https://github.com/webrjpro/lumen10prova/issues) · [Português brasileiro](README_pt-BR.md).

Copyright 2026 Carlos Antonio de Oliveira Piquet. GNU GPL v3 or later; see `LICENSE.txt`.
