<?php
// Safety check
if (!defined('ABSPATH')) { exit; }

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use Elementor\Group_Control_Typography;

if (!class_exists('IU_Heading_Group_Widget')) {
    class IU_Heading_Group_Widget extends Widget_Base {
        public function get_name() {
            return 'iu_heading_group';
        }

        public function get_title() {
            return __('Heading Group', 'istodata-utilities');
        }

        public function get_icon() {
            return 'eicon-heading';
        }

        public function get_categories() {
            return [ 'istodata-kit' ];
        }

        public function get_style_depends() {
            if (!wp_style_is('iu-heading-group', 'registered')) {
                wp_register_style(
                    'iu-heading-group',
                    IU_PLUGIN_URL . 'assets/css/heading-group.css',
                    array(),
                    defined('IU_PLUGIN_VERSION') ? IU_PLUGIN_VERSION : null
                );
            }
            return [ 'iu-heading-group' ];
        }

        public function get_script_depends() {
            // Load JS only when Stagger is enabled (handled in render)
            return [];
        }

        protected function register_controls() {
            // Content controls
            $this->start_controls_section('section_content', [
                'label' => __('Περιεχόμενο', 'istodata-utilities'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]);

            $this->add_control('show_overline', [
                'label' => __('Εμφάνιση Overline', 'istodata-utilities'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Ναι', 'istodata-utilities'),
                'label_off' => __('Όχι', 'istodata-utilities'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]);

            $this->add_control('overline', [
                'label' => __('Overline', 'istodata-utilities'),
                'type' => Controls_Manager::TEXT,
                'dynamic' => [ 'active' => true ],
                'description' => __('Δέχεται ασφαλές inline HTML, π.χ. span, strong, em, br.', 'istodata-utilities'),
                'condition' => ['show_overline' => 'yes'],
            ]);

            $this->add_control('heading_text', [
                'label' => __('Κεφαλίδα', 'istodata-utilities'),
                'type' => Controls_Manager::TEXTAREA,
                'dynamic' => [ 'active' => true ],
                'placeholder' => __('Π.χ. Τίτλος Ενότητας', 'istodata-utilities'),
                'rows' => 2,
                'default' => __('Τίτλος', 'istodata-utilities'),
            ]);

            $this->add_control('heading_tag', [
                'label' => __('Επίπεδο Κεφαλίδας', 'istodata-utilities'),
                'type' => Controls_Manager::SELECT,
                'default' => 'h2',
                'options' => [
                    'h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'h5' => 'H5', 'h6' => 'H6',
                    'span' => 'SPAN', 'div' => 'DIV',
                ],
            ]);

            $this->add_control('text', [
                'label' => __('Κείμενο', 'istodata-utilities'),
                'type' => Controls_Manager::WYSIWYG,
                'dynamic' => [ 'active' => true ],
            ]);

            $this->end_controls_section();

            // Style: Layout
            $this->start_controls_section('section_style_layout', [
                'label' => __('Layout', 'istodata-utilities'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]);

            $this->add_responsive_control('alignment', [
                'label' => __('Στοίχιση', 'istodata-utilities'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [ 'title' => __('Αριστερά', 'istodata-utilities'), 'icon' => 'eicon-text-align-left' ],
                    'center' => [ 'title' => __('Κέντρο', 'istodata-utilities'), 'icon' => 'eicon-text-align-center' ],
                    'right' => [ 'title' => __('Δεξιά', 'istodata-utilities'), 'icon' => 'eicon-text-align-right' ],
                ],
                'default' => 'left',
                'selectors_dictionary' => [
                    'left' => 'left; --iu-hg-heading-margin-left: 0; --iu-hg-heading-margin-right: auto; --iu-hg-text-margin-left: 0; --iu-hg-text-margin-right: auto',
                    'center' => 'center; --iu-hg-heading-margin-left: auto; --iu-hg-heading-margin-right: auto; --iu-hg-text-margin-left: auto; --iu-hg-text-margin-right: auto',
                    'right' => 'right; --iu-hg-heading-margin-left: auto; --iu-hg-heading-margin-right: 0; --iu-hg-text-margin-left: auto; --iu-hg-text-margin-right: 0',
                ],
                'selectors' => [
                    '{{WRAPPER}} .iu-heading-group' => 'text-align: {{VALUE}};'
                ],
            ]);

            $this->add_responsive_control('gap_overline_heading', [
                'label' => __('Απόσταση Overline → Κεφαλίδα (px)', 'istodata-utilities'),
                'type' => Controls_Manager::SLIDER,
                'range' => [ 'px' => [ 'min' => 0, 'max' => 48 ] ],
                'default' => [ 'size' => 8 ],
                'selectors' => [
                    '{{WRAPPER}} .iu-heading-group' => '--iu-hg-gap-oh: {{SIZE}}{{UNIT}};'
                ],
                'condition' => ['show_overline' => 'yes'],
            ]);

            $this->add_responsive_control('gap_heading_text', [
                'label' => __('Απόσταση Κεφαλίδα → Κείμενο (px)', 'istodata-utilities'),
                'type' => Controls_Manager::SLIDER,
                'range' => [ 'px' => [ 'min' => 0, 'max' => 48 ] ],
                'default' => [ 'size' => 8 ],
                'selectors' => [
                    '{{WRAPPER}} .iu-heading-group' => '--iu-hg-gap-ht: {{SIZE}}{{UNIT}};'
                ],
            ]);

            $this->end_controls_section();

            // Style: Overline
            $this->start_controls_section('section_style_overline', [
                'label' => __('Στυλ Overline', 'istodata-utilities'),
                'tab'   => Controls_Manager::TAB_STYLE,
                'condition' => ['show_overline' => 'yes'],
            ]);

            $this->add_control('overline_color', [
                'label' => __('Χρώμα', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .iu-heading-group .iu-overline' => 'color: {{VALUE}};'
                ],
            ]);

            $this->add_group_control(Group_Control_Typography::get_type(), [
                'name' => 'overline_typo',
                'selector' => '{{WRAPPER}} .iu-heading-group .iu-overline',
            ]);

            // Overline: Text Shadow (Elementor popover style)
            $this->add_group_control( \Elementor\Group_Control_Text_Shadow::get_type(), [
                'name' => 'overline_text_shadow',
                'selector' => '{{WRAPPER}} .iu-heading-group .iu-overline',
            ]);

            $this->end_controls_section();

            // Style: Heading
            $this->start_controls_section('section_style_heading', [
                'label' => __('Στυλ Κεφαλίδας', 'istodata-utilities'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]);

            $this->add_control('heading_color', [
                'label' => __('Χρώμα', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .iu-heading-group .iu-heading' => 'color: {{VALUE}};'
                ],
            ]);

            $this->add_group_control(Group_Control_Typography::get_type(), [
                'name' => 'heading_typo',
                'selector' => '{{WRAPPER}} .iu-heading-group .iu-heading',
            ]);

            $this->add_responsive_control('heading_width', [
                'label' => __('Heading Width', 'istodata-utilities'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => [ 'px', '%' ],
                'range' => [
                    'px' => [ 'min' => 80, 'max' => 1600, 'step' => 1 ],
                    '%' => [ 'min' => 1, 'max' => 100, 'step' => 1 ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .iu-heading-group .iu-heading' => 'width: {{SIZE}}{{UNIT}}; max-width: 100%;',
                ],
            ]);

            // Heading: Text Shadow (Elementor popover style)
            $this->add_group_control( \Elementor\Group_Control_Text_Shadow::get_type(), [
                'name' => 'heading_text_shadow',
                'selector' => '{{WRAPPER}} .iu-heading-group .iu-heading',
            ]);

            $this->end_controls_section();

            // Style: Text
            $this->start_controls_section('section_style_text', [
                'label' => __('Στυλ Κειμένου', 'istodata-utilities'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]);

            $this->add_control('text_color', [
                'label' => __('Χρώμα', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .iu-heading-group .iu-text' => 'color: {{VALUE}};'
                ],
            ]);

            $this->add_group_control(Group_Control_Typography::get_type(), [
                'name' => 'text_typo',
                'selector' => '{{WRAPPER}} .iu-heading-group .iu-text',
            ]);

            $this->add_responsive_control('text_width', [
                'label' => __('Text Width', 'istodata-utilities'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => [ 'px', '%' ],
                'range' => [
                    'px' => [ 'min' => 80, 'max' => 1600, 'step' => 1 ],
                    '%' => [ 'min' => 1, 'max' => 100, 'step' => 1 ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .iu-heading-group .iu-text' => 'width: {{SIZE}}{{UNIT}}; max-width: 100%;',
                ],
            ]);

            // Text: Text Shadow (Elementor popover style)
            $this->add_group_control( \Elementor\Group_Control_Text_Shadow::get_type(), [
                'name' => 'body_text_shadow',
                'selector' => '{{WRAPPER}} .iu-heading-group .iu-text',
            ]);

            $this->end_controls_section();

            // Animations (Stagger)
            $this->start_controls_section('section_anim_stagger', [
                'label' => __('Animations (Stagger)', 'istodata-utilities'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]);

            $this->add_control('stagger_enable', [
                'label' => __('Ενεργοποίηση Stagger', 'istodata-utilities'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Ναι', 'istodata-utilities'),
                'label_off' => __('Όχι', 'istodata-utilities'),
                'return_value' => 'yes',
                'default' => '',
            ]);

            $this->add_control('anim_direction', [
                'label' => __('Entrance Animation', 'istodata-utilities'),
                'type' => Controls_Manager::SELECT,
                'default' => 'up',
                'options' => [
                    'fade' => __('Fade In', 'istodata-utilities'),
                    'up'   => __('Fade In Up', 'istodata-utilities'),
                    'down' => __('Fade In Down', 'istodata-utilities'),
                    'left' => __('Fade In Left', 'istodata-utilities'),
                    'right'=> __('Fade In Right', 'istodata-utilities'),
                ],
                'condition' => [ 'stagger_enable' => 'yes' ],
            ]);

            $this->add_responsive_control('anim_distance', [
                'label' => __('Απόσταση (px)', 'istodata-utilities'),
                'type' => Controls_Manager::SLIDER,
                'range' => [ 'px' => [ 'min' => 0, 'max' => 100, 'step' => 1 ] ],
                'default' => [ 'size' => 8 ],
                'selectors' => [
                    '{{WRAPPER}} .iu-heading-group' => '--iu-hg-anim-dist: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [ 'stagger_enable' => 'yes' ],
            ]);

            $this->add_control('anim_duration', [
                'label' => __('Διάρκεια (ms)', 'istodata-utilities'),
                'type' => Controls_Manager::SLIDER,
                'range' => [ 'px' => [ 'min' => 0, 'max' => 3000, 'step' => 25 ] ],
                'default' => [ 'size' => 1400 ],
                'selectors' => [
                    '{{WRAPPER}} .iu-heading-group' => '--iu-hg-anim-dur: {{SIZE}}ms;',
                ],
                'condition' => [ 'stagger_enable' => 'yes' ],
            ]);

            $this->add_control('anim_delay_overline', [
                'label' => __('Καθυστέρηση Overline (ms)', 'istodata-utilities'),
                'type' => Controls_Manager::SLIDER,
                'range' => [ 'px' => [ 'min' => 0, 'max' => 2000, 'step' => 25 ] ],
                'default' => [ 'size' => 100 ],
                'selectors' => [
                    '{{WRAPPER}} .iu-heading-group' => '--iu-hg-anim-delay-overline: {{SIZE}}ms;',
                ],
                'condition' => [ 'stagger_enable' => 'yes' ],
            ]);

            $this->add_control('anim_delay_heading', [
                'label' => __('Καθυστέρηση Κεφαλίδας (ms)', 'istodata-utilities'),
                'type' => Controls_Manager::SLIDER,
                'range' => [ 'px' => [ 'min' => 0, 'max' => 2000, 'step' => 25 ] ],
                'default' => [ 'size' => 300 ],
                'selectors' => [
                    '{{WRAPPER}} .iu-heading-group' => '--iu-hg-anim-delay-heading: {{SIZE}}ms;',
                ],
                'condition' => [ 'stagger_enable' => 'yes' ],
            ]);

            $this->add_control('anim_delay_text', [
                'label' => __('Καθυστέρηση Κειμένου (ms)', 'istodata-utilities'),
                'type' => Controls_Manager::SLIDER,
                'range' => [ 'px' => [ 'min' => 0, 'max' => 2000, 'step' => 25 ] ],
                'default' => [ 'size' => 500 ],
                'selectors' => [
                    '{{WRAPPER}} .iu-heading-group' => '--iu-hg-anim-delay-text: {{SIZE}}ms;',
                ],
                'condition' => [ 'stagger_enable' => 'yes' ],
            ]);

            // Device application toggles (default: off on mobile/tablet)
            $this->add_control('stagger_apply_mobile', [
                'label' => __('Εφαρμογή σε κινητό', 'istodata-utilities'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Ναι', 'istodata-utilities'),
                'label_off' => __('Όχι', 'istodata-utilities'),
                'return_value' => 'yes',
                'default' => '',
                'condition' => [ 'stagger_enable' => 'yes' ],
            ]);

            $this->add_control('stagger_apply_tablet', [
                'label' => __('Εφαρμογή σε tablet', 'istodata-utilities'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Ναι', 'istodata-utilities'),
                'label_off' => __('Όχι', 'istodata-utilities'),
                'return_value' => 'yes',
                'default' => '',
                'condition' => [ 'stagger_enable' => 'yes' ],
            ]);

            $this->end_controls_section();
        }

        protected function render() {
            $s = $this->get_settings_for_display();
            $show_overline = isset($s['show_overline']) && $s['show_overline'] === 'yes';
            $overline = isset($s['overline']) ? $s['overline'] : '';
            $heading_text = isset($s['heading_text']) ? $s['heading_text'] : '';
            $heading_tag = isset($s['heading_tag']) ? $s['heading_tag'] : 'h2';
            $text = isset($s['text']) ? $s['text'] : '';
            $text_has_content = trim(wp_strip_all_tags($text)) !== '';

            $tag = in_array($heading_tag, ['h1','h2','h3','h4','h5','h6','span','div'], true) ? $heading_tag : 'h2';

            $this->add_render_attribute('wrapper', 'class', 'iu-heading-group');
            if (!empty($s['stagger_enable']) && $s['stagger_enable'] === 'yes') {
                $this->add_render_attribute('wrapper', 'class', 'iu-heading-group--stagger');
                $dir = isset($s['anim_direction']) ? $s['anim_direction'] : 'up';
                $dir_class = 'iu-hg-dir-' . preg_replace('/[^a-z0-9_-]/i', '', $dir);
                $this->add_render_attribute('wrapper', 'class', $dir_class);
                // Apply toggles: if not explicitly enabled, disable on that device
                if (empty($s['stagger_apply_mobile']) || $s['stagger_apply_mobile'] !== 'yes') {
                    $this->add_render_attribute('wrapper', 'class', 'iu-hg-off-mobile');
                }
                if (empty($s['stagger_apply_tablet']) || $s['stagger_apply_tablet'] !== 'yes') {
                    $this->add_render_attribute('wrapper', 'class', 'iu-hg-off-tablet');
                }
                if (!wp_script_is('iu-heading-group-js', 'registered')) {
                    wp_register_script(
                        'iu-heading-group-js',
                        IU_PLUGIN_URL . 'assets/js/heading-group.js',
                        array(),
                        defined('IU_PLUGIN_VERSION') ? IU_PLUGIN_VERSION : null,
                        true
                    );
                }
                wp_enqueue_script('iu-heading-group-js');
            }

            echo '<div ' . $this->get_render_attribute_string('wrapper') . '>';
            $allowed_inline_html = [
                'span' => [
                    'class' => true,
                    'aria-label' => true,
                ],
                'strong' => [],
                'em' => [],
                'b' => [],
                'i' => [],
                'u' => [],
                'small' => [],
                'mark' => [],
                'sup' => [],
                'sub' => [],
                'br' => [],
            ];

            if ($show_overline && $overline !== '') {
                echo '<span class="iu-overline">' . wp_kses($overline, $allowed_inline_html) . '</span>';
            }
            if ($heading_text !== '') {
                printf('<%1$s class="iu-heading">%2$s</%1$s>', esc_attr($tag), wp_kses(nl2br($heading_text), $allowed_inline_html));
            }
            if ($text_has_content) {
                echo '<div class="iu-text">' . wp_kses_post(wpautop($text)) . '</div>';
            }
            echo '</div>';
            // Noscript fallback to ensure content is visible without JS
            if (!empty($s['stagger_enable']) && $s['stagger_enable'] === 'yes') {
                echo '<noscript><style>.iu-heading-group--stagger > *{opacity:1 !important;transform:none !important;animation:none !important;}</style></noscript>';
            }
        }
    }
}
