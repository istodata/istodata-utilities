<?php
/** Single release-owned source of Elementor compatibility. No filter can widen it. */
if (!defined('ABSPATH')) exit;

function iu_elementor_compatibility_registry() {
    return array(
        'atomic' => array(
            'label' => 'Atomic Interactions breakpoints',
            'accepted' => array(array(
                'core' => array('exact' => '4.3.2'),
                'pro' => array('exact' => '4.3.0'),
            ), array('core' => array('exact' => '4.3.3'), 'pro' => array('exact' => '4.3.1'))),
            'tested' => array(array('core' => '4.3.2', 'pro' => '4.3.0',
                'evidence' => 'tests/ATOMIC-INTERACTIONS.md',
                'scope' => 'Checksum-verified vendor runtime; browser/editor acceptance pending.'),
                array('core' => '4.3.3', 'pro' => '4.3.1',
                    'evidence' => 'docs/archive/2026-10-01-elementor/docs/elementor-kit-acceptance-staging.md',
                    'scope' => 'Full Kit/native Motion browser ON/OFF, desktop/mobile/tablet lifecycle; vendor trigger matrix.')),
        ),
        'fragment' => array(
            'label' => 'Elementor Fragment Cache',
            'accepted' => array(
                array('core' => array('exact' => '4.2.3'), 'pro' => array('exact' => '4.2.2')),
                // Preserve the existing core-only static widget path. Pro adapters require Pro.
                array('core' => array('exact' => '4.2.3'), 'pro' => null),
                array('core' => array('exact' => '4.3.3'), 'pro' => array('exact' => '4.3.1')),
            ),
            'tested' => array(
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

function iu_elementor_runtime_pair() {
    return array('core' => defined('ELEMENTOR_VERSION') ? ELEMENTOR_VERSION : null,
        'pro' => defined('ELEMENTOR_PRO_VERSION') ? ELEMENTOR_PRO_VERSION : null);
}

function iu_elementor_version_matches($version, $rule) {
    if ($rule === null) return $version === null;
    if (!is_string($version) || preg_match('/\A[0-9]+\.[0-9]+\.[0-9]+\z/', $version) !== 1) return false;
    // Only explicit, release-owned versions are approved. An unbounded rule fails closed.
    return is_array($rule) && array_keys($rule) === array('exact') && $version === $rule['exact'];
}

function iu_elementor_feature_supported($feature, $pair = null, $requires_pro = false) {
    $registry = iu_elementor_compatibility_registry();
    if (!isset($registry[$feature])) return false;
    $pair = $pair === null ? iu_elementor_runtime_pair() : $pair;
    if ($requires_pro && empty($pair['pro'])) return false;
    foreach ($registry[$feature]['accepted'] as $rule) {
        if (iu_elementor_version_matches($pair['core'] ?? null, $rule['core']) &&
            iu_elementor_version_matches($pair['pro'] ?? null, $rule['pro'])) return true;
    }
    return false;
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
    return $entry['label'] . ': αποδεκτοί συνδυασμοί ' . implode(' ή ', $accepted) .
        '. Ελεγμένοι: ' . implode('; ', $tested) . '. Κάθε νέα έκδοση απαιτεί έλεγχο και ενημέρωση συμβατότητας του Kit.';
}

/** Global switch alone determines updater protection; no saved-data scan. */
function iu_elementor_fragment_enabled() {
    $settings = get_option('istodata_utilities_settings', array());
    return !empty($settings['optimizations']['elementor_fragment_cache']);
}

function iu_elementor_active_features() {
    $settings = get_option('istodata_utilities_settings', array());
    return array('atomic' => !empty($settings['optimizations']['elementor_atomic_interaction_breakpoints']),
        'fragment' => iu_elementor_fragment_enabled());
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
