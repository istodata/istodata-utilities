<?php
// Safety check
if (!defined('ABSPATH')) { exit; }

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

if (!class_exists('IU_WPML_Shortcode_Widget')) {
    class IU_WPML_Shortcode_Widget extends Widget_Base {
        public function get_name() {
            return 'iu_wpml_shortcode_widget';
        }

        public function get_title() {
            return __('Multilingual Shortcode', 'istodata-utilities');
        }

        public function get_icon() {
            return 'eicon-shortcode';
        }

        public function get_categories() {
            return array('istodata-kit');
        }

        protected function register_controls() {
            $languages = $this->get_languages();

            $this->start_controls_section('section_content', array(
                'label' => __('Content', 'istodata-utilities'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ));

            if (!$this->is_wpml_available()) {
                $this->add_control('wpml_required_notice', array(
                    'type' => Controls_Manager::RAW_HTML,
                    'raw' => esc_html__('WPML is required for this widget.', 'istodata-utilities'),
                    'content_classes' => 'elementor-panel-alert elementor-panel-alert-warning',
                ));
            } else if (empty($languages)) {
                $this->add_control('wpml_languages_notice', array(
                    'type' => Controls_Manager::RAW_HTML,
                    'raw' => esc_html__('No active WPML languages were found. Make sure WPML languages are configured.', 'istodata-utilities'),
                    'content_classes' => 'elementor-panel-alert elementor-panel-alert-warning',
                ));
            } else {
                foreach ($languages as $language) {
                    $language_code = !empty($language['language_code']) ? sanitize_key($language['language_code']) : '';
                    if ($language_code === '') {
                        continue;
                    }

                    $label = $this->get_language_control_label($language);

                    $this->add_control('shortcode_' . $language_code, array(
                        'label' => sprintf(__('Shortcode (%s)', 'istodata-utilities'), $label),
                        'type' => Controls_Manager::TEXTAREA,
                        'rows' => 3,
                        'placeholder' => '[your-shortcode]',
                    ));
                }
            }

            $this->end_controls_section();
        }

        protected function render() {
            $settings = $this->get_settings_for_display();
            $is_editor = $this->is_editor_mode();

            if (!$this->is_wpml_available()) {
                if ($is_editor) {
                    echo '<div class="iu-wpml-shortcode-notice">' . esc_html__('WPML is required for this widget.', 'istodata-utilities') . '</div>';
                }
                return;
            }

            $current_language = $this->get_current_language_code();
            $shortcode = '';

            if ($current_language !== '') {
                $control_key = 'shortcode_' . $current_language;
                if (!empty($settings[$control_key])) {
                    $shortcode = $this->normalize_shortcode_value($settings[$control_key]);
                }
            }

            if ($shortcode === '') {
                if ($is_editor) {
                    echo '<div class="iu-wpml-shortcode-notice">' . esc_html__('Add a shortcode for the current language.', 'istodata-utilities') . '</div>';
                }
                return;
            }

            echo do_shortcode($shortcode);
        }

        private function is_wpml_available() {
            return defined('ICL_SITEPRESS_VERSION') || has_filter('wpml_active_languages') || function_exists('icl_object_id');
        }

        private function is_editor_mode() {
            return class_exists('Elementor\\Plugin')
                && \Elementor\Plugin::$instance
                && \Elementor\Plugin::$instance->editor
                && \Elementor\Plugin::$instance->editor->is_edit_mode();
        }

        private function get_languages() {
            if (!$this->is_wpml_available()) {
                return array();
            }

            $languages = apply_filters('wpml_active_languages', null, array(
                'skip_missing' => 0,
            ));

            if (!is_array($languages) || empty($languages)) {
                return array();
            }

            $normalized = array();
            foreach ($languages as $language) {
                if (is_array($language) && !empty($language['language_code'])) {
                    $normalized[] = $language;
                }
            }

            return $normalized;
        }

        private function get_current_language_code() {
            $language_code = apply_filters('wpml_current_language', null);
            if (is_string($language_code) && $language_code !== '') {
                return sanitize_key($language_code);
            }

            $languages = $this->get_languages();
            foreach ($languages as $language) {
                if (!empty($language['active']) && !empty($language['language_code'])) {
                    return sanitize_key($language['language_code']);
                }
            }

            return '';
        }

        private function get_language_control_label($language) {
            $code = !empty($language['language_code']) ? strtoupper((string) $language['language_code']) : '';

            if (!empty($language['native_name'])) {
                return sprintf('%s - %s', $code, (string) $language['native_name']);
            }

            if (!empty($language['translated_name'])) {
                return sprintf('%s - %s', $code, (string) $language['translated_name']);
            }

            return $code;
        }

        private function normalize_shortcode_value($value) {
            return trim((string) $value);
        }
    }
}
