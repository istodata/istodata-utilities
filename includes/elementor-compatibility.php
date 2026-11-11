<?php
/** Single release-owned source of Elementor compatibility. No filter can widen it. */
if (!defined('ABSPATH')) exit;

function iu_elementor_compatibility_registry() {
    return array(
        'atomic' => array(
            'label' => 'Atomic Interactions breakpoints',
            'policy_ranges' => array(array('core' => array('min' => '4.3.6', 'before' => '4.4.0'), 'pro' => array('min' => '4.3.3', 'before' => '4.4.0')), array('core' => array('min' => '4.4.0', 'before' => '4.5.0'), 'pro' => array('min' => '4.4.0', 'before' => '4.5.0'))),
            'known_bad' => array(),
            'accepted' => array(array(
                'core' => array('exact' => '4.3.2'),
                'pro' => array('exact' => '4.3.0'),
            ), array('core' => array('exact' => '4.3.3'), 'pro' => array('exact' => '4.3.1')),
                array('core' => array('exact' => '4.3.4'), 'pro' => array('exact' => '4.3.1')),
                array('core' => array('exact' => '4.3.5'), 'pro' => array('exact' => '4.3.2')),
                array('core' => array('exact' => '4.3.6'), 'pro' => array('exact' => '4.3.2')),
                array('core' => array('exact' => '4.3.6'), 'pro' => array('exact' => '4.3.3'))),
            'tested' => array(array('core' => '4.4.0', 'pro' => '4.4.0', 'evidence' => 'docs/elementor-440-policy-assessment.md', 'scope' => 'Official source comparison of interactions and frontend-modules versus 4.3.6; no 4.4 interaction browser lifecycle test.'), array('core' => '4.3.6', 'pro' => '4.3.3', 'evidence' => 'docs/elementor-pro-433-review.md', 'scope' => 'Source parity and prior 4.3.6/4.3.2 QA; no 4.3.3 real WordPress, editor or native/cold/hit test. Conditions/cache stale behavior unverified.'), array('core' => '4.3.6', 'pro' => '4.3.2',
                'evidence' => 'docs/elementor-436-review.md',
                'scope' => 'Official source review; 17 checksum-verified shared/Pro VM cases with Motion doubles, separate one-trigger real Motion ON/OFF desktop/mobile/resize browser, and native anonymous Rocket ordered excluded chain/canary/no inline animation mutation. No main-world installation-marker or full-trigger Rocket browser PASS; no upstream fix claim.'),
                array('core' => '4.3.5', 'pro' => '4.3.2',
                'evidence' => 'docs/elementor-435-review.md',
                'scope' => 'Accepted exact pair after official vendor review, 17 checksum-verified shared/Pro VM cases and separate real local Motion/native-initializer browser six-trigger ON/OFF desktop/tablet/mobile lifecycle. Scoped staging Classic/Atomic editor save/reload and frontend interactions passed; no new full staging Motion matrix or clean editor latency claim. See corrected review for unknown extra GET initiators and excluded invalid harness attempts.'),
                array('core' => '4.3.2', 'pro' => '4.3.0',
                'evidence' => 'tests/ATOMIC-INTERACTIONS.md',
                'scope' => 'Checksum-verified vendor runtime; browser/editor acceptance pending.'),
                array('core' => '4.3.3', 'pro' => '4.3.1',
                    'evidence' => 'docs/archive/2026-10-01-elementor/docs/elementor-kit-acceptance-staging.md',
                    'scope' => 'Full Kit/native Motion browser ON/OFF, desktop/mobile/tablet lifecycle; vendor trigger matrix.'),
                array('core' => '4.3.4', 'pro' => '4.3.1',
                    'evidence' => 'docs/elementor-434-acceptance.md',
                    'scope' => 'Official vendor source review and unchanged Motion/shared/Pro checksums; 17 actual vendor runtime cases. Prior 4.3.3 browser Motion matrix retained as separate evidence; no new full Motion browser matrix claimed.')),
        ),
        'fragment_atomic' => array(
            'label' => 'Atomic Elements Cache / Device Visibility',
            'policy_ranges' => array(array('core' => array('min' => '4.3.6', 'before' => '4.4.0'), 'pro' => array('min' => '4.3.3', 'before' => '4.4.0')), array('core' => array('min' => '4.4.0', 'before' => '4.5.0'), 'pro' => array('min' => '4.4.0', 'before' => '4.5.0'))),
            'known_bad' => array(),
            'accepted' => array(array('core' => array('exact' => '4.3.3'), 'pro' => array('exact' => '4.3.1')),
                array('core' => array('exact' => '4.3.4'), 'pro' => array('exact' => '4.3.1')),
                array('core' => array('exact' => '4.3.5'), 'pro' => array('exact' => '4.3.2')),
                array('core' => array('exact' => '4.3.6'), 'pro' => array('exact' => '4.3.2')),
                array('core' => array('exact' => '4.3.6'), 'pro' => array('exact' => '4.3.3'))),
            'tested' => array(array('core' => '4.4.0', 'pro' => '4.4.0', 'evidence' => 'docs/elementor-440-policy-assessment.md', 'scope' => 'Dev 6779430: native opt-in save and Atomic Loop/Classic Posts native/cold/hit, HTML and ordered asset URLs/inline parity; active avoid/offset bypass. One published source post; WPML, device and full browser matrix not tested.'), array('core' => '4.3.6', 'pro' => '4.3.3', 'evidence' => 'docs/elementor-pro-433-review.md', 'scope' => 'Source parity and prior 4.3.6/4.3.2 QA; no 4.3.3 real WordPress, editor or native/cold/hit test. Conditions/cache stale behavior unverified.'), array('core' => '4.3.6', 'pro' => '4.3.2',
                'evidence' => 'docs/elementor-436-review.md',
                'scope' => 'Staging mixed Classic/Atomic Loop native/cold/hit external/internal CSS with ordered external/generated/inline assets and zero original renders/queries on hits; repeated/new post-ID CSS batches, published frontend style invalidation, native SVG save and mobile hidden-node nonexecution. Actual WPML English translated page/template and distinct keys, not a full cross-language HIT matrix. Existing conservative scope and bypasses retained.'),
                array('core' => '4.3.5', 'pro' => '4.3.2',
                'evidence' => 'docs/elementor-435-review.md',
                'scope' => 'Accepted exact pair within existing conservative scope/bypasses: actual staging Atomic Loop native/miss/hit HTML and ordered external/generated/inline assets, zero original renders/queries on hits, 23 contexts, six GR/EN phone responses and 20 SDK checks. Native Atomic typed editor save/reload passed. Corrected phone browser evidence is emulated visibility/non-replay, not physical-device or phone-HIT evidence; WPML expiry and corrected MailOptin request-URL delta separately audited.'),
                array('core' => '4.3.3', 'pro' => '4.3.1',
                'evidence' => 'docs/atomic-fragment-integrated.md',
                'scope' => 'Shared engine: Atomic Loop and standalone Heading early hits with identical HTML/assets; typed editor save, seven-day default, GR/EN and phone variants, visibility, page context, source invalidation and concurrent cold misses. Eligible fragments share login and unrelated URL contexts under the common privacy/editor guards. Conservative public post/literal scope; pagination, custom/current queries, components, interactions and unknown resolvers bypass.'),
                array('core' => '4.3.4', 'pro' => '4.3.1',
                    'evidence' => 'docs/elementor-434-acceptance.md',
                    'scope' => 'Actual staging Atomic Loop: native/miss/hit HTML and ordered CSS/JS parity, zero original loop/widget executions and queries on hits; shared login/ordinary URL, device/language, source invalidation and concurrent misses. Conservative scope and bypasses unchanged.')),
        ),
        'fragment' => array(
            'label' => 'Elementor Fragment Cache',
            'policy_ranges' => array(array('core' => array('min' => '4.3.6', 'before' => '4.4.0'), 'pro' => array('min' => '4.3.3', 'before' => '4.4.0')), array('core' => array('min' => '4.4.0', 'before' => '4.5.0'), 'pro' => array('min' => '4.4.0', 'before' => '4.5.0'))),
            'known_bad' => array(),
            'accepted' => array(
                array('core' => array('exact' => '4.2.3'), 'pro' => array('exact' => '4.2.2')),
                // Preserve the existing core-only static widget path. Pro adapters require Pro.
                array('core' => array('exact' => '4.2.3'), 'pro' => null),
                array('core' => array('exact' => '4.3.3'), 'pro' => array('exact' => '4.3.1')),
                array('core' => array('exact' => '4.3.4'), 'pro' => array('exact' => '4.3.1')),
                array('core' => array('exact' => '4.3.5'), 'pro' => array('exact' => '4.3.2')),
                array('core' => array('exact' => '4.3.6'), 'pro' => array('exact' => '4.3.2')),
                array('core' => array('exact' => '4.3.6'), 'pro' => array('exact' => '4.3.3')),
            ),
            'tested' => array(array('core' => '4.4.0', 'pro' => '4.4.0', 'evidence' => 'docs/elementor-440-policy-assessment.md', 'scope' => 'Dev 6779430: Classic Posts alongside Atomic Loop native/cold/hit, HTML and ordered asset URLs/inline parity, zero originals on hits. One published source post; WPML and device not tested.'), array('core' => '4.3.6', 'pro' => '4.3.3', 'evidence' => 'docs/elementor-pro-433-review.md', 'scope' => 'Source parity and prior 4.3.6/4.3.2 QA; no 4.3.3 real WordPress, editor or native/cold/hit test. Conditions/cache stale behavior unverified.'),
                array('core' => '4.3.6', 'pro' => '4.3.2',
                    'evidence' => 'docs/elementor-436-review.md',
                    'scope' => 'Staging mixed Classic/Atomic native/cold/hit HTML/assets and zero original renders/queries on hits, late sibling CSS and native Pro query/excerpt states; published CSS invalidation and native SVG URL normalization passed. Actual English translated page/template and device-key/visibility checks are bounded, not a full cache context or browser matrix. Existing eligibility, privacy/editor bypasses and saved settings remain unchanged.'),
                array('core' => '4.3.5', 'pro' => '4.3.2',
                    'evidence' => 'docs/elementor-435-review.md',
                    'scope' => 'Accepted exact pair: staging six Classic header hits, zero original widgets/header loops versus 150 native/cold loops, ordered assets/generated/inline CSS parity and native once-only loop CSS. Corrected desktop menu/tab/cached-link navigation and Classic save/reload passed; ordinary URL/cross-page reuse retained. Phone visibility/menu/search passed under scoped UA/viewport emulation, not phone-fragment HIT. Real WP/Pro excerpt replay and REST-corrected Atomic repeat passed; request-only MailOptin URL delta audited, extra GET initiators/timing unresolved.'),
                array('core' => '4.3.4', 'pro' => '4.3.1',
                    'evidence' => 'docs/fragment-cache-loop-css-fix.md',
                    'scope' => 'Actual staging six Classic header fragments: zero cached widgets and heavy header loops on hits; native/miss/hit ordered external and inline CSS/JS parity, one native loop-1635 stylesheet, cross-page reuse with knowingly shared menu active attributes, phone visibility and browser menu/navigation checks.'),
                array('core' => '4.3.3', 'pro' => '4.3.1',
                    'evidence' => 'docs/archive/2026-10-01-elementor/docs/elementor-kit-acceptance-staging.md',
                    'scope' => 'Full Kit Greek/English save/reload, cross-page HTML/assets/effects, selective purge, UA exclusions; Atomic jointly ON.'),
                array('core' => '4.3.3', 'pro' => '4.3.1',
                    'evidence' => 'docs/fragment-cache-generic-result.md',
                    'scope' => 'Generic roots: four WP Menus, two Templates, core Text Editor and custom CSS/JS widget; GR/EN cross-page/user HTML/assets/query/excerpt parity, native initialization, preview, locking, purge and menu-item invalidation. Shared active-page menu attributes; opaque widget effects require individual review.'),
                array('core' => '4.3.3', 'pro' => '4.3.1',
                    'evidence' => 'docs/metrica-production-generic-20261003.md',
                    'scope' => 'Metrica production: six Greek header hits, zero original cached widgets/menu construction/loops; desktop/phone interactions, manager cross-page reuse and native editor/preview bypass. English header opt-ins remain OFF.'),
                array('core' => '4.2.3', 'pro' => '4.2.2',
                    'evidence' => 'docs/archive/2026-10-01-elementor/docs/elementor-kit-acceptance-staging.md',
                    'scope' => 'Greek full Kit save/reload/origin cross-page; remaining acceptance pending.'),
                array('core' => '4.2.3', 'pro' => null,
                    'evidence' => 'tests/elementor-fragment-bootstrap.php',
                    'scope' => 'Isolated core-only proxy bootstrap, no browser claim.'),
            ),
        ),
    );
}

function iu_elementor_installed_pair() {
    if (defined('WP_PLUGIN_DIR') && function_exists('get_file_data')) {
        $pair = array('core' => null, 'pro' => null);
        foreach (array('core' => 'elementor/elementor.php', 'pro' => 'elementor-pro/elementor-pro.php') as $key => $plugin) {
            $file = WP_PLUGIN_DIR . '/' . $plugin;
            if (is_file($file)) {
                $data = get_file_data($file, array('Version' => 'Version'), 'plugin');
                $pair[$key] = $data['Version'];
            }
        }
        return $pair;
    }
    return array('core' => defined('ELEMENTOR_VERSION') ? ELEMENTOR_VERSION : null,
        'pro' => defined('ELEMENTOR_PRO_VERSION') ? ELEMENTOR_PRO_VERSION : null);
}

function iu_elementor_runtime_pair($refresh = false) {
    // Read headers once per request; native post-install observation refreshes them
    // after each step, without blocking updates or trusting stale loaded constants.
    static $pairs = array();
    $context = (function_exists('get_current_network_id') ? get_current_network_id() : 0) . ':' .
        (function_exists('get_current_blog_id') ? get_current_blog_id() : 0);
    if ($refresh) $pairs = array(); // Plugin files are shared across switched sites.
    if (!isset($pairs[$context])) {
        $pair = iu_elementor_installed_pair();
        // Preserve the shipped "without Pro" static path when Pro is installed
        // but inactive. Advisories still use the raw installed counterpart.
        if (defined('WP_PLUGIN_DIR') && function_exists('get_site_option')) {
            $active = (array) get_option('active_plugins', array());
            $network = (array) get_site_option('active_sitewide_plugins', array());
            foreach (array('core' => 'elementor/elementor.php', 'pro' => 'elementor-pro/elementor-pro.php') as $key => $plugin) {
                if (!in_array($plugin, $active, true) && !isset($network[$plugin])) $pair[$key] = null;
            }
        }
        $pairs[$context] = $pair;
    }
    return $pairs[$context];
}

function iu_elementor_version_matches($version, $rule) {
    if ($rule === null) return $version === null;
    if (!is_string($version) || preg_match('/\A[0-9]+\.[0-9]+\.[0-9]+\z/', $version) !== 1 || !is_array($rule)) return false;
    if (array_keys($rule) === array('exact')) return $version === $rule['exact'];
    if (array_keys($rule) !== array('min', 'before')) return false;
    foreach ($rule as $boundary) if (!is_string($boundary) || preg_match('/\A[0-9]+\.[0-9]+\.[0-9]+\z/', $boundary) !== 1) return false;
    // Range bounds are mandatory and confined to one minor series.
    $min = explode('.', $rule['min']); $max = explode('.', $rule['before']);
    if ((int) $min[0] !== (int) $max[0] || (int) $max[1] !== (int) $min[1] + 1 || (int) $max[2] !== 0) return false;
    return version_compare($rule['min'], $rule['before'], '<') && version_compare($version, $rule['min'], '>=') && version_compare($version, $rule['before'], '<');
}

function iu_elementor_pair_matches($pair, $rule, $known_bad = false) {
    if (!isset($rule['core']) || !array_key_exists('pro', $rule)) return false;
    foreach (array('core', 'pro') as $key) {
        // Wildcards are permitted only in deny rules; they never grant support.
        if ($known_bad && $rule[$key] === array('any' => true)) continue;
        if (!iu_elementor_version_matches($pair[$key] ?? null, $rule[$key])) return false;
    }
    return true;
}

/** Known bad entries override exact approvals and ranges for the affected feature. */
function iu_elementor_entry_status($entry, $pair) {
    foreach ($entry['known_bad'] ?? array() as $rule) {
        if (iu_elementor_pair_matches($pair, $rule, true)) return 'blocked';
    }
    foreach ($entry['accepted'] ?? array() as $rule) {
        if (iu_elementor_pair_matches($pair, $rule)) return 'accepted';
    }
    foreach ($entry['policy_ranges'] ?? array() as $rule) {
        if (iu_elementor_pair_matches($pair, $rule)) return 'policy';
    }
    return 'unsupported';
}

function iu_elementor_known_bad_reason($feature, $pair) {
    $entry = iu_elementor_compatibility_registry()[$feature] ?? array();
    foreach ($entry['known_bad'] ?? array() as $rule) {
        if (iu_elementor_pair_matches($pair, $rule, true)) return (string) ($rule['reason'] ?? 'Γνωστή ασυμβατότητα.');
    }
    return '';
}

function iu_elementor_feature_status($feature, $pair = null, $requires_pro = false) {
    $registry = iu_elementor_compatibility_registry();
    if (!isset($registry[$feature])) return 'unsupported';
    $pair = $pair === null ? iu_elementor_runtime_pair() : $pair;
    if ($requires_pro && empty($pair['pro'])) return 'unsupported';
    return iu_elementor_entry_status($registry[$feature], $pair);
}

function iu_elementor_feature_supported($feature, $pair = null, $requires_pro = false) {
    return in_array(iu_elementor_feature_status($feature, $pair, $requires_pro), array('accepted', 'policy'), true);
}

function iu_elementor_compatibility_description($feature) {
    $entry = iu_elementor_compatibility_registry()[$feature];
    $accepted = array();
    foreach ($entry['accepted'] as $pair) {
        $parts = array();
        foreach (array('core' => 'Elementor', 'pro' => 'Pro') as $key => $label) {
            $rule = $pair[$key];
            $parts[] = $rule === null ? 'χωρίς Pro (static widgets)' : $label . ' ' .
                ($rule['exact'] ?? 'μη έγκυρη δήλωση συμβατότητας');
        }
        $accepted[] = implode(' / ', $parts);
    }
    $tested = array();
    foreach ($entry['tested'] as $pair) {
        $tested[] = $pair['core'] . ' / ' . ($pair['pro'] ?? 'χωρίς Pro') . ' [' . $pair['scope'] . ']';
    }
    $ranges = array();
    foreach ($entry['policy_ranges'] as $pair) {
        $ranges[] = 'Elementor ' . $pair['core']['min'] . ' έως πριν από ' . $pair['core']['before'] .
            ' / Pro ' . $pair['pro']['min'] . ' έως πριν από ' . $pair['pro']['before'];
    }
    return $entry['label'] . ': αποδεκτοί συνδυασμοί ' . implode(' ή ', $accepted) .
        '. Επιτρέπονται βάσει πολιτικής (όχι κατ’ ανάγκη ελεγμένοι): ' . implode(' ή ', $ranges) .
        '. Στοιχεία δοκιμών: ' . implode('; ', $tested) . '.';

}

/** Saved opt-in is preserved when runtime compatibility temporarily bypasses caching. */
function iu_elementor_fragment_enabled() {
    $settings = get_option('istodata_utilities_settings', array());
    return !empty($settings['optimizations']['elementor_fragment_cache']);
}

function iu_elementor_active_features() {
    $settings = get_option('istodata_utilities_settings', array());
    $active = array('atomic' => !empty($settings['optimizations']['elementor_atomic_interaction_breakpoints']),
        'fragment' => iu_elementor_fragment_enabled());
    $plugin = class_exists('Elementor\\Plugin') ? \Elementor\Plugin::$instance : null;
    $device = !isset($settings['additional']['elementor_device_visibility']) || !empty($settings['additional']['elementor_device_visibility']);
    if (($active['fragment'] || $device) && $plugin && isset($plugin->experiments) &&
        is_callable(array($plugin->experiments, 'is_feature_active')) &&
        defined('Elementor\\Modules\\AtomicWidgets\\Module::EXPERIMENT_NAME') &&
        $plugin->experiments->is_feature_active(\Elementor\Modules\AtomicWidgets\Module::EXPERIMENT_NAME)) {
        $active['fragment_atomic'] = true;
    }
    return $active;
}

function iu_elementor_pair_failures($pair, $active) {
    $failures = array();
    foreach ($active as $feature => $enabled) {
        if ($enabled && !iu_elementor_feature_supported($feature, $pair)) $failures[] = $feature;
    }
    return $failures;
}

/** Derive a shared explicit pair from the registry. */
function iu_elementor_common_pair($features) {
    $registry = iu_elementor_compatibility_registry();
    $versions = array('core' => array(), 'pro' => array());
    foreach ($features as $feature) {
        if (!isset($registry[$feature])) return null;
        foreach ($registry[$feature]['accepted'] as $pair) {
            foreach ($pair as $key => $rule) {
                $version = $rule === null ? null : ($rule['exact'] ?? '');
                if (!in_array($version, $versions[$key], true)) $versions[$key][] = $version;
            }
        }
    }
    foreach ($versions['core'] as $core) foreach ($versions['pro'] as $pro) {
        $pair = array('core' => $core, 'pro' => $pro);
        if (!iu_elementor_pair_failures($pair, array_fill_keys($features, true))) return $pair;
    }
    return null;
}
