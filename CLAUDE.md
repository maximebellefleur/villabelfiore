# Rooted — Project Instructions for Claude

## Session Start Protocol (ALWAYS do this first)

1. **Read `storage/platform_tasks.json`** and look for any tasks with `"status": "trigger_ai"`.
2. If any exist, treat them as highest priority — resolve them before the user's current request.
3. After resolving a trigger_ai task, update its status to `"empty"` in the JSON and set its `"note"` to e.g. `"Triggered to AI on Apr 30 at 12:34"`.

## Task Logging Protocol (ALWAYS do this on every user message)

When the user sends a request:
1. **Evaluate**: is this a platform task (something to build, fix, or change in the app)?
   - **YES — log it**: new features, bug fixes, UI changes, data model changes, page redesigns
   - **NO — skip**: operational commands ("commit and push", "deploy", "what is X?"), clarifications, approval/confirmation messages, general conversation
2. **If yes**: add the task directly to `storage/platform_tasks.json`. Use:
   - `title`: 5–8 words, what it is
   - `description`: exactly 2 sentences summarising the request and its intent
3. **If a message contains multiple tasks** (bullet list etc.): decide if they form one unified task or should be split. Split only if they are genuinely independent features.
4. Log the task as `"status": "empty"` initially, then update to `"done"` once shipped.

**To add a task without PHP**, directly edit `storage/platform_tasks.json` — append an object with the next sequential `id`, `title`, `description`, `status: "empty"`, `note: null`, `created_at` (current datetime), `resolved_at: null`.

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
