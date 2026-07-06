<?php
// Safety check
if (!defined('ABSPATH')) { exit; }

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use Elementor\Repeater;
use Elementor\Icons_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Text_Shadow;

if (!class_exists('IU_Typed_Widget')) {
    class IU_Typed_Widget extends Widget_Base {
        public function get_name() { return 'iu_typed'; }
        public function get_title() { return __('Typed', 'istodata-utilities'); }
        public function get_icon() { return 'eicon-animation-text'; }
        public function get_categories() { return [ 'istodata-kit' ]; }

        public function get_style_depends() {
            if (!wp_style_is('iu-typed', 'registered')) {
                wp_register_style(
                    'iu-typed',
                    IU_PLUGIN_URL . 'assets/css/typed.css',
                    array(),
                    defined('IU_PLUGIN_VERSION') ? IU_PLUGIN_VERSION : null
                );
            }
            return [ 'iu-typed' ];
        }

        public function get_script_depends() {
            if (!wp_script_is('iu-typed-js', 'registered')) {
                wp_register_script(
                    'iu-typed-js',
                    IU_PLUGIN_URL . 'assets/js/typed.js',
                    array(),
                    defined('IU_PLUGIN_VERSION') ? IU_PLUGIN_VERSION : null,
                    true
                );
            }
            return [ 'iu-typed-js' ];
        }

        protected function register_controls() {
            // Content
            $this->start_controls_section('section_content', [
                'label' => __('Περιεχόμενο', 'istodata-utilities'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]);

            // Static prefix/suffix around the typed text
            $this->add_control('before_text', [
                'label' => __('Σταθερό κείμενο πριν', 'istodata-utilities'),
                'type' => Controls_Manager::TEXTAREA,
                'rows' => 2,
                'dynamic' => [ 'active' => true ],
                'default' => '',
            ]);

            $rep = new Repeater();
            $rep->add_control('text', [
                'label' => __('Κείμενο', 'istodata-utilities'),
                'type' => Controls_Manager::TEXTAREA,
                'rows' => 2,
                'dynamic' => [ 'active' => true ],
                'default' => __('Παράδειγμα κειμένου', 'istodata-utilities'),
            ]);
            $rep->add_control('link', [
                'label' => __('Σύνδεσμος', 'istodata-utilities'),
                'type' => Controls_Manager::URL,
                'dynamic' => [ 'active' => true ],
                'placeholder' => 'https://',
            ]);
            $rep->add_control('item_class', [
                'label' => __('CSS Class', 'istodata-utilities'),
                'type' => Controls_Manager::TEXT,
            ]);
            $rep->add_control('icon', [
                'label' => __('Εικονίδιο', 'istodata-utilities'),
                'type'  => Controls_Manager::ICONS,
                'default' => [ 'value' => 'fas fa-arrow-up', 'library' => 'fa-solid' ],
            ]);

            $this->add_control('items', [
                'label' => __('Εναλλασσόμενα Κείμενα', 'istodata-utilities'),
                'type' => Controls_Manager::REPEATER,
                'fields' => $rep->get_controls(),
                'title_field' => '{{{ text }}}',
                'default' => [
                    [ 'text' => __('Παράδειγμα 1', 'istodata-utilities') ],
                    [ 'text' => __('Παράδειγμα 2', 'istodata-utilities') ],
                ],
            ]);

            // Move suffix after items in the UI
            $this->add_control('after_text', [
                'label' => __('Σταθερό κείμενο μετά', 'istodata-utilities'),
                'type' => Controls_Manager::TEXTAREA,
                'rows' => 2,
                'dynamic' => [ 'active' => true ],
                'default' => '',
            ]);

            // Removed: Wrapper CSS Class (use Elementor's Advanced → CSS Classes instead)

            // Wrapper HTML tag (for semantic headings like H2)
            $this->add_control('wrapper_tag', [
                'label' => __('Wrapper HTML tag', 'istodata-utilities'),
                'type' => Controls_Manager::SELECT,
                'default' => 'div',
                'options' => [
                    'div' => 'div',
                    'p'   => 'p',
                    'span'=> 'span',
                    'h1'  => 'h1',
                    'h2'  => 'h2',
                    'h3'  => 'h3',
                    'h4'  => 'h4',
                    'h5'  => 'h5',
                    'h6'  => 'h6',
                ],
            ]);

            $wp_rocket_active = (defined('WP_ROCKET_VERSION') || (function_exists('is_plugin_active') && is_plugin_active('wp-rocket/wp-rocket.php')));
            if ($wp_rocket_active) {
                $this->add_control('above_fold', [
                    'label' => __('Εξαίρεση από το Delay JS', 'istodata-utilities'),
                    'description' => __('Εξαιρεί το typed.js από το WP Rocket Delay JS για αυτήν την ενότητα (χρήσιμο για above-the-fold περιεχόμενο).', 'istodata-utilities'),
                    'type' => Controls_Manager::SWITCHER,
                    'label_on' => __('Ναι', 'istodata-utilities'),
                    'label_off' => __('Όχι', 'istodata-utilities'),
                    'return_value' => 'yes',
                    'default' => '',
                ]);
            }

            $this->end_controls_section();

            // Behavior
            $this->start_controls_section('section_behavior', [
                'label' => __('Συμπεριφορά', 'istodata-utilities'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]);

            $this->add_control('type_speed', [
                'label' => __('Ταχύτητα Πληκτρολόγησης', 'istodata-utilities'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 200,
                'step' => 1,
                'default' => 30,
            ]);
            $this->add_control('back_speed', [
                'label' => __('Ταχύτητα Διαγραφής', 'istodata-utilities'),
                'type' => Controls_Manager::NUMBER,
                'min' => 0,
                'max' => 200,
                'step' => 1,
                'default' => 5,
            ]);
            $this->add_control('back_delay', [
                'label' => __('Καθυστέρηση Διαγραφής (ms)', 'istodata-utilities'),
                'type' => Controls_Manager::NUMBER,
                'min' => 0,
                'max' => 5000,
                'step' => 50,
                'default' => 1000,
            ]);
            $this->add_control('loop', [
                'label' => __('Loop', 'istodata-utilities'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Ναι', 'istodata-utilities'),
                'label_off' => __('Όχι', 'istodata-utilities'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]);
            $this->add_control('show_cursor', [
                'label' => __('Εμφάνιση Δρομέα', 'istodata-utilities'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Ναι', 'istodata-utilities'),
                'label_off' => __('Όχι', 'istodata-utilities'),
                'return_value' => 'yes',
                'default' => '',
            ]);
            $this->add_control('observer_threshold', [
                'label' => __('Όριο Ενεργοποίησης', 'istodata-utilities'),
                'description' => __('Πόσο μέρος του κειμένου πρέπει να είναι ορατό στην οθόνη για να ξεκινήσει η πληκτρολόγηση. 0 = ξεκινά όταν φανεί ελάχιστα, 1 = ξεκινά μόνο όταν είναι πλήρως ορατό.', 'istodata-utilities'),
                'type' => Controls_Manager::NUMBER,
                'min' => 0,
                'max' => 1,
                'step' => 0.05,
                'default' => 1.0,
            ]);

            $this->end_controls_section();

            // Style — Typed Text
            $this->start_controls_section('section_style_text', [
                'label' => __('Στυλ Εναλλασσόμενου Κειμένου', 'istodata-utilities'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]);

            $this->add_control('text_color', [
                'label' => __('Χρώμα Κειμένου', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .iu-typed-wrapper' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .iu-typed-wrapper a' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .iu-typed-wrapper span' => 'color: {{VALUE}};',
                ],
            ]);

            $this->add_control('text_hover_color', [
                'label' => __('Χρώμα Κειμένου (hover)', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    // Apply hover color only to the live typed content (and its links), not to static before/after
                    '{{WRAPPER}} .iu-typed-wrapper:hover .iu-typed__live' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .iu-typed-wrapper:hover .iu-typed__live a' => 'color: {{VALUE}};',
                ],
            ]);

            $this->add_group_control(Group_Control_Typography::get_type(), [
                'name' => 'text_typography',
                'selector' => '{{WRAPPER}} .iu-typed-wrapper',
            ]);

            $this->add_group_control(Group_Control_Text_Shadow::get_type(), [
                'name' => 'text_shadow',
                'selector' => '{{WRAPPER}} .iu-typed-wrapper, {{WRAPPER}} .iu-typed-wrapper a',
            ]);

            $this->add_responsive_control('text_align', [
                'label' => __('Στοίχιση', 'istodata-utilities'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [ 'title' => __('Αριστερά', 'istodata-utilities'), 'icon' => 'eicon-text-align-left' ],
                    'center' => [ 'title' => __('Κέντρο', 'istodata-utilities'), 'icon' => 'eicon-text-align-center' ],
                    'right' => [ 'title' => __('Δεξιά', 'istodata-utilities'), 'icon' => 'eicon-text-align-right' ],
                ],
                'default' => 'left',
                'selectors' => [
                    '{{WRAPPER}} .iu-typed-wrapper' => 'text-align: {{VALUE}};',
                ],
            ]);

            $this->end_controls_section();

            // Style — Static Before/After Text
            $this->start_controls_section('section_style_static', [
                'label' => __('Στυλ Σταθερών Κειμένων', 'istodata-utilities'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]);

            // Colors for before/after static texts
            $this->add_control('before_text_color', [
                'label' => __('Χρώμα (Πριν)', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .iu-typed-wrapper .iu-typed__before' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .iu-typed-wrapper .iu-typed__before a' => 'color: {{VALUE}};',
                ],
            ]);

            $this->add_group_control(Group_Control_Typography::get_type(), [
                'name' => 'before_text_typography',
                'label' => __('Typography (Πριν)', 'istodata-utilities'),
                'selector' => '{{WRAPPER}} .iu-typed-wrapper .iu-typed__before',
            ]);

            $this->add_control('after_text_color', [
                'label' => __('Χρώμα (Μετά)', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .iu-typed-wrapper .iu-typed__after' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .iu-typed-wrapper .iu-typed__after a' => 'color: {{VALUE}};',
                ],
            ]);

            $this->add_group_control(Group_Control_Typography::get_type(), [
                'name' => 'after_text_typography',
                'label' => __('Typography (Μετά)', 'istodata-utilities'),
                'selector' => '{{WRAPPER}} .iu-typed-wrapper .iu-typed__after',
            ]);

            $this->end_controls_section();

            // Style — Icons
            $this->start_controls_section('section_style_icons', [
                'label' => __('Στυλ Εικονιδίου', 'istodata-utilities'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]);

            $this->add_control('icon_color', [
                'label' => __('Χρώμα', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .iu-typed-wrapper .iu-typed__icon' => 'color: {{VALUE}};',
                ],
            ]);

            $this->add_control('icon_hover_color', [
                'label' => __('Χρώμα hover', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .iu-typed-wrapper:hover .iu-typed__icon' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .iu-typed-wrapper a:hover .iu-typed__icon' => 'color: {{VALUE}};',
                ],
            ]);

            $this->add_responsive_control('icon_size', [
                'label' => __('Μέγεθος (px)', 'istodata-utilities'),
                'type' => Controls_Manager::SLIDER,
                'range' => [ 'px' => [ 'min' => 8, 'max' => 128 ] ],
                'default' => [ 'size' => 16, 'unit' => 'px' ],
                'selectors' => [
                    '{{WRAPPER}} .iu-typed-wrapper .iu-typed__icon' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .iu-typed-wrapper .iu-typed__icon svg' => 'width: 100%; height: 100%;',
                ],
            ]);

            $this->add_responsive_control('icon_gap', [
                'label' => __('Απόσταση από το κείμενο (px)', 'istodata-utilities'),
                'type' => Controls_Manager::SLIDER,
                'range' => [ 'px' => [ 'min' => 0, 'max' => 32 ] ],
                'default' => [ 'size' => 4, 'unit' => 'px' ],
                'selectors' => [
                    '{{WRAPPER}} .iu-typed-wrapper .iu-typed__icon' => 'margin-left: {{SIZE}}{{UNIT}};',
                ],
            ]);

            $this->add_responsive_control('icon_offset_y', [
                'label' => __('Κάθετη μετατόπιση (px)', 'istodata-utilities'),
                'type' => Controls_Manager::SLIDER,
                'range' => [ 'px' => [ 'min' => -50, 'max' => 50, 'step' => 1 ] ],
                'default' => [ 'size' => 0, 'unit' => 'px' ],
                'selectors' => [
                    '{{WRAPPER}} .iu-typed-wrapper .iu-typed__icon' => 'top: {{SIZE}}{{UNIT}}; position: relative; --iu-typed-icon-ty: {{SIZE}}{{UNIT}}; --iu-typed-icon-va: {{SIZE}}{{UNIT}};',
                ],
            ]);

            $this->add_control('icon_rotate', [
                'label' => __('Περιστροφή (°)', 'istodata-utilities'),
                'type' => Controls_Manager::SLIDER,
                'range' => [ 'px' => [ 'min' => 0, 'max' => 360, 'step' => 1 ] ],
                'default' => [ 'size' => 0 ],
                'selectors' => [
                    '{{WRAPPER}} .iu-typed-wrapper .iu-typed__icon' => '--iu-typed-icon-rot: {{SIZE}}deg;',
                ],
            ]);

            // Hover animation (icon)
            $this->add_control('icon_hover_enable', [
                'label' => __('Κίνηση εικονιδίου στο hover', 'istodata-utilities'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Ναι', 'istodata-utilities'),
                'label_off' => __('Όχι', 'istodata-utilities'),
                'return_value' => 'yes',
                'default' => '',
            ]);

            $this->add_responsive_control('icon_hover_tx', [
                'label' => __('Μετατόπιση X στο hover (px)', 'istodata-utilities'),
                'type' => Controls_Manager::SLIDER,
                'range' => [ 'px' => [ 'min' => -50, 'max' => 50, 'step' => 1 ] ],
                'default' => [ 'size' => 3, 'unit' => 'px' ],
                'condition' => [ 'icon_hover_enable' => 'yes' ],
                'selectors' => [
                    '{{WRAPPER}} .iu-typed-wrapper' => '--iu-typed-icon-hover-tx: {{SIZE}}{{UNIT}};',
                ],
            ]);

            $this->add_responsive_control('icon_hover_ty', [
                'label' => __('Μετατόπιση Y στο hover (px)', 'istodata-utilities'),
                'type' => Controls_Manager::SLIDER,
                'range' => [ 'px' => [ 'min' => -50, 'max' => 50, 'step' => 1 ] ],
                'default' => [ 'size' => -3, 'unit' => 'px' ],
                'condition' => [ 'icon_hover_enable' => 'yes' ],
                'selectors' => [
                    '{{WRAPPER}} .iu-typed-wrapper' => '--iu-typed-icon-hover-ty: {{SIZE}}{{UNIT}};',
                ],
            ]);

            $this->add_control('icon_hover_duration', [
                'label' => __('Διάρκεια κίνησης (ms)', 'istodata-utilities'),
                'type' => Controls_Manager::SLIDER,
                'range' => [ 'px' => [ 'min' => 0, 'max' => 2000, 'step' => 10 ] ],
                'default' => [ 'size' => 250 ],
                'condition' => [ 'icon_hover_enable' => 'yes' ],
                'selectors' => [
                    '{{WRAPPER}} .iu-typed-wrapper' => '--iu-typed-icon-tr-dur: {{SIZE}}ms;',
                ],
            ]);

            $this->end_controls_section();
        }

        protected function render() {
            $s = $this->get_settings_for_display();

            // Enqueue Typed.js
            wp_enqueue_script('iu-typed-js');

            // If above the fold and WP Rocket is active, exclude from Delay JS
            $above_fold = !empty($s['above_fold']) && $s['above_fold'] === 'yes';
            if ($above_fold && (defined('WP_ROCKET_VERSION') || (function_exists('is_plugin_active') && is_plugin_active('wp-rocket/wp-rocket.php')))) {
                // Exclude both the library file and the inline init from Delay JS
                add_filter('rocket_delay_js_exclusions', function($patterns){
                    // Library URL patterns
                    $patterns[] = 'typed.js';
                    $patterns[] = 'istodata-utilities/assets/js/typed.js';
                    // Inline init marker token present in our inline script below
                    $patterns[] = 'IU_TYPED_INLINE_INIT';
                    return $patterns;
                });
            }

            // Unique IDs per instance
            $instance_id = $this->get_id();
            $typed_id = 'iu-typed-' . esc_attr($instance_id);
            $strings_id = $typed_id . '-strings';

            // Wrapper classes (base)
            $wrapper_classes = 'typed-wrapper iu-typed-wrapper';

            // Wrapper tag
            $allowed_wrapper_tags = [ 'div','p','span','h1','h2','h3','h4','h5','h6' ];
            $wrapper_tag = 'div';
            if (!empty($s['wrapper_tag']) && in_array(strtolower($s['wrapper_tag']), $allowed_wrapper_tags, true)) {
                $wrapper_tag = strtolower($s['wrapper_tag']);
            }

            // Build strings markup
            echo '<div id="' . esc_attr($strings_id) . '" class="iu-typed-strings" style="display:none">';
            if (!empty($s['items']) && is_array($s['items'])) {
                foreach ($s['items'] as $item) {
                    $text = isset($item['text']) ? $item['text'] : '';
                    $cls  = isset($item['item_class']) ? $item['item_class'] : '';
                    $link = isset($item['link']) && is_array($item['link']) ? $item['link'] : array();
                    $href = !empty($link['url']) ? $link['url'] : '';
                    $target = (!empty($href) && !empty($link['is_external'])) ? ' target="_blank" rel="noopener"' : '';
                    echo '<div>';
                    $tag_name = !empty($href) ? 'a' : 'span';
                    $attrs = '';
                    if ($tag_name === 'a') {
                        $attrs .= ' href="' . esc_url($href) . '"' . $target;
                    }
                    echo '<' . $tag_name . ' class="' . esc_attr($cls) . '"' . $attrs . '>' . esc_html($text) . ' ';
                    if (!empty($item['icon'])) {
                        ob_start();
                        Icons_Manager::render_icon($item['icon'], [ 'aria-hidden' => 'true' ]);
                        $icon_html = ob_get_clean();
                        $icon_html = $this->iu_svg_normalize($icon_html);
                        if (is_string($icon_html) && $icon_html !== '') {
                            echo '<span class="iu-typed__icon" aria-hidden="true">' . $icon_html . '</span>';
                        }
                    }
                    echo '</' . $tag_name . '>';
                    echo '</div>';
                }
            }
            echo '</div>';

            // Visible wrapper (target element) — render a fallback first item (text + optional link/icon) so there's content before JS init (helps editor too)
            $before_text = isset($s['before_text']) ? (string)$s['before_text'] : '';
            $after_text  = isset($s['after_text']) ? (string)$s['after_text'] : '';
            // Inline color for immediate, pre-CSS rendering (avoids black icon flash)
            $inline_color = '';
            if (!empty($s['text_color'])) {
                $color = trim((string)$s['text_color']);
                if ($color !== '') { $inline_color = $color; }
            }
            // Fallback: if no text color provided, inherit from icon color for initial paint
            if ($inline_color === '' && !empty($s['icon_color'])) {
                $icon_color = trim((string)$s['icon_color']);
                if ($icon_color !== '') { $inline_color = $icon_color; }
            }
            $fallback_html = '';
            $fallback_icon_id = 'iu-typed-fallback-icon-' . $instance_id;
            $fallback_icon_present = false;
            if (!empty($s['items']) && is_array($s['items'])) {
                $first = $s['items'][0];
                $text  = isset($first['text']) ? $first['text'] : '';
                $cls   = isset($first['item_class']) ? $first['item_class'] : '';
                $link  = isset($first['link']) && is_array($first['link']) ? $first['link'] : array();
                $href  = !empty($link['url']) ? $link['url'] : '';
                $target= (!empty($href) && !empty($link['is_external'])) ? ' target="_blank" rel="noopener"' : '';
                $tag   = !empty($href) ? 'a' : 'span';

                $attrs = '';
                if ($tag === 'a') {
                    $attrs .= ' href="' . esc_url($href) . '"' . $target;
                }

                // Build inner HTML: text + optional icon
                $inner = esc_html($text);
                if (!empty($first['icon'])) {
                    ob_start();
                    Icons_Manager::render_icon($first['icon'], [ 'aria-hidden' => 'true' ]);
                    $icon_html = ob_get_clean();
                    $icon_html = $this->iu_svg_normalize($icon_html);
                    if (is_string($icon_html) && $icon_html !== '') {
                        $fallback_icon_present = true;
                        // Inline minimal sizing to avoid huge first paint: pick the smallest configured size (mobile → tablet → desktop)
                        $sizes = array();
                        if (!empty($s['icon_size_mobile']) && is_array($s['icon_size_mobile']) && isset($s['icon_size_mobile']['size']) && is_numeric($s['icon_size_mobile']['size'])) {
                            $sizes[] = (int)$s['icon_size_mobile']['size'];
                        }
                        if (!empty($s['icon_size_tablet']) && is_array($s['icon_size_tablet']) && isset($s['icon_size_tablet']['size']) && is_numeric($s['icon_size_tablet']['size'])) {
                            $sizes[] = (int)$s['icon_size_tablet']['size'];
                        }
                        if (!empty($s['icon_size']) && is_array($s['icon_size']) && isset($s['icon_size']['size']) && is_numeric($s['icon_size']['size'])) {
                            $sizes[] = (int)$s['icon_size']['size'];
                        }
                        $initial_size = !empty($sizes) ? min($sizes) : 16;
                        $inline_icon_style = 'width:' . (int)$initial_size . 'px; height:' . (int)$initial_size . 'px; display:inline-block;';
                        if (!empty($s['icon_color'])) {
                            $icon_color = trim((string)$s['icon_color']);
                            if ($icon_color !== '') {
                                $inline_icon_style .= ' color:' . $icon_color . ';';
                            }
                        }
                        $inner .= ' <span id="' . esc_attr($fallback_icon_id) . '" class="iu-typed__icon" style="' . esc_attr($inline_icon_style) . '" aria-hidden="true">' . $icon_html . '</span>';
                    }
                }
                $fallback_html = '<' . $tag . ' class="' . esc_attr($cls) . '"' . $attrs . '>' . $inner . '</' . $tag . '>';
            }
            $wrapper_style_attr = $inline_color !== '' ? ' style="color: ' . esc_attr($inline_color) . ';"' : '';
            // Early inline CSS for responsive fallback icon sizing (applies at first paint)
            if ($fallback_icon_present) {
                $base = 16; $tab = null; $mob = null;
                if (!empty($s['icon_size']) && is_array($s['icon_size']) && isset($s['icon_size']['size']) && is_numeric($s['icon_size']['size'])) {
                    $base = (int)$s['icon_size']['size'];
                }
                if (!empty($s['icon_size_tablet']) && is_array($s['icon_size_tablet']) && isset($s['icon_size_tablet']['size']) && is_numeric($s['icon_size_tablet']['size'])) {
                    $tab = (int)$s['icon_size_tablet']['size'];
                }
                if (!empty($s['icon_size_mobile']) && is_array($s['icon_size_mobile']) && isset($s['icon_size_mobile']['size']) && is_numeric($s['icon_size_mobile']['size'])) {
                    $mob = (int)$s['icon_size_mobile']['size'];
                }
                echo '<style>';
                echo '#' . esc_attr($fallback_icon_id) . '{width:' . (int)$base . 'px;height:' . (int)$base . 'px;}';
                if ($mob !== null) {
                    echo '@media (max-width: 767px){#' . esc_attr($fallback_icon_id) . '{width:' . (int)$mob . 'px;height:' . (int)$mob . 'px;}}';
                }
                if ($tab !== null) {
                    echo '@media (min-width: 768px) and (max-width: 1024px){#' . esc_attr($fallback_icon_id) . '{width:' . (int)$tab . 'px;height:' . (int)$tab . 'px;}}';
                }
                echo '</style>';
            }
            echo '<' . $wrapper_tag . ' class="' . esc_attr($wrapper_classes) . '"' . $wrapper_style_attr . '>';
            if ($before_text !== '') {
                echo '<span class="iu-typed__before">' . wp_kses_post($before_text) . '</span>';
            }
            echo '<span id="' . esc_attr($typed_id) . '" class="iu-typed__live">' . $fallback_html . '</span>';
            if ($after_text !== '') {
                echo '<span class="iu-typed__after">' . wp_kses_post($after_text) . '</span>';
            }
            echo '</' . $wrapper_tag . '>';

            

            // Inline init script with IntersectionObserver
            $type_speed = isset($s['type_speed']) ? (int)$s['type_speed'] : 30;
            $back_speed = isset($s['back_speed']) ? (int)$s['back_speed'] : 5;
            $back_delay = isset($s['back_delay']) ? (int)$s['back_delay'] : 1000;
            $loop       = !empty($s['loop']) && $s['loop'] === 'yes' ? 'true' : 'false';
            $show_cursor= !empty($s['show_cursor']) && $s['show_cursor'] === 'yes' ? 'true' : 'false';
            $threshold  = isset($s['observer_threshold']) ? floatval($s['observer_threshold']) : 1.0;
            // Detect Elementor editor mode to keep preview stable while editing
            $is_editor  = (class_exists('Elementor\\Plugin') && \Elementor\Plugin::$instance && \Elementor\Plugin::$instance->editor && \Elementor\Plugin::$instance->editor->is_edit_mode());

            ?>
            <script>
            (function(){
                // Marker so WP Rocket Delay JS can exclude this inline script when requested
                try { window.IU_TYPED_INLINE_INIT = true; } catch(e) {}
                var target = document.getElementById('<?php echo esc_js($typed_id); ?>');
                var stringsId = '#<?php echo esc_js($strings_id); ?>';
                var isEditor = <?php echo $is_editor ? 'true' : 'false'; ?>;
                if (!target) return;

                function initTyped(){
                    if (typeof window.Typed !== 'function') { return; }
                    if (target.__iuTypedInit) return; // guard
                    target.__iuTypedInit = true;
                    // Clear fallback content to avoid being unshifted as an extra first string by Typed.js
                    try { target.innerHTML = ''; } catch(e) { /* no-op */ }
                    // In editor, allow a single pass through all items (no loop). We keep all strings for a more faithful preview.
                    // Any errors here should not break the page.
                    try { /* no-op on purpose */ } catch(e) { /* no-op */ }
                    try {
                        new window.Typed('#<?php echo esc_js($typed_id); ?>', {
                            stringsElement: stringsId,
                            typeSpeed: <?php echo (int)$type_speed; ?>,
                            backSpeed: <?php echo (int)$back_speed; ?>,
                            backDelay: <?php echo (int)$back_delay; ?>,
                            smartBackspace: false,
                            loop: (isEditor ? false : <?php echo $loop; ?>),
                            showCursor: <?php echo $show_cursor; ?>,
                            contentType: 'html'
                        });
                    } catch(e) { /* no-op */ }
                }
                function waitForTypedAndInit(maxTries){
                    maxTries = (typeof maxTries === 'number') ? maxTries : 60; // ~3s at 50ms
                    if (typeof window.Typed === 'function') { initTyped(); return; }
                    if (maxTries <= 0) return;
                    setTimeout(function(){ waitForTypedAndInit(maxTries - 1); }, 50);
                }

                // In Elementor editor: initialize immediately on DOM ready (skip IO)
                if (isEditor) {
                    if (document.readyState === 'complete' || document.readyState === 'interactive') {
                        setTimeout(function(){ waitForTypedAndInit(); }, 10);
                    } else {
                        document.addEventListener('DOMContentLoaded', function(){ setTimeout(function(){ waitForTypedAndInit(); }, 10); });
                    }
                    return;
                }

                if ('IntersectionObserver' in window) {
                    var obs = new IntersectionObserver(function(entries, o){
                        entries.forEach(function(entry){
                            if (entry.isIntersecting) {
                                setTimeout(function(){ waitForTypedAndInit(); }, 10);
                                o.unobserve(entry.target);
                            }
                        });
                    }, { root: null, threshold: <?php echo json_encode(max(0, min(1, $threshold))); ?> });
                    obs.observe(target);
                } else {
                    // Fallback: init on DOMContentLoaded
                    if (document.readyState === 'complete' || document.readyState === 'interactive') {
                        setTimeout(function(){ waitForTypedAndInit(); }, 10);
                    } else {
                        document.addEventListener('DOMContentLoaded', function(){ setTimeout(function(){ waitForTypedAndInit(); }, 10); });
                    }
                }
            })();
            </script>
            <?php
        }

        // Normalize SVG HTML to allow CSS sizing (drop width/height; keep viewBox)
        private function iu_svg_normalize($svg_html) {
            if (!is_string($svg_html) || stripos($svg_html, '<svg') === false) return $svg_html;
            // Normalize root <svg>: drop width/height, ensure inline style has width/height 100%
            $svg_html = preg_replace_callback('/<svg\b([^>]*)>/i', function($m){
                $attrs = $m[1];
                $attrs = preg_replace('/\swidth\s*=\s*"[^"]*"/i', '', $attrs);
                $attrs = preg_replace('/\sheight\s*=\s*"[^"]*"/i', '', $attrs);
                // Ensure inline style exists and includes width/height 100% for stable early sizing
                $has_style = preg_match('/\sstyle\s*=\s*"([^"]*)"/i', $attrs, $smatch);
                if ($has_style) {
                    $style = $smatch[1];
                    $style = preg_replace('/(?:^|;|\s)(width|height)\s*:\s*[^;]+;?/i', ';', $style);
                    $style = preg_replace('/;{2,}/', ';', trim($style));
                    $style = trim($style, '; ');
                    $style = trim($style . '; width:100%; height:100%;', '; ');
                    $attrs = preg_replace('/\sstyle\s*=\s*"[^"]*"/i', ' style="' . esc_attr($style) . '"', $attrs, 1);
                } else {
                    $attrs .= ' style="width:100%; height:100%;"';
                }
                // Prefer currentColor for paint at initial render
                if (preg_match('/\sfill\s*=\s*"([^"]*)"/i', $attrs)) {
                    $attrs = preg_replace('/\sfill\s*=\s*"(?!none)[^"]*"/i', ' fill="currentColor"', $attrs);
                } else {
                    $attrs .= ' fill="currentColor"';
                }
                return '<svg' . $attrs . '>';
            }, $svg_html, 1);

            // For inner elements, rewrite fill/stroke (except "none") to currentColor to avoid black flash
            $svg_html = preg_replace('/\sfill\s*=\s*"(?!none)[^"]*"/i', ' fill="currentColor"', $svg_html);
            $svg_html = preg_replace('/\sstroke\s*=\s*"(?!none)[^"]*"/i', ' stroke="currentColor"', $svg_html);
            return $svg_html;
        }
    }
}
