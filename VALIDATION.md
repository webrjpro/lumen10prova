# Validation — 9 November 2026

| Environment / check | Result |
| --- | --- |
| Moodle 5.1.8, PHP 8.2.35, MariaDB 11.4.13 | 44 PHPUnit tests / 253 assertions passed |
| Moodle 5.2.4+, PHP 8.3.36, MariaDB 11.4.13 | 44 PHPUnit tests / 253 assertions passed |
| Isolated-browser functional checks | 21 passed; no browser JavaScript errors |
| Standard Grunt AMD build | Passed, with lint warnings set to fail |
| Static PHP / language / source-map checks | 58 PHP files, 295 activity language keys, 4 AMD modules; passed |
| Component ZIP and signature verification | CRC, exact source contents, Ed25519 signature and SHA-256 hashes verified |

The same 44 tests were executed in both Moodle environments; they are not 88 distinct test cases. Browser checks cover localisation, escaped import preview, native import confirmation, response ownership, CSRF, room updates, focus-loss holding, teacher-authorised resumption, saved responses, stale-generation rejection, report/grading templates and the mobile iframe summary.

Tests used synthetic users/data in isolated installations. They are not evidence of a load capacity, universal accessibility compliance, prevention of misconduct or Marketplace approval. No real external AI request was required by these checks. This file describes release validation, not a general independent security certification.

## Package hashes

- `mod_lumen10prova-1.3.0.zip`: `284a33f15eee5f8b6299c7deecedbf104ab2f8bd28fc64ac1409cfeef67782eb`
- `quizaccess_lumen10prova-1.2.0.zip`: `268f1fd9d7369e5a00ddf1514577c6c77ce94d97941b66a84e43b23ca0bea2bc`
