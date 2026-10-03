<?php
/** Real guarded Pro skin: semantic replay must preserve future removal identity. */
require_once (getenv('IU_FRAGMENT_INCLUDE') ?: WP_PLUGIN_DIR . '/iu-kit-fragment-harness/elementor-fragment-cache.php');
$external = function ($value) { return $value; };
add_filter('excerpt_length', $external, 11, 2);
$baseline = IU_Elementor_Fragment_Excerpt::profile();
if ($baseline === null) throw new RuntimeException('Unreviewed excerpt baseline');
$widget = \Elementor\Plugin::$instance->widgets_manager->get_widget_types('posts');
$skin = $widget->get_skin('classic');
function iu_fragment_excerpt_parent($length) {
    return \Elementor\Plugin::$instance->elements_manager->create_element_instance(array(
        'id' => 'excerpt-fixture', 'elType' => 'widget', 'widgetType' => 'posts', 'elements' => array(),
        'settings' => array('_skin' => 'classic', 'classic_show_excerpt' => '', 'classic_excerpt_length' => $length)));
}
$skin->set_parent(iu_fragment_excerpt_parent(25));
add_filter('excerpt_length', array($skin, 'filter_excerpt_length'), 20);
add_filter('excerpt_more', array($skin, 'filter_excerpt_more'), 20);
$final = IU_Elementor_Fragment_Excerpt::profile();
if (apply_filters('excerpt_length', 55) !== 25) throw new RuntimeException('Source profile incorrect');
if (!IU_Elementor_Fragment_Excerpt::replay($baseline) || !IU_Elementor_Fragment_Excerpt::replay($final) ||
    apply_filters('excerpt_length', 55) !== 25 || apply_filters('excerpt_more', 'more') !== '') {
    throw new RuntimeException('Excerpt replay differs from guarded skin');
}
// A later real Posts render binds and removes this same global skin's filters.
$skin->set_parent(iu_fragment_excerpt_parent(60));
add_filter('excerpt_length', array($skin, 'filter_excerpt_length'), 20);
add_filter('excerpt_more', array($skin, 'filter_excerpt_more'), 20);
if (apply_filters('excerpt_length', 55) !== 60) throw new RuntimeException('Next skin binding failed');
remove_filter('excerpt_length', array($skin, 'filter_excerpt_length'), 20);
remove_filter('excerpt_more', array($skin, 'filter_excerpt_more'), 20);
if (IU_Elementor_Fragment_Excerpt::profile() !== $baseline || apply_filters('excerpt_length', 55) !== 55) {
    throw new RuntimeException('Replay left an extra callback after the next Posts render');
}
$changed = function ($value) { return $value; };
add_filter('excerpt_more', $changed, 12);
if (IU_Elementor_Fragment_Excerpt::replay($baseline)) throw new RuntimeException('Changed external registration admitted');
remove_filter('excerpt_more', $changed, 12);
remove_filter('excerpt_length', $external, 11);
echo "excerpt-profile-replay-external-preservation-and-future-removal: OK\n";
