# Feature registry schema (v2)

File: `features.yaml`

## Top level

| Key | Required | Notes |
| --- | --- | --- |
| `version` | yes | `1` or `2` |
| `features` | yes | Non-empty list of feature rows |
| `journeys` | no | List of journey definitions (v2) |

Companion file `manual_cases.yaml` (optional): human-only cases with stable `MAN-*` ids. Exported together with journeys via `ournear-qa export-manual`.

Legacy v1 rows validate unchanged: only `features` required.

## Feature row

Existing keys preserved (`id`, `module`, `name`, `routes`, `roles`, `status`, `risk`, `human`, …).

Optional v2 keys:

| Key | Type | Rules |
| --- | --- | --- |
| `phpunit` | list of strings | Each entry must match `ClassNameTest` and exist under repo `tests/Feature/` or `tests/Unit/` |
| `depends_on` | list of feature `id` | Targets must exist; no self-reference; no cycles |

## Journey row

| Key | Required | Notes |
| --- | --- | --- |
| `id` | yes | Stable `JRN-*` identifier |
| `status` | yes | `planned` \| `partial` \| `implemented` \| `not_applicable` — schema ≠ automated proof |
| `feature_ids` | yes | Non-empty; each id must exist in `features` |
| `role` | yes | Primary actor (e.g. `candidate`, `employer`) |
| `purpose` | yes | Short description |
| `prerequisites` | yes | List (may be empty) |
| `steps` | yes | Non-empty ordered list of human-readable steps |
| `expected_outcomes` | yes | List |
| `assertions` | yes | List (pytest path strings, probe notes, etc.) |
| `test_data` | yes | List (may be empty) |
| `risk` | yes | P0–P3 aligned with features |
| `depends_on` | no | Other **journey** ids only; acyclic |
| `phpunit` | no | Same format as feature `phpunit` |

Validation: `ournear-qa validate-registry` or `validate_registry()` in Python.
