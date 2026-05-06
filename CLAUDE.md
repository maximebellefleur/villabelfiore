# Rooted — Project Instructions for Claude

## Session Start Protocol (ALWAYS do this first)

Task statuses live exclusively in `storage/task_statuses.json` on the user's live server — that file is NOT in the repo and Claude has no access to it. There is no local trigger_ai check to run. Just proceed with the user's current request.

## ⚠️ AFTER EVERY SINGLE TASK — NO EXCEPTIONS

After completing **any** task — bug fix, feature, one-liner change, anything — immediately do ALL of the following before responding to the next message:

1. **Bump the patch version** in `config/defaults.php` (e.g. 3.1.67 → 3.1.68). Update `version_name`.
2. **Add a changelog entry** in `config/changelog.php` (new version, today's date, bullet points).
3. **Add a roadmap block** in `config/roadmap.php` (`'status' => 'released'`, today's date).
4. **Update `version.json`** to the new version number.
5. **Run `bash build-update-zip.sh`** to rebuild the ZIP.
6. **Commit everything** — changed source files + ZIP — with a descriptive message.
7. **Push**: `git push -u origin HEAD:claude/create-rooted-project-RVbog`

**Do this even for hotfixes.** The user upgrades the live app after every task via Settings → Update → Update Now. If there is no new ZIP with a new version number, the upgrade panel shows nothing and the fix is not deployable.

**Never batch multiple tasks into one version bump.** Each task = one version increment = one push.

## Task Logging Protocol

**Rule**: if the user's message starts with `(ZONE)` — e.g. `(GARDEN) fix layout`, `(SEEDS) add notes` — always log it as a task. No judgment, no exceptions. The user controls what gets logged by choosing to use the `(ZONE)` prefix.

**Steps**:
1. Edit `config/platform_tasks.json` — append a new object with **only** these fields:
   - `id`: next integer (max existing id + 1)
   - `title`: 5–8 words summarising the request
   - `description`: 2 sentences max — what + why
   - `created_at`: current datetime `"YYYY-MM-DD HH:MM:SS"`

   Do NOT include `status`, `note`, or `resolved_at` — those live in `storage/task_statuses.json` (user data, not in repo).
2. Tell the user the task ID: e.g. **Task #12 logged.**

**Status rules — critical**:
- Status, note, and resolved_at are owned by the user and live exclusively in `storage/task_statuses.json` on the live server. Claude has no access to that file and never writes status data anywhere.
- The user manages all status changes (empty → done → error → trigger_ai → empty) via the web UI on `/settings/upcoming`.

**Data separation**:
- `config/platform_tasks.json` — task definitions only (id/title/description/created_at). Deployed via upgrade ZIP.
- `storage/task_statuses.json` — user-only status/note/resolved_at keyed by task id. NOT in upgrade ZIP. Never touched by Claude.
- `storage/future_steps.json` — user-only data, NOT in upgrade ZIP. Never touched by Claude.

**Do NOT log**: messages without a `(ZONE)` prefix, operational commands ("commit and push"), clarifications, or general conversation.

## End-of-Session Release Checklist

When all tasks for a session are complete, always do the following in order:

1. **Summarize** each change made, one by one (what was changed and why).
2. **Bump the version** in `config/defaults.php` — increment the patch number (e.g. 1.4.2 → 1.4.3). Update `version_name` to describe the release.
3. **Add a changelog entry** in `config/changelog.php` with the new version, today's date, and bullet points under `new`, `improved`, and/or `fixed`.
4. **Update `config/roadmap.php`** — add the new version block with `'status' => 'released'` and the release date.
5. **Commit all changes** with a descriptive message summarizing the version.
6. **Rebuild the update ZIP**: `bash build-update-zip.sh`
7. **Commit the new ZIP** (`rooted-cpanel-update.zip`).
8. **Push** everything to the branch: `git push -u origin <branch>`

This ensures every session produces a version that is immediately upgradable through the Rooted upgrade panel (Settings → Update → Update Now).
