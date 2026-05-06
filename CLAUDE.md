# Rooted — Project Instructions for Claude

## Session Start Protocol (ALWAYS do this first)

Task statuses live exclusively in `storage/task_statuses.json` on the user's live server — that file is NOT in the repo and Claude has no access to it. There is no local trigger_ai check to run. Just proceed with the user's current request.

## What triggers a task log + version bump

**Two things count as a task:**

1. **`(ZONE)` prefix** — message starts with a word in parentheses, e.g. `(ITEMS)`, `(SURVEY)`, `(GARDEN)`. The word in brackets is the area/ticket type.
2. **Copied AI prompt** — user pastes a block that starts with `=== ROOTED` (the trigger_ai clipboard format). That identifies an existing task by ID and Claude works on it.

**Everything else is just conversation.** If the user is asking questions, giving feedback, or chatting without a `(ZONE)` prefix and without a copied task prompt — do NOT log anything and do NOT bump the version. Just reply.

## ⚠️ AFTER EVERY TASK — NO EXCEPTIONS

After completing any task triggered by either of the two methods above, immediately do ALL of the following before responding to the next message:

1. **Bump the patch version** in `config/defaults.php` (e.g. 3.1.67 → 3.1.68). Update `version_name`.
2. **Add a changelog entry** in `config/changelog.php` (new version, today's date, bullet points).
3. **Add a roadmap block** in `config/roadmap.php` (`'status' => 'released'`, today's date).
4. **Update `version.json`** to the new version number.
5. **Run `bash build-update-zip.sh`** to rebuild the ZIP.
6. **Commit everything** — changed source files + ZIP — with a descriptive message.
7. **Push**: `git push -u origin HEAD:claude/create-rooted-project-RVbog`

The user upgrades the live app after every task via Settings → Update → Update Now. No ZIP = no deployable fix.

**Never batch multiple tasks into one version bump.** Each task = one version increment = one push.

## Task Logging Protocol

When a task is triggered (see above), log it to `config/platform_tasks.json`:

1. Append a new object with **only** these fields:
   - `id`: next integer (max existing id + 1)
   - `title`: 5–8 words summarising the request
   - `description`: 2 sentences max — what + why
   - `created_at`: current datetime `"YYYY-MM-DD HH:MM:SS"`

   Do NOT include `status`, `note`, or `resolved_at` — those live in `storage/task_statuses.json` (user data, not in repo).
2. Tell the user the task ID: e.g. **Task #12 logged.**

For a **copied AI prompt** (existing task): work on the task, do NOT create a new log entry. You may append one line to the existing task's `description` summarising what was changed.

**Status rules — critical**:
- Status is owned by the user exclusively via the web UI. Claude never writes status data anywhere.

**Data separation**:
- `config/platform_tasks.json` — task definitions only. Deployed via upgrade ZIP.
- `storage/task_statuses.json` — user-only. NOT in repo. Never touched by Claude.
- `storage/future_steps.json` — user-only. NOT in repo. Never touched by Claude.
