# AGENTS.md

Instructions for Codex agents working on the ISTODATA Kit plugin.

## Project

- This is a WordPress plugin.
- The plugin directory is `istodata-utilities`.
- The local workspace is usually `H:\Το Drive μου\Development\ISTODATA PLUGINS\istodata-utilities`.
- The local zip output directory is `H:\Το Drive μου\Development\ISTODATA PLUGINS`.
- The GitHub releases page is `https://github.com/istodata/istodata-utilities/releases`.

## Default Workflow

1. Make changes locally in this workspace.
2. Keep changes scoped to the user's request.
3. Update `CHANGELOG.md` for every user-facing fix, feature, or workflow change.
4. Do not bump the plugin version unless the user asks for a release or explicitly asks for a version bump.
5. Create a local test zip when the user wants to test the plugin manually in WordPress.
6. Do not create GitHub tags, releases, commits, or pushes unless the user explicitly says the tested change is ready for release.

## Changelog

- Add unreleased work under an `## Unreleased` heading at the top of `CHANGELOG.md`.
- When preparing a release, convert `## Unreleased` to `## X.Y.Z - YYYY-MM-DD`.
- Include a `Version bump to X.Y.Z.` entry only when the plugin header and `IU_PLUGIN_VERSION` are actually updated.
- Keep entries concise and grouped by practical impact: `New`, `Fix`, `Improve`, `Tweak`, `Docs`, or `Cleanup`.

## Versioning

- The plugin version is defined in two places in `istodata-utilities.php`:
  - Plugin header: `Version: X.Y.Z`
  - Constant: `define('IU_PLUGIN_VERSION', 'X.Y.Z');`
- Keep both values identical.
- Release tags use the `vX.Y.Z` format, for example `v2.19.3`.

## Local Test Zip

When creating a manual test zip for upload through WordPress Admin -> Plugins -> Add Plugin:

- Build a clean staging copy first, then zip that staging copy.
- Name the zip `istodata-utilities.zip` so WordPress targets the existing `wp-content/plugins/istodata-utilities` directory.
- The zip must contain exactly one top-level plugin folder: `istodata-utilities/`.
- The main plugin file must be `istodata-utilities/istodata-utilities.php`.
- Do not put files directly at the zip root.
- Do not create nested plugin folders such as `istodata-utilities/istodata-utilities/istodata-utilities.php`.
- Zip entry paths must use forward slashes. Do not ship archives whose entries contain Windows backslashes.
- Save the zip in `H:\Το Drive μου\Development\ISTODATA PLUGINS`.
- Exclude development and temporary files:
  - `.git/`
  - `.github/` unless explicitly needed for distribution
  - `.claude/`
  - existing `.zip` files
  - `test-download.zip`
  - `*.Zone.Identifier`
  - OS metadata and temp files
  - local-only scripts or scratch files

After creating the zip, always inspect the archive contents before giving it to the user. Do not rely on the filesystem staging folder alone.

Required zip verification checklist:

- The upload file is exactly `H:\Το Drive μου\Development\ISTODATA PLUGINS\istodata-utilities.zip`.
- `istodata-utilities/istodata-utilities.php` exists inside the zip.
- `istodata-utilities.php` does not exist at the zip root.
- `istodata-utilities/istodata-utilities/istodata-utilities.php` does not exist.
- No zip entries contain Windows backslashes (`\`).
- No entries start with a version/test folder such as `istodata-utilities-v2.19.2-20260706-test/`.
- No `.git/`, `.github/`, `.claude/`, `*.Zone.Identifier`, or nested `.zip` entries are included.

Report the verification results and the exact absolute zip path to the user.

## GitHub Release

Only do this after the user confirms the WordPress manual test is OK and asks for a release.

1. Confirm the requested version, for example `2.19.3`.
2. Update the plugin header version and `IU_PLUGIN_VERSION`.
3. Move `CHANGELOG.md` entries from `Unreleased` to the release heading.
4. Create a clean release zip in the parent plugin directory.
5. Create tag `vX.Y.Z`.
6. Create the GitHub release and upload the zip.
7. Publish or keep as draft exactly as requested by the user.

Prefer authenticated GitHub tooling (`gh` or the GitHub connector) when available. If authentication is missing, stop and report what is needed.

## Verification

- Run `php -l istodata-utilities.php` when PHP is available.
- If PHP is not available in PATH, say so explicitly.
- Check the final changed files and report unrelated pre-existing working tree changes without modifying them.
