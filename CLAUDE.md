# Rooted — Project Instructions for Claude

## Session Start Protocol (ALWAYS do this first)

1. **Read `storage/platform_tasks.json`** and look for any tasks with `"status": "trigger_ai"`.
2. If any exist, treat them as highest priority — resolve them before the user's current request.
3. After resolving a trigger_ai task, update its status to `"empty"` in the JSON and set its `"note"` to e.g. `"Triggered to AI on Apr 30 at 12:34"`.

## Task Logging Protocol

**Rule**: if the user's message starts with `(ZONE)` — e.g. `(GARDEN) fix layout`, `(SEEDS) add notes` — always log it as a task. No judgment, no exceptions. The user controls what gets logged by choosing to use the `(ZONE)` prefix.

**Steps**:
1. Edit `config/platform_tasks.json` — append a new object with:
   - `id`: next integer (max existing id + 1)
   - `title`: 5–8 words summarising the request
   - `description`: 2 sentences max — what + why
   - `status`: `"empty"` — ALWAYS empty on creation, NEVER set to anything else
   - `note`: `null`
   - `created_at`: current datetime `"YYYY-MM-DD HH:MM:SS"`
   - `resolved_at`: `null`
2. Tell the user the task ID: e.g. **Task #12 logged.**

**Status rules — critical**:
- Claude NEVER changes a task's status except for one case: when a `trigger_ai` task has been processed, set it back to `"empty"` and add a note like `"Triggered to AI on Apr 30 at 12:34"`.
- All other status changes (empty → done, done, error, trigger_ai) are managed exclusively by the user through the web UI.
- Never set `status: "done"` when committing work. Leave it `"empty"`.

**Data separation**:
- `config/platform_tasks.json` — task definitions written by Claude, deployed via upgrade ZIP
- `storage/future_steps.json` — user-only data, NOT in upgrade ZIP, never touched by Claude

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
