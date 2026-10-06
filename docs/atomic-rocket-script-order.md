# Atomic Interactions and WP Rocket, 2026-10-05

Anonymous Avra homepage HTML contains the Kit patch and Core 4.3.3 / Pro 4.3.1 assets.
Rocket rewrites Motion/shared/Pro into delayed scripts with async/defer, while
the patch becomes a delayed inline script without defer. Pro needs shared APIs;
the patch must run after shared and before Pro captures those functions.
At a measured DOM viewport width of 375, excluded image elements bef0c47,
38b9e08 and a0a37e6 acquire transform/opacity inline styles after user interaction
on the optimized page. Their interaction data explicitly excludes mobile/tablet.
The same viewport at /?nowprocket=1 leaves all five initial image elements
without inline animation styles; the scripts retain their native blocking order.

Candidate PHP adds Rocket delay/defer/minify exclusions only for the supported,
enabled frontend fix with an enqueued or printed Atomic Pro handle. Only Motion,
shared utils, Pro interactions, config and patch are protected. Other scripts,
per-interaction exclusions, registry declarations and animation runtime are unchanged.

Candidate validation: PHP lint passed using existing portable PHP (not in PATH),
24 loader cases passed including exclusion guards; both vendor-bundle suites
passed 17 cases each. Existing Rocket 3.23.5.1 source confirms the filter names.
Full WordPress/Rocket candidate installation, cache regeneration and visual
mobile/desktop acceptance remain required. No live changes or cache purge performed.
