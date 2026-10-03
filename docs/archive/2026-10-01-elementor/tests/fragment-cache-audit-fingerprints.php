<?php
/** Read-only fingerprints of the staging header and referenced Elementor documents. */
if (getenv('IU_AUDIT_LANGUAGE')) do_action('wpml_switch_language', getenv('IU_AUDIT_LANGUAGE'));
$pending = array(30, 33000, 7767, 7778, 1635);
$seen = array();
while ($pending) {
    $id = array_shift($pending);
    if (isset($seen[$id])) continue;
    $seen[$id] = true;
    $raw = (string) get_post_meta($id, '_elementor_data', true);
    echo $id . ' ' . hash('sha256', $raw) . "\n";
    $walk = function ($nodes) use (&$walk, &$pending) {
        foreach ((array) $nodes as $node) {
            if (!is_array($node)) continue;
            foreach (array('template_id', 'loop_template_id') as $key) {
                $ref = absint($node['settings'][$key] ?? 0);
                if ($ref) $pending[] = $ref;
            }
            if (isset($node['elements'])) $walk($node['elements']);
        }
    };
    $walk(json_decode($raw, true));
}
