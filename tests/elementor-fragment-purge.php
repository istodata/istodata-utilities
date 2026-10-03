<?php
/** Exercise the actual capability/nonce-protected purge handler on staging. */
require_once (getenv('IU_FRAGMENT_INCLUDE') ?: WP_PLUGIN_DIR . '/iu-kit-fragment-harness/elementor-fragment-cache.php');
wp_set_current_user(1);
$document = absint($args[0] ?? 30);
$element = sanitize_key($args[1] ?? '26fa46c6');
$option = 'iu_fragment_epoch_' . md5($document . ':' . $element);
$before = get_option($option, '0');
$_POST = array('document_id' => $document, 'element_id' => $element);
$_REQUEST['_wpnonce'] = wp_create_nonce('iu_fragment_purge_' . $document . '_' . $element);
register_shutdown_function(function () use ($option, $before) {
    if (get_option($option, '0') === $before) {
        throw new RuntimeException('Authorized purge did not advance element epoch');
    }
    echo "authorized-element-purge: OK\n";
});
IU_Elementor_Fragment_Cache::purge();
