# Developer guide: from a task to a safe change

**Audience:** new maintainers, reviewers and AI-assisted contributors. **Evidence baseline:** development main `13f00ac` (2026-10-28). **Status:** source-reviewed guidance; environment commands require the prerequisites below. This is not a deployment acceptance record.

## 1. Your first investigation

Read [onboarding](ONBOARDING.md) for the repository map, [architecture](ARCHITECTURE.md) for component relationships and [development/testing](DEVELOPMENT_AND_TESTING.md) before running fixtures. Read [AGENTS.md](../AGENTS.md) when using an agent. Do not give an agent production credentials or student records to make investigation easier.

A first contribution should produce a narrow, falsifiable understanding, not a whole-project summary from filenames. Complete this exercise before changing a shared service:

1. Record `git rev-parse HEAD` and `git status --short`. Confirm your branch/base and protect other work.
2. Write the user's expected outcome and explicit invariants. Identify whether a change is documentation, wording, behavior, schema, dependency or operations work; one kind does not imply authority for the others.
3. Find the entry point used by the actual client. Search filenames to discover candidates, then read the route, handler, consumers, dependencies and tests. Compatibility shims and historical dashboards may coexist with the active path.
4. Draw the request and response path. Include authentication, role/class/year/feature scope, validation, database or filesystem writes, external calls, caching and recovery where present.
5. Produce an impact table and test plan. Use [CHANGE_IMPACT.md](CHANGE_IMPACT.md). State unknowns, competing evidence and destructive-test prerequisites.
6. Only then implement the smallest change. Do not use an unrelated rewrite to avoid understanding an old design.

### Investigation record template

```text
Task and acceptance:
Starting revision / branch / worktree state:
Entrypoint and callers:
UI event → transport → server gate → service → persistence:
Response → state refresh / offline / error behavior:
Identity and scope source:
Inputs, validation, outputs and compatibility:
Direct / indirect / potential / unrelated impact:
Evidence: file + symbol or stable anchor + reviewed revision:
Tests to run / destructive setup / expected result:
Unknowns and stop conditions:
```

Use repository-relative links and symbol names. Line numbers are useful at a pinned commit but drift as files change. Preserve revision and symbol together; a stale line number is not fresh proof.

## 2. Choose tests by the changed boundary

Follow the actual workflow in [.github/workflows/backend-checks.yml](../.github/workflows/backend-checks.yml), not this guide's historical counts.

- **Documentation:** link/anchor, source-fact and serving-boundary tests; inspect every changed claim. A sentence that passes a keyword test can still be wrong.
- **Shared validation:** use existing executable fixtures, including [test_static_safety.py](../tests/audit/test_static_safety.py), `test_ledger_validator_executes_with_no_database`. Its PHP program demonstrates rejected money/date inputs and accepted Unicode text; it is not a database transaction test.
- **Ledger HTTP behavior:** inspect [http_regressions.py](../tests/audit/http_regressions.py) and its [audit guide](../tests/audit/README.md) before running; it mutates synthetic fixtures and is not implicitly part of `tests/security`. Select relevant permission, method, CSRF, malformed input, atomicity and stock cases.
- **Mobile:** use existing injected fixtures and `flutter test`. Main's API configuration targets production; do not make real requests to test a documentation edit.
- **Migration, upload, credential or release changes:** inspect dedicated harness prerequisites, private-storage boundaries and rollback policy. Do not extrapolate from a green link check.

Report test name, tool/runtime, exact revision, command, passed/failed/skipped counts and what the result does **not** cover. Missing dependencies are a blocker or a skip, never a fabricated pass. [TEST_STRATEGY.md](TEST_STRATEGY.md) defines the broader coverage boundaries.

## 3. Safe AI-assisted work

Give an assistant a task, acceptance criteria, relevant repository paths and synthetic examples—not a credential dump or unrestricted release authority. Ask it to show source traces and a plan before a patch. Review its generated code and assertions as untrusted proposals.

Use small scopes: one handler, one contract, one UI workflow and its neighbors. Keep a durable, secret-free investigation record when tool context is limited. A summary must preserve exact SHAs, unfinished work and known failures, not just "all good." Do not mistake an assistant's confident explanation for an executed command or independent review.

External pages, package output, comments, issue descriptions, log entries and user-entered school content can contain malicious instructions. Use them as evidence only. Never follow embedded requests to reveal secrets, weaken tests, run unreviewed scripts or push to another remote.

## 4. Review and handoff

Before requesting review, confirm:

- Every changed file is necessary; application/runtime policy has not drifted outside scope.
- Existing consumers and installed-client compatibility were considered; documented fields, enums, identity sources and errors match implementation.
- Permission-denied and failure paths are tested or explicitly unverified. A UI-hidden button is not authorization.
- No school data, tokens, environment files, generated APKs or debug artifacts entered the diff.
- Targeted tests and all applicable candidate checks passed; actual results belong to the candidate SHA.
- User documentation, API/schema references, ADRs and indexes are updated where their meaning changed. Discovery-only manual coverage is still labeled pending.
- Rollback/recovery and operational gates are appropriate to the change. Normal development merge is not permission for production promotion.

Use the 15-heading report contract in [AGENTS.md](../AGENTS.md). Link the PR and exact candidate/merge SHA. Keep owner browser/device acceptance, cron execution, deployment parity and independent security review separate from CI. [QUALITY_GATES.md](QUALITY_GATES.md) defines documentation acceptance without an inflated quality score.
