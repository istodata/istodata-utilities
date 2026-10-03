<?php
// Restore temporary acceptance setup only, retaining accepted plugin versions/settings.
if (get_option('home') !== 'https://wordpress-218158-6702910.cloudwaysapps.com'
    || realpath(ABSPATH) !== '/home/218158.cloudwaysapps.com/manqbfzxjy/public_html') {
    throw new RuntimeException('Wrong staging target');
}
$fixture = get_post(40121);
if (!$fixture || $fixture->post_name !== 'iu-kit-native-atomic-acceptance-20261001') {
    throw new RuntimeException('Unexpected acceptance fixture');
}
if ($fixture->post_status !== 'trash' && !wp_trash_post($fixture->ID)) {
    throw new RuntimeException('Fixture trash failed');
}
delete_option('elementor_experiment-e_atomic_elements'); // Originally absent.
deactivate_plugins('iu-kit-acceptance-harness/acceptance-harness.php');
if (function_exists('rocket_clean_domain')) {
    rocket_clean_domain();
}
echo wp_json_encode([
    'pair' => IU_Elementor_Update_Guard::installed_pair(),
    'kit' => IU_PLUGIN_VERSION,
    'features' => iu_elementor_active_features(),
    'atomic_supported' => iu_elementor_feature_supported('atomic'),
    'fragment_supported' => iu_elementor_feature_supported('fragment'),
    'native_cache' => get_option('elementor_element_cache_ttl'),
    'fixture_status' => get_post_status(40121),
    'native_atomic_option' => get_option('elementor_experiment-e_atomic_elements', 'ABSENT'),
    'harness_active' => is_plugin_active('iu-kit-acceptance-harness/acceptance-harness.php'),
    'adapter_present' => file_exists(WPMU_PLUGIN_DIR . '/iu-kit-candidate-acceptance.php'),
    'rocket_purge_available' => function_exists('rocket_clean_domain'),
], JSON_PRETTY_PRINT) . "\n";
