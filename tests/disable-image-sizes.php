<?php
/** Standalone regression checks for the Kit's image-size optimization. */

function get_option($option, $default = false) {
    return $GLOBALS['options'][$option] ?? $default;
}

function update_option($option, $value) {
    $GLOBALS['updates'][$option] = $value;
    // Model a scalar option read from the database on the next request.
    $GLOBALS['options'][$option] = (string) $value;
}

function add_action($hook, $callback) {
    $GLOBALS['actions'][$hook][] = $callback;
}

function add_filter($hook, $callback) {
    $GLOBALS['filters'][$hook][] = $callback;
}

function apply_filters($hook, $value) {
    foreach ($GLOBALS['filters'][$hook] ?? array() as $callback) {
        $value = $callback($value);
    }
    return $value;
}

function __return_false() {
    return false;
}

function is_admin() {
    return $GLOBALS['is_admin'];
}

function check($condition, $message) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

// Load the production init registration and callback without booting unrelated Kit features.
$source = file_get_contents($argv[1] ?? dirname(__DIR__) . '/istodata-utilities.php');
$matched = preg_match("/^add_action\('init', 'iu_apply_optimizations'\);\Rfunction iu_apply_optimizations\(\) \{.*?^\}/ms", $source, $match);
check($matched === 1, 'Could not load the optimization init callback.');
eval($match[0]);

function request($admin, $expected_updates, $enabled, $scenario) {
    $GLOBALS['is_admin'] = $admin;
    $GLOBALS['updates'] = array();
    $GLOBALS['filters'] = array();

    foreach ($GLOBALS['actions']['init'] as $callback) {
        $callback();
    }

    check($GLOBALS['updates'] === $expected_updates, "$scenario: unexpected option updates.");
    $sizes = array_fill_keys(array('thumbnail', 'medium', 'medium_large', 'large', '1536x1536', '2048x2048'), array('width' => 100));
    $custom_sizes = array('theme-card' => array('width' => 480), 'plugin-square' => array('width' => 200));
    check(apply_filters('intermediate_image_sizes_advanced', $sizes + $custom_sizes) === ($enabled ? $custom_sizes : $sizes + $custom_sizes),
        "$scenario: unexpected upload sub-sizes.");
    check(apply_filters('big_image_size_threshold', 2560) === ($enabled ? false : 2560),
        "$scenario: unexpected original-image scaling threshold.");
    check(get_option('thumbnail_crop') === '1' && get_option('medium_large_size_w') === '768',
        "$scenario: an unrelated media option changed.");
    echo "PASS $scenario\n";
}

$defaults = array(
    'thumbnail_size_h' => '150',
    'thumbnail_size_w' => '150',
    'medium_size_h' => '300',
    'medium_size_w' => '300',
    'large_size_h' => '1024',
    'large_size_w' => '1024',
);
$zero_updates = array_fill_keys(array_keys($defaults), 0);
$options = $defaults + array(
    'istodata_utilities_settings' => array('optimizations' => array('disable_image_sizes' => false)),
    'thumbnail_crop' => '1',
    'medium_large_size_w' => '768',
);

request(false, array(), false, 'Fresh site with the feature off');
check(array_intersect_key($options, $defaults) === $defaults, 'Fresh site must retain Core dimensions.');

$options['istodata_utilities_settings']['optimizations']['disable_image_sizes'] = '1';
request(true, $zero_updates, true, 'First request after enabling');
check(array_intersect_key($options, $defaults) === array_map('strval', $zero_updates), 'Enabled dimensions must be zero.');
for ($request = 1; $request <= 2; $request++) {
    request(false, array(), true, "Stable frontend request $request");
    request(true, array(), true, "Stable admin request $request");
}

// Media Settings saves after init. The following request must reset only edited dimensions.
$options['medium_size_w'] = '420';
$options['large_size_h'] = '900';
request(true, array('medium_size_w' => 0, 'large_size_h' => 0), true, 'Next admin request after a Media Settings save');
request(true, array(), true, 'Stable admin request after reapplying dimensions');

unset($options['istodata_utilities_settings']['optimizations']['disable_image_sizes']);
request(true, array(), false, 'Checkbox cleared');
check(array_intersect_key($options, $defaults) === array_map('strval', $zero_updates), 'Turning the feature off must leave stored zeros.');
$options['thumbnail_size_w'] = '180';
request(true, array(), false, 'Media Settings edited while off');
request(false, array(), false, 'Frontend after a Media Settings edit while off');
check(get_option('thumbnail_size_w') === '180', 'Admin-selected width must persist while off.');

$options['istodata_utilities_settings']['optimizations']['disable_image_sizes'] = '1';
request(false, array('thumbnail_size_w' => 0), true, 'Re-enabled with one dimension changed');

// A site previously served by 2.24.4 already has six stored string zeros and an enabled checkbox.
request(false, array(), true, 'Upgrade from 2.24.4 with saved zeros');
$options['medium_size_h'] = 0;
request(true, array(), true, 'Integer zero from the option cache');
unset($options['thumbnail_size_h']);
request(false, array('thumbnail_size_h' => 0), true, 'Missing option repaired while enabled');
