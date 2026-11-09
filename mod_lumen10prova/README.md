# Lumen10 Prova

An institutional exam activity built on Moodle's native Quiz, question bank and Question Engine. It adds an exam room, teacher authorisation, controlled result release, a response summary, bounded CSV/XLSX importing and optional teacher AI assistance.

**Activity:** `mod_lumen10prova` 1.3.0 (`2026110900`).
**Required companion:** `quizaccess_lumen10prova` 1.2.0 (`2026110900`).
**Supported Moodle branches:** 5.1 and 5.2. PHP must meet the requirements of the installed Moodle version.

[Português brasileiro](README_pt-BR.md) · [Source and downloads](https://github.com/webrjpro/lumen10prova) · [Public issue tracker](https://github.com/webrjpro/lumen10prova/issues)

## Dependencies and installation

Install **both** components. The activity cannot operate without the quiz access rule. They have separate standard Moodle ZIP packages in the [GitHub releases](https://github.com/webrjpro/lumen10prova/releases).

1. Back up the database, Moodledata and code. Check that no exam attempt is in progress before updating.
2. Install `quizaccess_lumen10prova` in `mod/quiz/accessrule/lumen10prova` (under `public/` where applicable).
3. Install `mod_lumen10prova` in `mod/lumen10prova`.
4. Run Moodle's **Site administration > Notifications** upgrade, or the native CLI upgrade. If using ZIP installation, stage both packages before completing the dependency check.
5. Verify that both components are up to date. Keep the native Quiz scheduled tasks and Moodle cron running.

Do not replace Moodle core files. The plugin does not require Lumen10 Certificates, the institutional portal or any other Lumen10 module. Optional integrations must be configured separately. The companion rule can be installed first and remains inactive for ordinary quizzes.

## Native question and grade ownership

Each activity creates a linked native quiz in the same course. The quiz is accessible through the exam activity and is not duplicated on the course page. Question versions, responses, randomisation, autosave, grading and attempts remain owned by Moodle.

There is **one grade item**, belonging to Quiz. The exam activity does not add a second grade or duplicate its weight in the course total. Standard gradebook aggregation remains under institutional control.

The activity and companion guard the linked quiz's native entry points. Manage its general settings through the exam activity. Native user/group overrides remain available for individual time allowances and resits.

## Creating and running an exam

1. Add **Lumen10 Prova** to a course. Set dates, duration, allowed attempts, grade range, passing grade and grading method.
2. Open the native question editor to add fixed or random questions. Installed Moodle question types remain available.
3. Choose free, sequential or block navigation. Sequential/block navigation does not permit returning to an earlier page/block.
4. Optionally require a group, profile fields, acceptance of exam rules, a rotating code or explicit teacher authorisation.
5. Open **Exam room** to authorise participants, rotate the code, monitor attempt state or release results.
6. Use native Quiz overrides for approved accommodations. User overrides take precedence over group overrides. Group time settings use native Moodle precedence, including unlimited values.

Time is enforced on the server. Closing the browser does not pause the deadline. Only responses received by Moodle can be recovered; unsent responses during a network interruption are not guaranteed. Native autosave must remain enabled.

## Monitoring and controlled resumption

Monitoring reports attempt state and recent browser communication. Optional telemetry records focus changes and salted IP/session hashes. A lack of communication is not proof of disconnection or misconduct.

Controlled resumption can hold an attempt after a tab/app switch, leaving, printing or a communication gap exceeding 90 seconds. A teacher must provide a reason before authorising resumption. The original deadline continues; previously saved answers are preserved. Security generations reject stale response pages after release.

Browser controls are superficial. They do not lock the device, stop screenshots, detect external AI use or replace a supervised exam browser. All evidence requires human assessment. Accessibility needs and institutional exam policies must be evaluated before enabling these controls.

The room batches latest attempts, live records, release records and quiz overrides. Participant-specific Moodle access and availability checks remain in place.

## Importing CSV / XLSX

Download the template from the activity. The column names are a stable, backward-compatible import format:

`tipo;nome;enunciado;alternativa_a;alternativa_b;alternativa_c;alternativa_d;alternativa_e;resposta;feedback;pontos`

Supported types are `multichoice` (one correct A–E choice), `truefalse` (V/F, true/false or 1/0), `shortanswer` and `essay`. Existing Portuguese type aliases remain supported. Essay questions require human grading; leave their answer column empty.

The limit is 5 MB and 200 questions per upload. XLSX formulas, embedded executables, external relationships and oversized decompressed content are rejected. The preview must be confirmed; existing questions are preserved. Use Moodle's native importer for other formats, advanced question types, images and mathematical content.

Preview rows are held in a user-scoped Moodle cache for up to 30 minutes. The session contains only an opaque token. Context, teacher ownership, expiry, CSRF validation and a lock protect confirmation. Expired previews must be uploaded again.

## Grading, result release and completion

Grading uses the native Question Engine, including manual grading for essays. Candidate codes can pseudonymise the Lumen10 grading screen; teachers with wider administrative permissions may still identify users elsewhere in Moodle.

Release can be immediate, after closure, after grading, on a date or by manual action. Correct answers and feedback remain subject to the closure and release policy. Completed attempts are preserved.

Optional completion rules cover viewing the activity, submitting an attempt, receiving a final grade and achieving the passing grade. Activity views emit the standard Moodle `course_module_viewed` event.

## Optional AI assistance

AI starts disabled and requires an institution-configured native Moodle AI provider, plugin/activity enablement, teacher permission, accepted policy and consent for each request. Teachers should avoid sending student identifiers or personal documents.

Suggestions are for human review only. AI does not insert questions automatically, assign grades or determine misconduct. Provider terms, network availability, charges and quotas are the institution's responsibility. No credentials are distributed in the plugin.

## Privacy and security

The Privacy API explicitly describes participant IDs, acceptance/release times, attempt IDs, candidate codes, salted hashes, security state, counters, audit actors and bounded action details. It retains a disclosure for teacher-authorised requests to an external AI provider.

The plugin supports native privacy export/deletion by approved context. Native Quiz owns responses and grades. Institutions must define retention for academic and audit records. Optional telemetry retention does not remove responses, grades or academic history.

Browser requests use registered Moodle External Services through `core/ajax`, with context, capability, enrolment and attempt-ownership checks. Unload telemetry uses a keepalive request to the same registered service and retains Moodle's session/CSRF validation. It cannot submit answers.

## Backup and restore

For complete recovery, back up the **whole course including the linked quiz**, or the database and Moodledata together with required configuration and keys. An activity-only archive is not a complete backup of the question bank and native attempts. External provider credentials are not transferred as activity content.

## Development

AMD sources are in `amd/src`. From the Moodle development root, install the dependencies declared by that Moodle checkout, then run:

```sh
npx grunt amd --root=public/mod/lumen10prova
```

For a checkout without the `public/` directory, adjust the path to `mod/lumen10prova`. Commit the generated `.min.js` and source maps. Run the activity PHPUnit tests in Moodle's dedicated test database, never against production.

## Reporting an issue

Use the [public issue tracker](https://github.com/webrjpro/lumen10prova/issues). Include Moodle/PHP versions, both plugin versions, steps to reproduce and a redacted error. Do not publish credentials, student data, question answers or private server logs.

## Licence and authorship

Copyright 2026 Carlos Antonio de Oliveira Piquet. GNU GPL v3 or later; see `LICENSE.txt`. Moodle and third-party dependencies retain their own notices. Optional Ed25519/SHA-256 manifests support source-integrity checks; they are not independent certification, an official IP registration or a guarantee against copying.
