# Rooted — Project Instructions for Claude

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
