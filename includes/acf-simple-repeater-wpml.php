<?php
if (!defined('ABSPATH')) { exit; }

/** Translate row leaves while retaining the original ACF meta representation. */
class IU_Simple_Repeater_WPML {
    const PREFIX = 'iu-sr-';
    const MANIFEST = 'iu-sr-manifest';
    private $term_deliveries = array();

    public function __construct() {
        add_filter('wpml_tm_translation_job_data', array($this, 'build_job'), 100, 2);
        add_action('wpml_pro_translation_completed', array($this, 'save_job'), 100, 3);
        add_filter('wpml_tm_adjust_translation_fields', array($this, 'label_fields'), 100);
        // WPML 5 taxonomy jobs use scalar term-meta units, not post job packages.
        add_filter('wpml_translatable_term_meta', array($this, 'term_keys'), 100, 3);
        add_filter('wpml_translatable_term_meta_values', array($this, 'term_units'), 100, 3);
        add_filter('wpml_apply_translated_term_meta', array($this, 'save_term_unit'), 100, 5);
        add_action('created_term', array($this, 'copy_term_constants'), 100, 3);
        add_action('edited_term', array($this, 'copy_term_constants'), 100, 3);
        // WPML enqueues the taxonomy editor during page rendering, after admin_enqueue_scripts.
        add_action('admin_footer', array($this, 'enqueue_term_editor'), 5);
        add_action('wp_ajax_iu_sr_term_editor', array($this, 'load_term_editor'));
        add_action('wp_ajax_wpml_save_term', array($this, 'prepare_term_editor_save'), 1);
    }

    public function enqueue_term_editor() {
        if (!wp_script_is('term-popup-view', 'enqueued')) {
            return;
        }
        $path = dirname(__DIR__) . '/assets/js/iu-simple-repeater-wpml.js';
        wp_enqueue_script('iu-simple-repeater-wpml', IU_PLUGIN_URL . 'assets/js/iu-simple-repeater-wpml.js', array('term-popup-view'), filemtime($path), true);
        wp_localize_script('iu-simple-repeater-wpml', 'iuRepeaterWPML', array(
            'nonce' => wp_create_nonce('iu_sr_term_editor'),
            'loading' => __('Loading repeater translations...', 'istodata-utilities'),
            'error' => __('Repeater translations could not be loaded or saved. Reopen this window and try again.', 'istodata-utilities'),
            'copied' => __('Numbers, images and links are copied from the original.', 'istodata-utilities'),
        ));
    }

    private function editor_source($tt_id, $taxonomy, $trid, $language) {
        $tax = get_taxonomy($taxonomy);
        if (!$tax || !current_user_can($tax->cap->edit_terms)) {
            return false;
        }
        $term = get_term_by('term_taxonomy_id', (int) $tt_id, $taxonomy);
        if (!$term || $term->taxonomy !== $taxonomy) {
            return false;
        }
        $details = apply_filters('wpml_element_language_details', null, array('element_id' => $term->term_taxonomy_id, 'element_type' => 'tax_' . $taxonomy));
        if (!$details || !empty($details->source_language_code) || (string) $details->trid !== (string) $trid || !$language || $details->language_code === $language) {
            return false;
        }
        return $term;
    }

    public function editor_fields($source_id, $target_id) {
        $result = array();
        foreach ($this->fields('term', $source_id) as $name => $field) {
            $definition = acf_get_field($field['reference']);
            $translated = $target_id ? get_metadata('term', $target_id, $name, true) : array();
            $units = array();
            $positions = array_flip(array_keys($field['rows']));
            foreach ($this->units($field['rows']) as $key => $unit) {
                $label_key = $unit['key'] . '_label';
                $label = !empty($definition[$label_key]) ? $definition[$label_key] : ($unit['key'] === 'title' ? 'Title' : 'Description');
                $value = isset($translated[$unit['index']][$unit['key']]) && is_string($translated[$unit['index']][$unit['key']]) ? $translated[$unit['index']][$unit['key']] : '';
                $units[$key] = array('label' => ($positions[$unit['index']] + 1) . ' / ' . $label, 'source' => $unit['value'], 'value' => $value, 'multiline' => $unit['key'] === 'text');
            }
            $result[$name] = array('label' => !empty($definition['label']) ? $definition['label'] : $name, 'fingerprint' => $this->fingerprint($field['rows']), 'units' => $units);
        }
        return $result;
    }

    public function load_term_editor() {
        check_ajax_referer('iu_sr_term_editor', 'nonce');
        $taxonomy = isset($_POST['taxonomy']) ? sanitize_key($_POST['taxonomy']) : '';
        $language = isset($_POST['language']) ? sanitize_key($_POST['language']) : '';
        $trid = isset($_POST['trid']) ? (string) wp_unslash($_POST['trid']) : '';
        $tt_id = isset($_POST['source']) ? (int) $_POST['source'] : 0;
        $source = $this->editor_source($tt_id, $taxonomy, $trid, $language);
        if (!$source) {
            wp_send_json_error(null, 403);
            return;
        }
        $target = apply_filters('wpml_object_id', $source->term_id, $taxonomy, false, $language);
        wp_send_json_success($this->editor_fields($source->term_id, $target && (int) $target !== (int) $source->term_id ? $target : 0));
    }

    /** Validate the whole editor submission before WPML changes any term data. */
    public function editor_meta($source_id, $submitted) {
        $current = $this->fields('term', $source_id);
        if (!is_array($submitted) || array_diff_key($current, $submitted) || array_diff_key($submitted, $current)) {
            return false;
        }
        $meta = array();
        foreach ($current as $name => $field) {
            $input = $submitted[$name];
            if (!is_array($input) || !isset($input['fingerprint'], $input['values']) || $input['fingerprint'] !== $this->fingerprint($field['rows']) || !is_array($input['values'])) {
                return false;
            }
            $units = $this->units($field['rows']);
            if (array_diff_key($units, $input['values']) || array_diff_key($input['values'], $units)) {
                return false;
            }
            $rows = $field['rows'];
            foreach ($units as $key => $unit) {
                if (!is_string($input['values'][$key])) {
                    return false;
                }
                $rows[$unit['index']][$unit['key']] = $this->sanitize($input['values'][$key], $unit['key']);
            }
            // WPML unslashes the serialized string, then add_term_meta unslashes the rows.
            $meta[$name] = wp_slash(serialize(wp_slash($rows)));
            $meta['_' . $name] = wp_slash($field['reference']);
        }
        return $meta;
    }

    public function prepare_term_editor_save() {
        $key = '_iu_sr_translation';
        if (!isset($_POST['meta_data'][$key])) {
            return;
        }
        $payload = is_string($_POST['meta_data'][$key]) ? json_decode(wp_unslash($_POST['meta_data'][$key]), true) : null;
        if (!is_array($payload) || !isset($payload['nonce']) || !wp_verify_nonce($payload['nonce'], 'iu_sr_term_editor') || !wpml_is_action_authenticated('wpml_save_term')) {
            wp_send_json_error(null, 403);
            return;
        }
        $source = $this->editor_source(isset($payload['source']) ? (int) $payload['source'] : 0,
            isset($_POST['taxonomy']) ? sanitize_key($_POST['taxonomy']) : '',
            isset($_POST['trid']) ? (string) wp_unslash($_POST['trid']) : '',
            isset($_POST['term_language_code']) ? sanitize_key($_POST['term_language_code']) : '');
        $meta = $source ? $this->editor_meta($source->term_id, isset($payload['fields']) ? $payload['fields'] : null) : false;
        if ($meta === false) {
            wp_send_json_error(array('message' => __('The repeater changed. Reopen the translation window before saving.', 'istodata-utilities')), 409);
            return;
        }
        unset($_POST['meta_data'][$key]);
        $_POST['meta_data'] = array_merge($_POST['meta_data'], $meta);
    }

    private function fields($kind, $id) {
        if (!function_exists('acf_get_field')) {
            return array();
        }
        $result = array();
        foreach ((array) get_metadata($kind, $id) as $key => $values) {
            if (strpos($key, '_') === 0 || !metadata_exists($kind, $id, '_' . $key)) {
                continue;
            }
            $reference = get_metadata($kind, $id, '_' . $key, true);
            $field = is_string($reference) ? acf_get_field($reference) : false;
            if (!$field || $field['type'] !== 'iu_simple_repeater') {
                continue;
            }
            // Honour explicitly saved Ignore, Copy and Copy Once preferences.
            if (isset($field['wpml_cf_preferences']) && (int) $field['wpml_cf_preferences'] !== 2) {
                continue;
            }
            $rows = get_metadata($kind, $id, $key, true);
            if (!is_array($rows)) {
                continue;
            }
            $result[$key] = array('reference' => $reference, 'rows' => $rows);
        }
        return $result;
    }

    private function units($rows) {
        $units = array();
        foreach ($rows as $index => $row) {
            if (!is_array($row)) {
                continue;
            }
            foreach (array('title', 'text') as $key) {
                if (!isset($row[$key]) || !is_string($row[$key]) || trim($row[$key]) === '') {
                    continue;
                }
                // Preserve numbers/statistics, including 4.500, 45+, 10% and 80x80.
                if (!preg_match('/\p{L}/u', preg_replace('/(?<=\d)[xX](?=\d)/', '', wp_strip_all_tags($row[$key])))) {
                    continue;
                }
                $units[bin2hex((string) $index) . '-' . $key] = array(
                    'index' => $index, 'key' => $key, 'value' => $row[$key],
                );
            }
        }
        return $units;
    }

    private function fingerprint($rows) {
        return hash('sha256', serialize($rows));
    }

    public function build_job($package, $post) {
        if (!($post instanceof WP_Post) || !isset($package['contents']) || !is_array($package['contents'])) {
            return $package;
        }
        $objects = array(array('kind' => 'post', 'id' => $post->ID, 'taxonomy' => ''));
        foreach (get_object_taxonomies($post->post_type) as $taxonomy) {
            if (!apply_filters('wpml_is_translated_taxonomy', false, $taxonomy)) {
                continue;
            }
            $terms = wp_get_object_terms($post->ID, $taxonomy);
            if (is_wp_error($terms)) {
                continue;
            }
            foreach ($terms as $term) {
                $objects[] = array('kind' => 'term', 'id' => $term->term_id, 'taxonomy' => $taxonomy, 'tt_id' => $term->term_taxonomy_id);
            }
        }
        $manifest = array();
        foreach ($objects as $object) {
            foreach ($this->fields($object['kind'], $object['id']) as $name => $field) {
                $entry = array_merge($object, $field, array('name' => $name));
                $token = self::PREFIX . $object['kind'] . '-' . $object['id'] . '-' . bin2hex($name);
                $manifest[$token] = $entry;
                // Remove WPML's generic extraction only for this identified repeater.
                foreach (array_keys($package['contents']) as $key) {
                    $owned = false;
                    if ($object['kind'] === 'post' && class_exists('WPML_TM_Field_Type_Encoding')) {
                        list($meta_name) = WPML_TM_Field_Type_Encoding::decode($key);
                        $owned = $meta_name === $name;
                    } elseif ($object['kind'] === 'term') {
                        $prefix = 'tfield-' . $name . '-' . $object['tt_id'];
                        $owned = $key === $prefix || strpos($key, $prefix . '_') === 0;
                    }
                    if ($owned) {
                        unset($package['contents'][$key]);
                    }
                }
                foreach ($this->units($field['rows']) as $suffix => $unit) {
                    $package['contents'][$token . '-' . $suffix] = array(
                        'translate' => 1, 'data' => base64_encode($unit['value']), 'format' => 'base64',
                    );
                }
            }
        }
        if ($manifest) {
            // The immutable job snapshot carries structure and non-translated values.
            $package['contents'][self::MANIFEST] = array('translate' => 0, 'data' => wp_json_encode($manifest));
        }
        return $package;
    }

    public function label_fields($fields) {
        foreach ($fields as &$field) {
            if (isset($field['field_type']) && preg_match('/^iu-sr-(?:post|term)-\d+-((?:[a-f0-9]{2})+)-((?:[a-f0-9]{2})+)-(title|text)$/', $field['field_type'], $match)) {
                $index = hex2bin($match[2]);
                $field['title'] = sprintf('%s / %s / %s', hex2bin($match[1]), ctype_digit($index) ? (int) $index + 1 : $index, $match[3] === 'title' ? 'Title' : 'Description');
                $field['group'] = 'ISTODATA Simple Repeater';
            }
        }
        return $fields;
    }

    public function save_job($target_id, $fields, $job) {
        if (empty($job->elements) || empty($job->original_doc_id) || (int) $target_id === (int) $job->original_doc_id) {
            return;
        }
        $manifest = array();
        foreach ($job->elements as $element) {
            if ($element->field_type === self::MANIFEST) {
                $manifest = json_decode($element->field_data, true);
                break;
            }
        }
        if (!is_array($manifest)) {
            return;
        }
        $translations = array();
        foreach ((array) $fields as $key => $field) {
            if (is_array($field) && !empty($field['finished']) && isset($field['data']) && is_string($field['data'])) {
                $translations[isset($field['field_type']) ? $field['field_type'] : $key] = $field['data'];
            }
        }
        foreach ($manifest as $token => $entry) {
            if (!isset($entry['kind'], $entry['id'], $entry['name'], $entry['rows'], $entry['reference']) || !in_array($entry['kind'], array('post', 'term'), true)) {
                continue;
            }
            $current = $this->fields($entry['kind'], $entry['id']);
            if (!isset($current[$entry['name']]) || $current[$entry['name']] !== array('reference' => $entry['reference'], 'rows' => $entry['rows'])) {
                continue; // A stale job must never apply translations to reordered/edited rows.
            }
            if ($entry['kind'] === 'post') {
                if ((int) $entry['id'] !== (int) $job->original_doc_id) {
                    continue;
                }
                $destination = $target_id;
            } else {
                $destination = apply_filters('wpml_object_id', $entry['id'], $entry['taxonomy'], false, $job->language_code);
            }
            if (!$destination || (int) $destination === (int) $entry['id']) {
                continue;
            }
            $rows = $entry['rows'];
            $complete = true;
            foreach ($this->units($rows) as $suffix => $unit) {
                $key = $token . '-' . $suffix;
                if (!array_key_exists($key, $translations)) {
                    $complete = false;
                    break;
                }
                $rows[$unit['index']][$unit['key']] = $this->sanitize($translations[$key], $unit['key']);
            }
            if ($complete) {
                $this->write($entry['kind'], $destination, $entry['name'], $entry['reference'], $rows);
            }
        }
    }

    private function sanitize($value, $key) {
        return $key === 'title' ? sanitize_text_field($value) : wp_kses_post($value);
    }

    private function write($kind, $id, $name, $reference, $rows) {
        update_metadata($kind, $id, $name, wp_slash($rows));
        update_metadata($kind, $id, '_' . $name, $reference);
        if (function_exists('acf_flush_value_cache')) {
            acf_flush_value_cache($kind === 'term' ? 'term_' . $id : $id, $name);
        }
    }

    public function term_units($values, $term_id, $taxonomy) {
        foreach ($this->fields('term', $term_id) as $name => $field) {
            unset($values[$name]);
            $prefix = self::PREFIX . bin2hex($name) . '-' . $this->fingerprint($field['rows']) . '-';
            foreach ($this->units($field['rows']) as $suffix => $unit) {
                $values[$prefix . $suffix] = $unit['value'];
            }
        }
        return $values;
    }

    public function term_keys($keys, $taxonomy, $term = null) {
        $keys = is_array($keys) ? $keys : array();
        if ($term instanceof WP_Term) {
            return array_values(array_unique(array_merge($keys, array_keys($this->fields('term', $term->term_id)))));
        }
        // WPML also asks without a term when detecting source-meta changes.
        if (function_exists('acf_get_field_groups') && function_exists('acf_get_fields')) {
            foreach (acf_get_field_groups(array('taxonomy' => $taxonomy)) as $group) {
                foreach ((array) acf_get_fields($group) as $field) {
                    if ($field['type'] === 'iu_simple_repeater' && (!isset($field['wpml_cf_preferences']) || (int) $field['wpml_cf_preferences'] === 2)) {
                        $keys[] = $field['name'];
                    }
                }
            }
        }
        return array_values(array_unique($keys));
    }

    public function save_term_unit($handled, $target_id, $key, $value, $context) {
        if ($handled || strpos($key, self::PREFIX) !== 0) {
            return $handled;
        }
        if (!preg_match('/^iu-sr-((?:[a-f0-9]{2})+)-([a-f0-9]{64})-((?:[a-f0-9]{2})+)-(title|text)$/', $key, $match)) {
            return true;
        }
        $source_id = isset($context['sourceTermId']) ? (int) $context['sourceTermId'] : 0;
        if (!$source_id || $source_id === (int) $target_id) {
            return true;
        }
        $name = hex2bin($match[1]);
        $fields = $this->fields('term', $source_id);
        if (!isset($fields[$name]) || $this->fingerprint($fields[$name]['rows']) !== $match[2]) {
            return true;
        }
        $field = $fields[$name];
        $units = $this->units($field['rows']);
        $suffix = $match[3] . '-' . $match[4];
        if (!isset($units[$suffix])) {
            return true;
        }
        $delivery_key = $target_id . ':' . $source_id . ':' . $name . ':' . $match[2];
        $this->term_deliveries[$delivery_key][$suffix] = $this->sanitize($value, $match[4]);
        $received = $this->term_deliveries[$delivery_key];
        if (!array_diff_key($units, $received)) {
            $rows = $field['rows'];
            foreach ($units as $unit_key => $unit) {
                $rows[$unit['index']][$unit['key']] = $received[$unit_key];
            }
            $this->write('term', $target_id, $name, $field['reference'], $rows);
            unset($this->term_deliveries[$delivery_key]);
        }
        return true;
    }

    public function copy_term_constants($term_id, $tt_id, $taxonomy) {
        $language = apply_filters('wpml_element_language_details', null, array('element_id' => $tt_id, 'element_type' => 'tax_' . $taxonomy));
        if (!$language || empty($language->source_language_code)) {
            return;
        }
        $source_id = apply_filters('wpml_object_id', $term_id, $taxonomy, false, $language->source_language_code);
        if (!$source_id || (int) $source_id === (int) $term_id) {
            return;
        }
        foreach ($this->fields('term', $source_id) as $name => $field) {
            if (!$this->units($field['rows'])) {
                $this->write('term', $term_id, $name, $field['reference'], $field['rows']);
            }
        }
    }
}

new IU_Simple_Repeater_WPML();
