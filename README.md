# Lumen10 Prova

Institutional exams built on Moodle's native Quiz, question bank and Question Engine.

This repository contains **two separately installable Moodle plugins**:

| Component | Release | Directory |
| --- | --- | --- |
| Activity module `mod_lumen10prova` | 1.3.0 / 2026110900 | [mod_lumen10prova](mod_lumen10prova/) |
| Required access rule `quizaccess_lumen10prova` | 1.2.0 / 2026110900 | [quizaccess_lumen10prova](quizaccess_lumen10prova/) |

Both support Moodle **5.1 and 5.2**. Install the companion rule first and stage both components before completing Moodle's upgrade.

## Downloads and installation

Download the **two standard component ZIPs** from [Releases](https://github.com/webrjpro/lumen10prova/releases). Do not upload GitHub's combined source archive as one Moodle plugin.

- `mod_lumen10prova-1.3.0.zip` installs in `mod/lumen10prova`.
- `quizaccess_lumen10prova-1.2.0.zip` installs in `mod/quiz/accessrule/lumen10prova`.

Use the `public/` prefix where required by the Moodle checkout layout. Back up code, database and Moodledata before updating. Follow the [activity documentation](mod_lumen10prova/README.md) and [companion documentation](quizaccess_lumen10prova/README.md).

The companion rule is a separate plugin and requires its own Marketplace submission. Availability on GitHub is not a claim of Marketplace approval.

## Features

- Native question-bank ownership, native autosave and one native Quiz grade item.
- Exam room, teacher authorisation, rotating codes and controlled resumption.
- Free, sequential and block navigation; native individual/group time overrides.
- Teacher monitoring, controlled result release and manual grading.
- Bounded CSV/XLSX import with preview, user-scoped temporary cache and explicit confirmation.
- English and Brazilian Portuguese interfaces, Mustache presentation and standard compiled AMD modules.
- Optional institution-configured AI assistance, disabled unless enabled and consented.
- Standard viewing/completion events, explicit privacy metadata and context/ownership-checked External Services.

Browser controls do not prove misconduct or prevent all copying. AI does not assign grades. Institutions remain responsible for academic policies, accommodations and data retention.

## Validation and support

See [validation results](VALIDATION.md) and [changes](CHANGELOG.md). Report reproducible, redacted problems in the [public issue tracker](https://github.com/webrjpro/lumen10prova/issues).

Do not post credentials, student records, private exam answers or private server logs.

## Licence

Copyright 2026 Carlos Antonio de Oliveira Piquet. GNU GPL v3 or later. Each component includes its licence, authorship notices and an optional signed source-integrity manifest. Private signing keys and deployment credentials are not distributed.
