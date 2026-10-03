# Metrica production installation — 2026-10-03

## Installation

- Production: `https://www.metrica.gr`, Cloud1/server 218158/application 3422917, `thpuwtnwyp/public_html`.
- Installation started at 08:15:47 UTC (11:15:47 Europe/Athens).
- Installed the accepted local ZIP with WP-CLI `plugin install --force`, Kit only, version unchanged at 2.22.0.
- SHA256: `0edbc0289d047cdd14d9dd7210d0703f40231eea333488e5fb4e7558cf3336c2`. All 76 installed files matched.
- No release, version bump, commit, push, unrelated plugin update or cache settings change.

## Backup and recovery reference

Private directory outside webroot:
`/home/master/applications/thpuwtnwyp/tmp/iu-kit-generic-deploy-20261003`

Full webroot archive `files-before.tar.gz` and database `database-before.sql` were created before replacement. All database tables were InnoDB; export used a single transaction. The archive passed gzip validation and contained the main plugin and wp-config files. Checksums are retained in `backup-sha256.txt`.

The live archive reported only `tar: .: file changed as we read it` for root directory metadata. This backup is not an atomic combined filesystem/database snapshot. The database export completed at 08:08:15 UTC, and file backup followed. Recovery, if separately approved, should first restore only the previous Kit folder from the archive; a full database restore would require assessing changes made since backup. No rollback was performed.

## Preserved state

Exact hashes before/after match for Kit settings, active plugins (including order and array keys), and saved Elementor data for header documents 30 and 33000.

Header 30 retains four WP Menu and two Template opt-ins with TTL 604800 seconds. Root `26fa46c6` remains off. Header 33000 has no opt-ins. Native Elementor Element Cache remains `disable`. No staging configuration was copied.

## Actual production evidence

| Check | Result |
| --- | --- |
| First anonymous desktop origin request | Six header fragments stored; six original widgets; eight WP Menu constructions; 150 loop 1635 renders |
| Second anonymous desktop origin request | Six header hits; zero original cached widgets, menu constructions and header loops |
| Cached HTML | All six fragment hashes identical on initial miss/hit |
| Assets | Same external assets on subsequent requests; initial `post-7778.css` regeneration changed only `ver`; both version URLs returned identical CSS bytes |
| Authorized manager on another page | Six hits, identical fragments, zero original cached widgets/menu constructions/header loops |
| Phone origin request | Root mega-menu and its loops pruned; no hidden heavy child fragment returned |
| English | No header opt-ins added; native rendering preserved |
| Desktop/phone interactions, Greek/English | Dropdown or native phone popup opened; destination returned 200; no JS exceptions or failed JS/CSS requests |
| Direct preview | Zero cache events; original widgets and loops executed |
| Actual Elementor editor iframe | Server observation: preview true, header gate false, six original widgets, 150 loops, zero cache events |
| Ordinary public requests after cleanup | Greek and English 200, page cache HIT, observer absent |
| Application errors since install | Zero new PHP fatals or WordPress database errors |

The root mega-menu still renders once: caching its six selected children preserves the approved menu structure while bypassing the heavy dropdown loops. Three other previously opted-in homepage elements also stored/hit; no saved opt-in was added.

## Cleanup and limitations

The request-gated observer plugin, its temporary output directory and credential files were removed. Only our temporary session for an existing manager was destroyed. No new user was created and other sessions were preserved. Deactivating the observer left an array index hole in `active_plugins`; the exact pre-install array was restored after verifying identical plugin names and load order.

No manual full-page/CDN purge or preload was performed. The Kit upgrader hook advanced fragment generation, and format 15 prevents reuse of old entries.

Pre-existing `Undefined variable $post_id` warnings continue. A pre-install Search & Filter Pro `urlencode(array)` fatal at 00:54 UTC was recorded separately and not modified. Shared WP Menu fragments retain the already accepted source-page active classes. English desktop dropdown links retain their existing Greek destinations.

Two observer assertions required correction: the page had additional existing opt-ins beyond the six header targets, and Elementor did not retain the shutdown marker in the editor iframe DOM. Editor bypass was therefore verified through secret-header-gated server observation. These required no Kit code change.

Detailed counts, hashes, asset evidence and settings comparisons: `metrica-production-generic-20261003.json`. Before/after screenshots: `metrica-production-20261003-{before,after}-el-{desktop,phone}.png`.

Existing unrelated repository changes were preserved. Only this report, its JSON evidence and four screenshots were added in this deployment turn.
