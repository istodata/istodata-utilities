<?php
$data = json_decode(get_post_meta(1635, '_elementor_data', true), true);
echo wp_json_encode($data) . "\n";
foreach (array('elementor/query/query_results','post_class','elementor/image_size/get_attachment_image_html','pre_wp_get_loading_optimization_attributes','wp_get_loading_optimization_attributes','wp_get_attachment_image_context','wp_get_attachment_image','wp_lazy_loading_enabled','wp_min_priority_img_pixels','wp_omit_loading_attr_threshold','wp_loading_optimization_force_header_contexts') as $hook) {
    foreach (($GLOBALS['wp_filter'][$hook]->callbacks ?? array()) as $group) foreach ($group as $entry) {
        $fn=$entry['function'];
        echo $hook . ': ' . (is_array($fn) ? (is_object($fn[0]) ? get_class($fn[0]) : $fn[0]) . '::' . $fn[1] : (is_string($fn) ? $fn : 'closure')) . "\n";
    }
}
