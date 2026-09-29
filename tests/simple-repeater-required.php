<?php
// Standalone: php tests/simple-repeater-required.php
define('ABSPATH', __DIR__);
$hooks = array();
$errors = array();
$checks = 0;
function add_filter($name, $callback, $priority = 10, $args = 1) { $GLOBALS['hooks'][$name][] = $callback; }
function add_action($name, $callback, $priority = 10, $args = 1) { add_filter($name, $callback, $priority, $args); }
function apply_filters($name, $value, ...$args) {
    foreach ($GLOBALS['hooks'][$name] ?? array() as $callback) { $value = $callback($value, ...$args); }
    return $value;
}
function __($value, $domain) { return $value; }
function acf_render_field_setting($field, $setting) { $GLOBALS['settings'][$setting['name']] = $setting; }
function esc_attr($value) { return htmlspecialchars((string) $value, ENT_QUOTES); }
function esc_html($value) { return esc_attr($value); }
function esc_html__($value, $domain) { return esc_html($value); }
function esc_textarea($value) { return esc_attr($value); }
function wp_json_encode($value) { return json_encode($value); }
function absint($value) { return abs((int) $value); }
function wp_strip_all_tags($value) { return strip_tags($value); }
function sanitize_text_field($value) { return trim(strip_tags($value)); }
function wp_kses_post($value) { return strip_tags($value, '<p><strong><a>'); }
function esc_url_raw($value) { return $value; }
function acf_add_validation_error($input, $message) { $GLOBALS['errors'][$input] = $message; }
class acf_field {
    public $name, $label, $category, $defaults;
    public function __construct() {
        add_filter('acf/validate_value/type=' . $this->name, array($this, 'validate_value'), 10, 4);
    }
}
function acf_register_field_type($class) { $GLOBALS['repeater'] = new $class(); }
require __DIR__ . '/../includes/acf-simple-repeater-field.php';
iu_acf_simple_repeater_register_field();
// ACF core validation can be injected here for integration checks.
function check($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
    $GLOBALS['checks']++;
}
$field = array('type' => 'iu_simple_repeater', 'required' => 1, 'label' => 'Reasons', '_name' => 'reasons', 'key' => 'field_reasons', 'enabled_fields' => array('title', 'text'));
$input = 'acf[field_reasons]';
$empty_values = array(
    'null' => null, 'empty string' => '', 'empty array' => array(),
    'empty marker' => array('__empty' => ''),
    'blank row' => array('__empty' => '', 0 => array('title' => ' ', 'text' => "\n")),
    'empty HTML' => array(0 => array('text' => '<p><br></p>')),
    'disabled subfield' => array(0 => array('image' => 42)),
    'malformed row' => array(0 => 'invalid'),
);
foreach ($empty_values as $name => $value) {
    check($repeater->validate_value(true, $value, $field, $input) === false, 'Required rejects ' . $name);
    $optional = array_merge($field, array('required' => 0));
    check($repeater->validate_value(true, $value, $optional, $input) === true, 'Optional accepts ' . $name);
    check($repeater->update_value($value, 12, $optional) === array(), 'Optional clearing still saves empty array: ' . $name);
    if (function_exists('acf_validate_value')) {
        check(acf_validate_value($value, $field, $input) === false && isset($errors[$input]), 'Actual ACF pipeline rejects ' . $name);
        unset($errors[$input]);
    }
}
foreach (array('title' => 'Reason', 'text' => '<p>Explanation</p>', 'zero title' => '0') as $name => $value) {
    $row = array($name === 'text' ? 'text' : 'title' => $value);
    check($repeater->validate_value(true, array('__empty' => '', 3 => $row), $field, $input) === true, 'Required accepts ' . $name);
}
foreach (array('image' => 42, 'link' => 'https://example.com/') as $name => $value) {
    $media_field = array_merge($field, array('enabled_fields' => array($name)));
    check($repeater->validate_value(true, array(0 => array($name => $value)), $media_field, $input) === true, 'Enabled ' . $name . ' counts as an item');
}
check($repeater->validate_value('Previous error', array(0 => array('title' => 'Reason')), $field, $input) === 'Previous error', 'Preserve existing error message');
check($repeater->validate_value(false, array(0 => array('title' => 'Reason')), $field, $input) === false, 'Preserve failed validation');
if (function_exists('acf_validate_value')) {
    check(acf_validate_value(array('__empty' => '', 0 => array('title' => 'Reason')), $field, $input) === true, 'Actual ACF pipeline accepts real item');
    check(acf_validate_value(array('__empty' => ''), array_merge($field, array('required' => 0)), $input) === true, 'Actual ACF pipeline accepts optional empty marker');
}
$minimum_field = array_merge($field, array('min_items' => 2, 'max_items' => 4, 'required' => 0));
$two_rows = array('__empty' => '', 2 => array('title' => 'First'), 8 => array('text' => 'Second'));
foreach (array('optional' => 0, 'required' => 1) as $name => $required) {
    $minimum_field['required'] = $required;
    check(is_string($repeater->validate_value(true, array('__empty' => ''), $minimum_field, $input)), $name . ' minimum rejects zero rows');
    check(is_string($repeater->validate_value(true, array(0 => array('title' => 'First')), $minimum_field, $input)), $name . ' minimum rejects one row');
    check($repeater->validate_value(true, $two_rows, $minimum_field, $input) === true, $name . ' minimum accepts two populated sparse rows');
    check(is_string($repeater->validate_value(true, array(0 => array('title' => 'First'), 1 => array('title' => ' ')), $minimum_field, $input)), $name . ' minimum does not count blank rows');
    if (function_exists('acf_validate_value')) {
        $errors = array();
        check(acf_validate_value(array('__empty' => ''), $minimum_field, $input) === false, $name . ' minimum blocks ACF save');
        if (!$required) { check(strpos($errors[$input], '2') !== false, 'Minimum message includes configured count'); }
        check(acf_validate_value($two_rows, $minimum_field, $input) === true, $name . ' minimum passes ACF save');
    }
}
$optional = array_merge($field, array('required' => 0));
foreach (array(0, '', -2) as $minimum) {
    check($repeater->validate_value(true, array('__empty' => ''), array_merge($optional, array('min_items' => $minimum)), $input) === true, 'Zero, blank or negative minimum leaves optional behavior unchanged');
}
check($repeater->validate_value(true, $two_rows, array_merge($minimum_field, array('max_items' => 2)), $input) === true, 'Equal minimum and maximum accepted');
check($repeater->validate_value(true, $two_rows, array_merge($minimum_field, array('max_items' => 0)), $input) === true, 'Minimum works without maximum');
check(is_string($repeater->validate_value(true, $two_rows, array_merge($minimum_field, array('max_items' => 1)), $input)), 'Contradictory minimum/maximum reports settings error');
check($repeater->validate_value('Previous error', $two_rows, $minimum_field, $input) === 'Previous error', 'Minimum preserves earlier validation errors');
$repeater->render_field_settings($field);
check($settings['min_items']['default_value'] === 0 && $settings['min_items']['min'] === 0 && $settings['min_items']['step'] === 1, 'Min Items numeric setting defaults to zero');
check($settings['max_items']['default_value'] === 0, 'Existing Max Items default preserved');
ob_start();
$repeater->render_field(array_merge($minimum_field, array('name' => $input, 'value' => array())));
$html = ob_get_clean();
check(strpos($html, 'iu-acf-simple-repeater__minimum') !== false && strpos($html, 'data-max-items="4"') !== false, 'Editor displays minimum without changing max-limit markup');
ob_start();
$repeater->render_field(array_merge($optional, array('name' => $input, 'value' => array())));
$html = ob_get_clean();
check(strpos($html, 'iu-acf-simple-repeater__minimum') === false, 'Legacy fields show no new minimum hint');
foreach (array(
    array(2, 4, array('Ελάχιστο όριο: 2 στοιχεία.', 'Μέγιστο όριο: 4 στοιχεία.'), array('Απαιτούνται')),
    array(4, 4, array('Απαιτούνται 4 στοιχεία.'), array('Ελάχιστο όριο:', 'Μέγιστο όριο:')),
    array(0, 4, array('Μέγιστο όριο: 4 στοιχεία.'), array('Ελάχιστο όριο:', 'Απαιτούνται')),
    array(2, 0, array('Ελάχιστο όριο: 2 στοιχεία.'), array('Μέγιστο όριο:', 'Απαιτούνται')),
    array(0, 0, array(), array('Ελάχιστο όριο:', 'Μέγιστο όριο:', 'Απαιτούνται')),
) as $case) {
    ob_start();
    $repeater->render_field(array_merge($optional, array('name' => $input, 'value' => array(), 'min_items' => $case[0], 'max_items' => $case[1])));
    $html = ob_get_clean();
    foreach ($case[2] as $text) { check(strpos($html, $text) !== false, 'Expected hint: ' . $text); }
    foreach ($case[3] as $text) { check(strpos($html, $text) === false, 'No unwanted hint: ' . $text); }
    if ($case[0] === 4) {
        check(substr_count($html, '<p class="description') === 1, 'Equal limits produce one visible hint');
        check(strpos($html, 'data-limit-text="Απαιτούνται 4 στοιχεία."') !== false, 'Existing limit attribute retains equal-limit text');
    }
}
echo 'PASS: ' . $checks . " checks\n";
