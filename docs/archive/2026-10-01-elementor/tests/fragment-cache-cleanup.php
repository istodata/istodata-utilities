<?php
/** Remove this private staging experiment's cache state, never a general purge. */
if (realpath(ABSPATH) !== realpath('/home/master/applications/manqbfzxjy/public_html') ||
    class_exists('IU_Elementor_Fragment_Cache', false)) {
    throw new RuntimeException('Cleanup requires the original Kit in the exact staging checkout');
}
global $wpdb;
$names = $wpdb->get_col($wpdb->prepare(
    "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
    $wpdb->esc_like('_transient_iu_frag_') . '%', $wpdb->esc_like('iu_frag_lock_') . '%'));
$deleted = 0;
foreach ($names as $name) {
    if (!wp_using_ext_object_cache() && preg_match('/^_transient_(iu_frag_[a-f0-9]{64})$/D', $name, $match)) {
        delete_transient($match[1]);
        $deleted++;
    } elseif (preg_match('/^iu_frag_lock_[a-f0-9]{32}$/D', $name)) {
        delete_option($name);
        $deleted++;
    }
}
foreach (array(30, 33000) as $document) {
    foreach (array('26fa46c6', '417d1ae', 'c60daee') as $element) {
        $suffix = md5($document . ':' . $element);
        delete_option('iu_fragment_epoch_' . $suffix);
        delete_option('iu_fragment_entry_' . $suffix);
    }
}
delete_option('iu_elementor_fragment_epoch');
echo 'Removed experiment fragments/locks: ' . $deleted . "\n";
if (wp_using_ext_object_cache()) {
    echo "Shared object-cache experiment fragments expire by their own TTL; no global flush.\n";
}
