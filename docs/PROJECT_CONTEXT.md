# ScoutDesk: Project Context

Paste this file at the start of any new chat. Update the status section (§10) at the end of every task.

## 1. Purpose and MVP scope

ScoutDesk is an independent web application for scout troop administration. Patrol secretaries submit activity programs and formal letters through structured forms. The group secretary reviews them, requests corrections, and approves. Users can retrieve their history.

**MVP (Release 1):** invite-only accounts; one group with patrols; patrol submissions (activity program, formal letter) with drafts; attachments; secretary review with general and per-field comments; approve, reject and reopen; history and search; audit log.

Everything else is deferred (see §9).

## 2. Environment and confirmed decisions

- Windows, PowerShell. Project folder: `D:\dev\scoutdesk`.
- PHP 8.3.31, Laravel 13.37.0, Composer.
- SQLite for development (`database/database.sqlite`, git-ignored). MySQL/MariaDB later. Hosting is undecided. No paid services. The app must run fully locally.
- Tests: PHPUnit with in-memory SQLite.
- Front end: Blade plus vendored Bootstrap 5.3 (LTR and RTL builds), planned from Task 2.3. Node/Vite is not used.
- Languages: **English and Arabic only.** Right-to-left layouts and mixed Arabic/English text are required. French is not supported: no French strings, translations or requirements.
- One group in the UI, but the database supports multiple groups (`group_id` on group-owned data).
- Timestamps are stored in UTC and displayed in Asia/Beirut.
- Workflow: Claude Chat Free, files created manually, one small task at a time, one Git checkpoint per task.

## 3. Modules

Namespaces: `App\Modules\<Module>\...` and `App\Shared\...`.

| Module | Responsibility | May depend on |
|---|---|---|
| Shared | Cross-cutting helpers: public-ID trait, base exceptions, enums used by several modules | nothing |
| Audit | Append-only `audit_logs` and the single API for recording entries | Shared |
| Identity | Users, login/logout, throttling, active-account check, account tokens (hashed, expiring, single-use), password setting and reset, user locale | Shared, Audit |
| Organization | Groups, patrols, memberships and roles, policies, group/patrol scoping, invitations and member management | Shared, Audit, Identity |

Rules:
- Dependencies point one way only. Audit and Identity never import Organization classes.
- Modules talk to each other through Actions and events, never by writing to another module's tables directly.
- Phase 3 adds a `Submissions` module that depends on Organization.
- Inside a module, create folders only when the first file needs them (`Actions`, `Models`, `Policies`, `Http/Controllers`, `Http/Requests`, `Enums`).
- A module gets its own service provider only when it first needs routes or policies.
- Migrations stay in `database/migrations`. Views in `resources/views/<module>/`. Language files in `lang/en` and `lang/ar`. Tests in `tests/Feature/<Module>`.

## 4. Architectural rules

- **Thin controllers:** HTTP in, HTTP out. Flow: Form Request, authorize, Action, response.
- **Validation:** Form Requests, with English and Arabic messages.
- **Authorization:** Policies. Every route requires authentication unless it is on an explicit public allow-list (a test enforces this).
- **Business operations:** one Action class per operation, run in a transaction, audited, reusable by the web UI and the future API.
- **Isolation:** explicit `forGroup()` query scopes plus policies plus route binding scoped by `public_id`. No global scopes. Access to another group's data returns 404.
- **IDs:** every public entity has a ULID `public_id` used in URLs and APIs. The numeric `id` is never exposed.
- **Portability:** statuses and roles are string columns cast to PHP enums (no DB `enum` columns). No database-specific SQL. Everything must behave the same on SQLite and MariaDB.
- **No building ahead:** no tables or classes before the phase that needs them.
- **Text:** every user-visible string lives in lang files, with `en` and `ar` keys in parity. Use logical CSS properties only. User-entered text uses `dir="auto"`. Emails, URLs and reference numbers are rendered LTR.
- **Dependencies:** no new Composer or npm package without a stated reason.

## 5. Security requirements

- No public registration. Accounts are created through invitations. The first group and group secretary are created by the CLI command `scoutdesk:install`, which prints a one-time setup link. No password is typed into a terminal.
- Group data is private. A patrol secretary sees only their own patrol. A group secretary sees only their own group.
- No self-promotion: `is_platform_admin` is not mass-assignable and no web path can write it. Invitation roles come from an allow-list keyed by the inviter's role. Role, group and patrol come from the token, never from form input. Nobody can change their own role, deactivate themselves, or deactivate the last active group secretary. Roles are fixed at creation in Phase 2.
- Tokens: random, only the SHA-256 hash is stored, shown once, expire after 72 hours, single use.
- Login: one generic failure message, rate limiting, session regeneration, active-account check on every request.
- Audit: important actions are logged in an append-only table. No passwords or tokens in metadata. Action keys use `<noun>.<verb>`, e.g. `invitation.created`.
- Uploads (Phase 3): private disk, random file names, server-side MIME and size validation, downloads through an authorized controller.
- Data minimization: no participant names, ages or medical information unless the official form demonstrably requires them.
- Secrets are never committed: `.env` and the SQLite file are git-ignored.

## 6. Roles (Phase 2)

`group_secretary` and `patrol_secretary`. The `is_platform_admin` flag exists but has no UI. `group_leader` comes later.

## 7. Submission revision and immutability rules (built in Phase 3)

1. A revision owns its content: structured rows keyed by the revision. A new revision is created by copying the previous revision's rows.
2. `revision_number` is an integer starting at 1, gapless, per submission, unique with the submission. Revision 1 is created with the draft; each new editable revision takes the next integer.
3. Once submitted, a revision's content and attachment links are never updated or deleted. This is enforced in Actions, model guards and tests.
4. A submission has at most one editable revision at a time.
5. Review, request-changes and approval reference an exact `revision_id`. If a newer revision exists when the action runs, it fails with a conflict.
6. Request changes leaves revision N untouched. The patrol's next edit creates revision N+1 as a draft copy.
7. Reopening an approved submission requires a reason, is blocked once an official document has been issued, and creates revision N+1 as a draft copy. Revision N stays approved forever. The "current revision" is a pointer on the submission.
8. Attachments: a file is stored once and never edited. A revision links to it through a join row. Replacing a file in a draft removes only that draft's link and adds a new file. Submitted revisions keep their original links, so reviewers always see what they saw. A file is physically deleted only if no submitted revision links to it.

## 8. Approval versus signing (later phases)

Approval, document generation, issuing and signing are separate operations. The same person may perform more than one, but an approved submission is not an issued document.

## 9. Deferred (not in the MVP)

- DOCX template filling, template registry, versions and field maps, DOCX-to-PDF conversion
- PDF generation, official document issuance, signing, reference numbers
- Consolidated group programs
- Email delivery
- Versioned API endpoints under `/api/v1/` (Actions are API-ready; controllers come later)
- Integration with the association-wide system (`external_refs`)
- `group_leader` role, platform-admin UI, email-based password reset
- Final activity-program field list: decided only after inspecting the official DOCX. Known table columns: الوقت، الفقرة، المسؤول، التجهيزات، الشرح

## 10. Status

Phase 2 (foundation):
- 2.0 Environment check: done
- 2.1 Create project: done
- 2.2 Module skeleton and project context: in progress
- 2.3 English/Arabic and RTL shell: pending
- 2.4 Core schema and models: pending
- 2.5 Audit log: pending
- 2.6 Account tokens and `scoutdesk:install`: pending
- 2.7 Accepting an invitation: pending
- 2.8 Login, logout, inactive accounts: pending
- 2.9 Authorization layer: pending
- 2.10 Patrol management: pending
- 2.11 Inviting patrol secretaries: pending
- 2.12 Member management and reset links: pending
- 2.13 Audit viewer: pending
- 2.14 Hardening, CI and handover: pending

Phase 3 starts only after all Phase 2 acceptance conditions are met.