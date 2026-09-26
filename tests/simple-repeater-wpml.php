<?php
// Standalone adapter contract tests: php tests/simple-repeater-wpml.php
define('ABSPATH', __DIR__);
$meta = $acf_fields = $hooks = array();
$checks = 0;
function add_filter($name, $callback, $priority = 10, $args = 1) { $GLOBALS['hooks'][$name] = $callback; }
function add_action($name, $callback, $priority = 10, $args = 1) { add_filter($name, $callback, $priority, $args); }
function apply_filters($name, $value, ...$args) {
    if ($name === 'wpml_is_translated_taxonomy') { return true; }
    if ($name === 'wpml_object_id') { return $value === 202 ? 102 : $value + 100; }
    if ($name === 'wpml_element_language_details') { return $GLOBALS['language_details'] ?? (object) array('source_language_code' => 'el'); }
    return $value;
}
function get_metadata($kind, $id, $key = '', $single = false) {
    $data = $GLOBALS['meta'][$kind][$id] ?? array();
    return $key === '' ? $data : ($data[$key] ?? '');
}
function metadata_exists($kind, $id, $key) { return isset($GLOBALS['meta'][$kind][$id][$key]); }
function update_metadata($kind, $id, $key, $value) { $GLOBALS['meta'][$kind][$id][$key] = wp_unslash($value); }
function wp_slash($value) { return is_array($value) ? array_map('wp_slash', $value) : (is_string($value) ? addslashes($value) : $value); }
function wp_unslash($value) { return is_array($value) ? array_map('wp_unslash', $value) : (is_string($value) ? stripslashes($value) : $value); }
function acf_get_field($key) { return $GLOBALS['acf_fields'][$key] ?? false; }
function wp_json_encode($value) { return json_encode($value); }
function wp_strip_all_tags($value) { return strip_tags($value); }
function sanitize_text_field($value) { return trim(strip_tags($value)); }
function wp_kses_post($value) { return strip_tags($value, '<p><a><strong><em>'); }
function get_object_taxonomies($type) { return array('event-type'); }
function wp_get_object_terms($id, $taxonomy) { return array((object) array('term_id' => 102, 'term_taxonomy_id' => 999)); }
function is_wp_error($value) { return false; }
function __($value, $domain) { return $value; }
function sanitize_key($value) { return preg_replace('/[^a-z0-9_-]/', '', strtolower($value)); }
function get_taxonomy($value) { return $value === 'event-type' ? (object) array('cap' => (object) array('edit_terms' => 'manage_categories')) : false; }
function current_user_can($cap) { return $GLOBALS['can_edit'] ?? true; }
function get_term_by($key, $id, $taxonomy) { return $id === 999 ? (object) array('term_id' => 102, 'term_taxonomy_id' => 999, 'taxonomy' => 'event-type') : false; }
function wp_verify_nonce($nonce, $action) { return $nonce === 'valid'; }
function wpml_is_action_authenticated($action) { return ($_POST['_icl_nonce'] ?? '') === 'valid'; }
function wp_send_json_error($data = null, $status = null) { throw new RuntimeException('JSON error ' . $status); }
class WP_Post { public $ID = 12; public $post_type = 'catering'; }
class WP_Term { public $term_id = 102; }
class WPML_TM_Field_Type_Encoding {
    public static function decode($key) {
        return preg_match('/^field-(.*?)-\d+(?:-|$)/', $key, $m) ? array($m[1], array()) : array('', array());
    }
}
require __DIR__ . '/../includes/acf-simple-repeater-wpml.php';
function check($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
    $GLOBALS['checks']++;
}
function seed($kind, $id, $name, $rows, $preference = 2) {
    $reference = 'field_' . $name;
    $GLOBALS['acf_fields'][$reference] = array('type' => 'iu_simple_repeater', 'wpml_cf_preferences' => $preference);
    $GLOBALS['meta'][$kind][$id][$name] = $rows;
    $GLOBALS['meta'][$kind][$id]['_' . $name] = $reference;
}
function delivery($package) {
    $fields = $elements = array();
    foreach ($package['contents'] as $key => $entry) {
        $elements[] = (object) array('field_type' => $key, 'field_data' => $entry['data'] ?? '');
        if (!empty($entry['translate']) && strpos($key, 'iu-sr-') === 0) {
            $fields[$key] = array('field_type' => $key, 'finished' => 1, 'data' => 'EN ' . base64_decode($entry['data']));
        }
    }
    return array($fields, (object) array('elements' => $elements, 'original_doc_id' => 12, 'language_code' => 'en'));
}
$adapter = new IU_Simple_Repeater_WPML();
$rows = array(
    0 => array('title' => 'Question', 'text' => '<p>Answer \\ path</p>', 'image' => 37, 'link' => 'https://example.com/?a=1&b=2'),
    3 => array('title' => '45+', 'text' => 'Guests', 'image' => 0, 'link' => ''),
    8 => array('title' => '80x80', 'text' => '', 'extra' => array('count' => 9)),
);
seed('post', 12, 'faq', $rows);
seed('post', 12, 'empty', array());
seed('post', 12, 'copy_only', $rows, 1);
seed('term', 102, 'stats', $rows);
$meta['post'][12]['unrelated'] = $rows;
$package = $adapter->build_job(array('contents' => array(
    'title' => array('translate' => 1, 'data' => base64_encode('Post title')),
    'field-faq-0-0-title' => array(), 'field-faq-0-0-title-name' => array(),
    'field-other-0' => array(), 'tfield-stats-999_0_title' => array(),
)), new WP_Post());
check(!isset($package['contents']['field-faq-0-0-title']), 'Remove generic post units');
check(!isset($package['contents']['tfield-stats-999_0_title']), 'Use term_taxonomy_id for generic term removal');
check(isset($package['contents']['field-other-0']), 'Preserve unrelated package units');
list($translated, $job) = delivery($package);
check(count($translated) === 6, 'Only title/text with words are translatable, for post and term');
seed('post', 112, 'empty', $rows);
$adapter->save_job(112, $translated, $job);
$result = get_metadata('post', 112, 'faq', true);
check(array_keys($result) === array(0, 3, 8), 'Preserve sparse row keys and order');
check($result[0]['title'] === 'EN Question', 'Translated title');
check($result[0]['text'] === 'EN <p>Answer \\ path</p>', 'HTML and backslash round trip');
check($result[0]['image'] === 37 && $result[0]['link'] === $rows[0]['link'], 'Copy image and URL without type changes');
check($result[3]['title'] === '45+' && $result[8] === $rows[8], 'Copy numbers and unknown row properties');
check(get_metadata('post', 112, 'empty', true) === array(), 'Clear previously populated target on empty source');
check(!metadata_exists('post', 112, 'copy_only'), 'Leave explicit Copy preference to WPML');
check(get_metadata('term', 202, 'stats', true) === $result, 'Attached taxonomy term round trip');
check(get_metadata('term', 202, '_stats', true) === 'field_stats', 'Preserve ACF reference');
check(unserialize(serialize($result)) === $result, 'Legacy serialized representation remains compatible');
$before = $meta;
$meta['post'][12]['faq'] = array_reverse($rows, true);
$adapter->save_job(112, $translated, $job);
check($meta['post'][112] === $before['post'][112], 'Reject stale job after reordering');
$meta['post'][12]['faq'] = $rows;
unset($translated[array_key_first($translated)]);
$meta['post'][112]['faq'][0]['title'] = 'Keep manual translation';
$adapter->save_job(112, $translated, $job);
check($meta['post'][112]['faq'][0]['title'] === 'Keep manual translation', 'Incomplete delivery must not overwrite a field');
$units = $adapter->term_units(array('other' => 'Other meta', 'stats' => 'serialized blob'), 102, 'event-type');
check(!isset($units['stats']) && $units['other'] === 'Other meta', 'Standalone terms expose only leaves, preserve other meta');
unset($units['other']);
check(count($units) === 3, 'Standalone term leaf count');
$meta['term'][202]['stats'] = array(array('title' => 'Old translation'));
foreach ($units as $key => $value) {
    check($adapter->save_term_unit(false, 202, $key, 'EN ' . $value, array('sourceTermId' => 102)) === true, 'Consume virtual key');
}
check($meta['term'][202]['stats'] === $result, 'Standalone taxonomy reconstruction');
$before = $meta['term'][202];
$meta['term'][102]['stats'][0]['title'] = 'Edited while translating';
foreach ($units as $key => $value) {
    $adapter->save_term_unit(false, 202, $key, 'Stale answer', array('sourceTermId' => 102));
}
check($meta['term'][202] === $before, 'Standalone terms reject stale units');
seed('term', 102, 'stats', array());
$adapter->copy_term_constants(202, 2002, 'event-type');
check($meta['term'][202]['stats'] === array(), 'Standalone empty term repeater clears target');
seed('term', 102, 'stats', array(array('title' => '123', 'image' => 55, 'link' => 'https://example.com/')));
$adapter->copy_term_constants(202, 2002, 'event-type');
check($meta['term'][202]['stats'] === $meta['term'][102]['stats'], 'Copy-only rows need no translation units');
check($adapter->save_term_unit(false, 202, 'unrelated', 'test', array()) === false, 'Ignore other adapters');
check(in_array('stats', $adapter->term_keys(array('other'), 'event-type', new WP_Term()), true), 'Discover term field from its ACF reference');
check($adapter->build_job(array('contents' => array()), (object) array('ID' => 12)) === array('contents' => array()), 'Do not process external WPML packages as posts');
$labels = $adapter->label_fields(array(array('field_type' => 'iu-sr-post-12-666171-30-title')));
check($labels[0]['title'] === 'faq / 1 / Title', 'Readable field and row label');
seed('term', 102, 'stats', $rows);
$acf_fields['field_stats']['label'] = 'Statistics';
$acf_fields['field_stats']['text_label'] = 'Caption';
seed('term', 102, 'empty', array());
seed('term', 102, 'numbers', array(array('title' => '45+', 'image' => 55)));
seed('term', 102, 'copy_only', $rows, 1);
$meta['term'][202]['stats'] = $result;
$editor = $adapter->editor_fields(102, 202);
check(count($editor['stats']['units']) === 3, 'Manual modal exposes individual text units only');
check($editor['stats']['units']['30-title']['value'] === 'EN Question', 'Manual editor prefills saved translation');
check($editor['stats']['units']['33-text']['label'] === '2 / Caption', 'Manual labels use sequential position and ACF label');
check($editor['empty']['units'] === array() && !isset($editor['copy_only']), 'Manual editor handles empty field and respects Copy preference');
$submitted = array();
foreach ($editor as $name => $field) {
    $submitted[$name] = array('fingerprint' => $field['fingerprint'], 'values' => array());
    foreach ($field['units'] as $key => $unit) { $submitted[$name]['values'][$key] = 'Manual ' . $unit['source']; }
}
$converted = $adapter->editor_meta(102, $submitted);
$restored = wp_unslash(unserialize(stripslashes($converted['stats']), array('allowed_classes' => false)));
check($restored[0]['text'] === 'Manual <p>Answer \\ path</p>', 'Manual WPML serialize/unslash path preserves HTML and backslash');
check($restored[3]['title'] === '45+' && $restored[0]['image'] === 37 && $restored[0]['link'] === $rows[0]['link'], 'Manual save copies numeric/media/link values');
check(array_keys($restored) === array(0, 3, 8) && $converted['_stats'] === 'field_stats', 'Manual save preserves row order and ACF reference');
check(unserialize(stripslashes($converted['empty'])) === array(), 'Manual empty repeater is saved as empty array');
$incomplete = $submitted;
unset($incomplete['stats']['values']['30-title']);
check($adapter->editor_meta(102, $incomplete) === false, 'Reject missing manual translation unit');
$incomplete = $submitted;
unset($incomplete['empty']);
check($adapter->editor_meta(102, $incomplete) === false, 'Reject missing manual field');
$changed = $submitted;
$changed['stats']['fingerprint'] = str_repeat('0', 64);
check($adapter->editor_meta(102, $changed) === false, 'Reject stale manual submission');
$cleared = $submitted;
$cleared['stats']['values']['30-title'] = '';
$cleared_meta = $adapter->editor_meta(102, $cleared);
check(unserialize(stripslashes($cleared_meta['stats']))[0]['title'] === '', 'Allow explicitly blank translated text');
$language_details = (object) array('source_language_code' => null, 'language_code' => 'el', 'trid' => '321');
$payload = array('nonce' => 'valid', 'source' => 999, 'fields' => $submitted);
$_POST = array('taxonomy' => 'event-type', 'trid' => '321', 'term_language_code' => 'en', '_icl_nonce' => 'valid',
    'meta_data' => array('rank_math_title' => 'SEO untouched', '_iu_sr_translation' => wp_slash(json_encode($payload))));
$valid_post = $_POST;
$adapter->prepare_term_editor_save();
check($_POST['meta_data']['rank_math_title'] === 'SEO untouched' && !isset($_POST['meta_data']['_iu_sr_translation']), 'Preserve Rank Math meta and consume bridge payload');
check($_POST['meta_data']['stats'] === $converted['stats'], 'Manual save hooks into existing WPML write path');
foreach (array('nonce', 'wpml_nonce', 'capability', 'trid', 'taxonomy', 'source', 'stale') as $failure) {
    $_POST = $valid_post;
    $can_edit = true;
    $bad_payload = $payload;
    if ($failure === 'nonce') { $bad_payload['nonce'] = 'invalid'; }
    if ($failure === 'source') { $bad_payload['source'] = 12345; }
    if ($failure === 'stale') { $bad_payload['fields']['stats']['fingerprint'] = 'old'; }
    $_POST['meta_data']['_iu_sr_translation'] = wp_slash(json_encode($bad_payload));
    if ($failure === 'wpml_nonce') { $_POST['_icl_nonce'] = 'invalid'; }
    if ($failure === 'capability') { $can_edit = false; }
    if ($failure === 'trid') { $_POST['trid'] = '987'; }
    if ($failure === 'taxonomy') { $_POST['taxonomy'] = 'other'; }
    $blocked = false;
    try { $adapter->prepare_term_editor_save(); } catch (RuntimeException $error) { $blocked = strpos($error->getMessage(), 'JSON error') === 0; }
    check($blocked, 'Block invalid manual submission: ' . $failure);
}
echo 'PASS: ' . $checks . " checks\n";
