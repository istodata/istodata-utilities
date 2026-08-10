<?php
// Safety check
if (!defined('ABSPATH')) { exit; }

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use Elementor\Icons_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;

if (!class_exists('IU_Social_Share_Widget')) {
    class IU_Social_Share_Widget extends Widget_Base {
        public function get_name() {
            return 'iu_social_share';
        }

        public function get_title() {
            return __('Social Share', 'istodata-utilities');
        }

        public function get_icon() {
            return 'eicon-share';
        }

        public function get_categories() {
            return [ 'istodata-kit' ];
        }

        public function get_style_depends() {
            if (!wp_style_is('iu-social-share', 'registered')) {
                wp_register_style(
                    'iu-social-share',
                    IU_PLUGIN_URL . 'assets/css/social-share.css',
                    array(),
                    defined('IU_PLUGIN_VERSION') ? IU_PLUGIN_VERSION : null
                );
            }
            return [ 'iu-social-share' ];
        }

        public function get_script_depends() {
            if (!wp_script_is('iu-social-share', 'registered')) {
                wp_register_script(
                    'iu-social-share',
                    IU_PLUGIN_URL . 'assets/js/social-share.js',
                    array(),
                    defined('IU_PLUGIN_VERSION') ? IU_PLUGIN_VERSION : null,
                    true
                );
            }

            return [ 'iu-social-share' ];
        }

        protected function register_controls() {
            $this->start_controls_section('section_content', [
                'label' => __('Κουμπιά Κοινοποίησης', 'istodata-utilities'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]);

            // Toggles per network
            $this->add_control('enable_facebook', [
                'label' => __('Facebook', 'istodata-utilities'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Ναι', 'istodata-utilities'),
                'label_off' => __('Όχι', 'istodata-utilities'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]);
            // ICONS control (Elementor-native) for consistent editor preview
            $this->add_control('facebook_icon', [
                'label' => __('Εικονίδιο (Facebook)', 'istodata-utilities'),
                'type' => Controls_Manager::ICONS,
                'default' => [ 'value' => 'fab fa-facebook-f', 'library' => 'fa-brands' ],
                'condition' => ['enable_facebook' => 'yes'],
            ]);

            $this->add_control('enable_linkedin', [
                'label' => __('LinkedIn', 'istodata-utilities'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Ναι', 'istodata-utilities'),
                'label_off' => __('Όχι', 'istodata-utilities'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]);
            $this->add_control('linkedin_icon', [
                'label' => __('Εικονίδιο (LinkedIn)', 'istodata-utilities'),
                'type' => Controls_Manager::ICONS,
                'default' => [ 'value' => 'fab fa-linkedin-in', 'library' => 'fa-brands' ],
                'condition' => ['enable_linkedin' => 'yes'],
            ]);

            $this->add_control('enable_whatsapp', [
                'label' => __('WhatsApp', 'istodata-utilities'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Ναι', 'istodata-utilities'),
                'label_off' => __('Όχι', 'istodata-utilities'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]);
            $this->add_control('whatsapp_icon', [
                'label' => __('Εικονίδιο (WhatsApp)', 'istodata-utilities'),
                'type' => Controls_Manager::ICONS,
                'default' => [ 'value' => 'fab fa-whatsapp', 'library' => 'fa-brands' ],
                'condition' => ['enable_whatsapp' => 'yes'],
            ]);

            $this->add_control('enable_email', [
                'label' => __('Email', 'istodata-utilities'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Ναι', 'istodata-utilities'),
                'label_off' => __('Όχι', 'istodata-utilities'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]);
            $this->add_control('email_icon', [
                'label' => __('Εικονίδιο (Email)', 'istodata-utilities'),
                'type' => Controls_Manager::ICONS,
                'default' => [ 'value' => 'fas fa-envelope', 'library' => 'fa-solid' ],
                'condition' => ['enable_email' => 'yes'],
            ]);

            $this->add_control('share_url', [
                'label' => __('URL προς κοινοποίηση', 'istodata-utilities'),
                'type' => Controls_Manager::URL,
                'placeholder' => __('Αυτόματη χρήση τρέχουσας σελίδας αν μείνει κενό', 'istodata-utilities'),
                'dynamic' => [ 'active' => true ],
            ]);
            $this->add_control('share_title', [
                'label' => __('Τίτλος προς κοινοποίηση', 'istodata-utilities'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => __('Αυτόματη χρήση τίτλου αν μείνει κενό', 'istodata-utilities'),
                'dynamic' => [ 'active' => true ],
            ]);

            $this->add_control('share_label', [
                'label' => __('Ετικέτα', 'istodata-utilities'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => __('Share This', 'istodata-utilities'),
                'dynamic' => [ 'active' => true ],
            ]);

            $this->add_control('label_icon', [
                'label' => __('Εικονίδιο ετικέτας', 'istodata-utilities'),
                'type' => Controls_Manager::ICONS,
                'condition' => [
                    'share_label!' => '',
                ],
            ]);

            $this->add_control('show_network_names', [
                'label' => __('Εμφάνιση ονομάτων δικτύων', 'istodata-utilities'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Ναι', 'istodata-utilities'),
                'label_off' => __('Όχι', 'istodata-utilities'),
                'return_value' => 'yes',
                'default' => '',
            ]);

            // Tooltip controls removed (no tooltips)

            $this->add_control('open_new', [
                'label' => __('Άνοιγμα σε νέο παράθυρο', 'istodata-utilities'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Ναι', 'istodata-utilities'),
                'label_off' => __('Όχι', 'istodata-utilities'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]);
            // rel nofollow is now enforced by default (no control)

            $this->end_controls_section();

            $this->start_controls_section('section_style_layout', [
                'label' => __('Διάταξη', 'istodata-utilities'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]);

            $this->add_control('layout', [
                'label' => __('Διάταξη', 'istodata-utilities'),
                'type' => Controls_Manager::SELECT,
                'options' => [ 'horizontal' => __('Οριζόντια', 'istodata-utilities'), 'vertical' => __('Κάθετη', 'istodata-utilities'), 'popover' => __('Popover', 'istodata-utilities') ],
                'default' => 'horizontal',
                'selectors_dictionary' => [
                    'horizontal' => 'row',
                    'vertical' => 'column',
                    'popover' => 'column',
                ],
                'selectors' => [
                    '{{WRAPPER}} .iu-social-share__icons' => 'flex-direction: {{VALUE}};',
                ],
            ]);

            $this->add_responsive_control('network_gap', [
                'label' => __('Απόσταση δικτύων (px)', 'istodata-utilities'),
                'type' => Controls_Manager::SLIDER,
                'range' => [ 'px' => [ 'min' => 0, 'max' => 64 ] ],
                'selectors' => [
                    '{{WRAPPER}} .iu-social-share__icons' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]);

            $this->add_responsive_control('label_position', [
                'label' => __('Θέση Ετικέτας', 'istodata-utilities'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [ 'title' => __('Αριστερά', 'istodata-utilities'), 'icon' => 'eicon-h-align-left' ],
                    'top' => [ 'title' => __('Πάνω', 'istodata-utilities'), 'icon' => 'eicon-v-align-top' ],
                ],
                'default' => 'left',
                'selectors_dictionary' => [
                    'left' => 'row',
                    'top' => 'column',
                ],
                'selectors' => [
                    '{{WRAPPER}} .iu-social-share__inner' => 'flex-direction: {{VALUE}};',
                ],
                'condition' => [
                    'share_label!' => '',
                ],
            ]);

            $this->add_responsive_control('alignment', [
                'label' => __('Στοίχιση', 'istodata-utilities'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'flex-start' => [ 'title' => __('Αριστερά', 'istodata-utilities'), 'icon' => 'eicon-text-align-left' ],
                    'center'     => [ 'title' => __('Κέντρο', 'istodata-utilities'), 'icon' => 'eicon-text-align-center' ],
                    'flex-end'   => [ 'title' => __('Δεξιά', 'istodata-utilities'), 'icon' => 'eicon-text-align-right' ],
                ],
                'default' => 'flex-start',
                'selectors' => [
                    '{{WRAPPER}} .iu-social-share' => 'justify-content: {{VALUE}};',
                ],
            ]);

            $this->end_controls_section();

            $this->start_controls_section('section_style', [
                'label' => __('Εικονίδια', 'istodata-utilities'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]);

            $this->add_responsive_control('icon_size', [
                'label' => __('Μέγεθος εικονιδίου (px)', 'istodata-utilities'),
                'type' => Controls_Manager::SLIDER,
                'range' => [ 'px' => [ 'min' => 8, 'max' => 128 ] ],
                'default' => [ 'size' => 24, 'unit' => 'px' ],
                'selectors' => [
                    '{{WRAPPER}} .iu-social-share .iu-share__icon' => '--iu-ss-icon-size: {{SIZE}}{{UNIT}}; width: var(--iu-ss-icon-size); height: var(--iu-ss-icon-size);',
                    '{{WRAPPER}} .iu-social-share .iu-share__icon svg' => 'width: 100%; height: 100%;',
                ],
            ]);

            $this->add_responsive_control('gap', [
                'label' => __('Απόσταση από το όνομα δικτύου (px)', 'istodata-utilities'),
                'type' => Controls_Manager::SLIDER,
                'range' => [ 'px' => [ 'min' => 0, 'max' => 64 ] ],
                'default' => [ 'size' => 8, 'unit' => 'px' ],
                'selectors' => [
                    '{{WRAPPER}} .iu-social-share__icons' => '--iu-ss-gap: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .iu-social-share--has-network-names a.iu-share' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]);

            $this->add_control('icon_color', [
                'label' => __('Χρώμα', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .iu-social-share .iu-share__icon' => 'color: {{VALUE}};',
                ],
            ]);

            $this->add_control('icon_hover_color', [
                'label' => __('Χρώμα hover', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .iu-social-share a:hover .iu-share__icon' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .iu-social-share a:hover .iu-share__network-name' => 'color: {{VALUE}};',
                ],
            ]);

            $this->add_control('icon_transition', [
                'label' => __('Διάρκεια μετάβασης (s)', 'istodata-utilities'),
                'type' => Controls_Manager::SLIDER,
                'range' => [ 'px' => [ 'min' => 0, 'max' => 2, 'step' => 0.05 ] ],
                'default' => [ 'size' => 0.2 ],
                'selectors' => [
                    '{{WRAPPER}} .iu-social-share' => '--iu-ss-tr: {{SIZE}}s;',
                ],
            ]);

            $this->end_controls_section();

            $this->start_controls_section('section_style_label', [
                'label' => __('Ετικέτα', 'istodata-utilities'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]);

            $this->add_responsive_control('label_gap', [
                'label' => __('Απόσταση Ετικέτας (px)', 'istodata-utilities'),
                'type' => Controls_Manager::SLIDER,
                'range' => [ 'px' => [ 'min' => 0, 'max' => 64 ] ],
                'default' => [ 'size' => 8, 'unit' => 'px' ],
                'selectors' => [
                    '{{WRAPPER}} .iu-social-share__inner' => '--iu-ss-label-gap: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'share_label!' => '',
                ],
            ]);

            $this->add_control('label_color', [
                'label' => __('Χρώμα', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .iu-social-share__label' => 'color: {{VALUE}};',
                ],
            ]);

            $this->add_control('label_hover_color', [
                'label' => __('Χρώμα hover', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .iu-social-share--popover .iu-social-share__toggle:hover' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .iu-social-share--popover .iu-social-share__toggle:focus-visible' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .iu-social-share--popover .iu-social-share__toggle:hover .iu-social-share__label-icon' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .iu-social-share--popover .iu-social-share__toggle:focus-visible .iu-social-share__label-icon' => 'color: {{VALUE}};',
                ],
                'condition' => [
                    'layout' => 'popover',
                ],
            ]);

            $this->add_group_control(Group_Control_Typography::get_type(), [
                'name' => 'label_typography',
                'selector' => '{{WRAPPER}} .iu-social-share__label',
            ]);

            $this->add_responsive_control('label_icon_size', [
                'label' => __('Μέγεθος εικονιδίου (px)', 'istodata-utilities'),
                'type' => Controls_Manager::SLIDER,
                'range' => [ 'px' => [ 'min' => 8, 'max' => 128 ] ],
                'default' => [ 'size' => 20, 'unit' => 'px' ],
                'selectors' => [
                    '{{WRAPPER}} .iu-social-share__label-icon' => '--iu-ss-label-icon-size: {{SIZE}}{{UNIT}}; width: var(--iu-ss-label-icon-size); height: var(--iu-ss-label-icon-size);',
                ],
                'condition' => [
                    'label_icon[value]!' => '',
                ],
            ]);

            $this->add_control('label_icon_color', [
                'label' => __('Χρώμα εικονιδίου', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .iu-social-share__label-icon' => 'color: {{VALUE}};',
                ],
                'condition' => [
                    'label_icon[value]!' => '',
                ],
            ]);

            $this->add_responsive_control('label_icon_gap', [
                'label' => __('Απόσταση από την ετικέτα (px)', 'istodata-utilities'),
                'type' => Controls_Manager::SLIDER,
                'range' => [ 'px' => [ 'min' => 0, 'max' => 64 ] ],
                'default' => [ 'size' => 8, 'unit' => 'px' ],
                'selectors' => [
                    '{{WRAPPER}} .iu-social-share__label' => '--iu-ss-label-icon-gap: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'label_icon[value]!' => '',
                ],
            ]);

            $this->add_responsive_control('label_align', [
                'label' => __('Στοίχιση', 'istodata-utilities'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [ 'title' => __('Αριστερά', 'istodata-utilities'), 'icon' => 'eicon-text-align-left' ],
                    'center' => [ 'title' => __('Κέντρο', 'istodata-utilities'), 'icon' => 'eicon-text-align-center' ],
                    'right' => [ 'title' => __('Δεξιά', 'istodata-utilities'), 'icon' => 'eicon-text-align-right' ],
                ],
                'default' => 'left',
                'selectors_dictionary' => [
                    'left' => 'left; align-self: flex-start',
                    'center' => 'center; align-self: center',
                    'right' => 'right; align-self: flex-end',
                ],
                'selectors' => [
                    '{{WRAPPER}} .iu-social-share__label' => 'text-align: {{VALUE}};',
                ],
            ]);

            $this->end_controls_section();

            $this->start_controls_section('section_style_network_names', [
                'label' => __('Ονόματα δικτύων', 'istodata-utilities'),
                'tab'   => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_network_names' => 'yes',
                ],
            ]);

            $this->add_control('network_name_color', [
                'label' => __('Χρώμα', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .iu-social-share .iu-share__network-name' => 'color: {{VALUE}};',
                ],
            ]);

            $this->add_group_control(Group_Control_Typography::get_type(), [
                'name' => 'network_name_typography',
                'selector' => '{{WRAPPER}} .iu-social-share .iu-share__network-name',
            ]);

            $this->end_controls_section();

            $this->start_controls_section('section_style_popover', [
                'label' => __('Popover', 'istodata-utilities'),
                'tab'   => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'layout' => 'popover',
                ],
            ]);

            $this->add_control('popover_background_color', [
                'label' => __('Χρώμα φόντου', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .iu-social-share--popover .iu-social-share__icons' => 'background-color: {{VALUE}};',
                ],
            ]);

            $this->add_responsive_control('popover_padding', [
                'label' => __('Padding', 'istodata-utilities'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%', 'rem' ],
                'selectors' => [
                    '{{WRAPPER}} .iu-social-share--popover .iu-social-share__icons' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]);

            $this->add_group_control(Group_Control_Border::get_type(), [
                'name' => 'popover_border',
                'selector' => '{{WRAPPER}} .iu-social-share--popover .iu-social-share__icons',
            ]);

            $this->add_responsive_control('popover_border_radius', [
                'label' => __('Στρογγύλεμα γωνιών', 'istodata-utilities'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%', 'em', 'rem' ],
                'selectors' => [
                    '{{WRAPPER}} .iu-social-share--popover .iu-social-share__icons' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]);

            $this->add_group_control(Group_Control_Box_Shadow::get_type(), [
                'name' => 'popover_box_shadow',
                'selector' => '{{WRAPPER}} .iu-social-share--popover .iu-social-share__icons',
            ]);

            $this->end_controls_section();
        }

        protected function render() {
            $s = $this->get_settings_for_display();

            $url = isset($s['share_url']['url']) && !empty($s['share_url']['url']) ? $s['share_url']['url'] : '';
            if (!$url) {
                $url = function_exists('get_permalink') && get_queried_object_id() ? get_permalink(get_queried_object_id()) : (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            }
            $title = !empty($s['share_title']) ? $s['share_title'] : (function_exists('get_the_title') && get_queried_object_id() ? get_the_title(get_queried_object_id()) : get_bloginfo('name'));

            $enc_url = rawurlencode($url);
            $enc_title = rawurlencode($title);

            $target = (!empty($s['open_new']) && $s['open_new'] === 'yes') ? ' target="_blank"' : '';
            // Always include nofollow by default
            $rel = ' rel="noopener nofollow"';

            $size = isset($s['icon_size']['size']) ? intval($s['icon_size']['size']) : 24;
            $share_label = isset($s['share_label']) ? trim((string) $s['share_label']) : '';
            $layout = isset($s['layout']) ? $s['layout'] : 'horizontal';
            $is_popover = $layout === 'popover';
            $show_network_names = $is_popover || (!empty($s['show_network_names']) && $s['show_network_names'] === 'yes');
            $popover_id = 'iu-social-share-popover-' . $this->get_id();

            // Wrapper uses CSS asset; per-instance values set via Elementor selectors
            echo '<div class="iu-social-share' . ($show_network_names ? ' iu-social-share--has-network-names' : '') . ($is_popover ? ' iu-social-share--popover' : '') . '">';

            echo '<div class="iu-social-share__inner">';

            if ($share_label !== '' || $is_popover) {
                if ($is_popover) {
                    echo '<button type="button" class="iu-social-share__label iu-social-share__toggle" aria-expanded="false" aria-controls="' . esc_attr($popover_id) . '">';
                } else {
                    echo '<div class="iu-social-share__label">';
                }
                $this->render_label_icon($s);
                echo '<span class="iu-social-share__label-text">' . esc_html($share_label !== '' ? $share_label : __('Share', 'istodata-utilities')) . '</span>';
                echo $is_popover ? '</button>' : '</div>';
            }

            echo '<div class="iu-social-share__icons"' . ($is_popover ? ' id="' . esc_attr($popover_id) . '" aria-hidden="true"' : '') . '>';

            // Render each network if enabled
            if (!empty($s['enable_facebook']) && $s['enable_facebook'] === 'yes') {
                $href = 'https://www.facebook.com/sharer/sharer.php?u=' . $enc_url;
                $label = __('Κοινοποίηση στο Facebook', 'istodata-utilities');
                echo '<a class="iu-share iu-share--facebook" href="' . esc_url($href) . '"' . $target . $rel . ' aria-label="' . esc_attr($label) . '">';
                $this->render_icon($s, 'facebook_icon', $size, 'F');
                $this->render_network_name('Facebook', $show_network_names);
                echo '</a>';
            }

            if (!empty($s['enable_linkedin']) && $s['enable_linkedin'] === 'yes') {
                $href = 'https://www.linkedin.com/sharing/share-offsite/?url=' . $enc_url;
                $label = __('Κοινοποίηση στο LinkedIn', 'istodata-utilities');
                echo '<a class="iu-share iu-share--linkedin" href="' . esc_url($href) . '"' . $target . $rel . ' aria-label="' . esc_attr($label) . '">';
                $this->render_icon($s, 'linkedin_icon', $size, 'in');
                $this->render_network_name('LinkedIn', $show_network_names);
                echo '</a>';
            }

            if (!empty($s['enable_whatsapp']) && $s['enable_whatsapp'] === 'yes') {
                $href = 'https://wa.me/?text=' . $enc_title . '%20' . $enc_url;
                $label = __('Κοινοποίηση στο WhatsApp', 'istodata-utilities');
                echo '<a class="iu-share iu-share--whatsapp" href="' . esc_url($href) . '"' . $target . $rel . ' aria-label="' . esc_attr($label) . '">';
                $this->render_icon($s, 'whatsapp_icon', $size, 'WA');
                $this->render_network_name('WhatsApp', $show_network_names);
                echo '</a>';
            }

            if (!empty($s['enable_email']) && $s['enable_email'] === 'yes') {
                $href = 'mailto:?subject=' . $enc_title . '&body=' . $enc_url;
                $label = __('Κοινοποίηση μέσω Email', 'istodata-utilities');
                echo '<a class="iu-share iu-share--email" href="' . esc_url($href) . '"' . $target . $rel . ' aria-label="' . esc_attr($label) . '">';
                $this->render_icon($s, 'email_icon', $size, '@');
                $this->render_network_name('Email', $show_network_names);
                echo '</a>';
            }

            echo '</div>';
            echo '</div>';
            echo '</div>';
        }

        private function render_icon($settings, $icon_key, $size, $fallback_text = '?') {
            $inline_size = max(0, (int) $size) . 'px';
            $icon_style = '--iu-ss-inline-size:' . $inline_size . ';display:inline-flex;align-items:center;justify-content:center;line-height:1;width:var(--iu-ss-icon-size,var(--iu-ss-inline-size));height:var(--iu-ss-icon-size,var(--iu-ss-inline-size));';

            // Preferred: Elementor Icons control
            if (!empty($settings[$icon_key])) {
                $icon = $settings[$icon_key];
                ob_start();
                Icons_Manager::render_icon($icon, [ 'aria-hidden' => 'true' ]);
                $icon_html = ob_get_clean();
                $icon_html = $this->iu_svg_normalize($icon_html, $icon_key);
                if (is_string($icon_html) && $icon_html !== '') {
                    echo '<span class="iu-share__icon" style="' . esc_attr($icon_style) . '">' . $icon_html . '</span>';
                    return;
                }
            }

            // Text fallback as minimal badge
            echo '<span class="iu-share__icon" style="' . esc_attr($icon_style . 'border-radius:4px;background:#eee;color:#333;') . '">' . esc_html($fallback_text) . '</span>';
        }

        private function render_network_name($name, $show_network_names) {
            if (!$show_network_names) {
                return;
            }

            echo '<span class="iu-share__network-name">' . esc_html($name) . '</span>';
        }

        private function render_label_icon($settings) {
            if (empty($settings['label_icon'])) {
                return;
            }

            ob_start();
            Icons_Manager::render_icon($settings['label_icon'], [ 'aria-hidden' => 'true' ]);
            $icon_html = $this->iu_svg_normalize(ob_get_clean(), 'label_icon');
            if (is_string($icon_html) && $icon_html !== '') {
                echo '<span class="iu-social-share__label-icon">' . $icon_html . '</span>';
            }
        }

        // Normalize SVG HTML from Icons_Manager output (strip width/height to allow CSS sizing)
        private function iu_svg_normalize($svg_html, $scope = 'icon') {
            if (!is_string($svg_html) || stripos($svg_html, '<svg') === false) return $svg_html;

            $scope = preg_replace('/[^A-Za-z0-9_-]/', '-', (string) $scope);
            $element_id = preg_replace('/[^A-Za-z0-9_-]/', '-', (string) $this->get_id());
            $id_prefix = 'iu-social-share-' . $element_id . '-' . $scope . '-';
            $id_map = array();

            preg_match_all('/\sid\s*=\s*(["\'])([^"\']+)\1/i', $svg_html, $id_matches, PREG_SET_ORDER);
            foreach ($id_matches as $id_match) {
                $old_id = $id_match[2];
                if (!isset($id_map[$old_id])) {
                    $id_map[$old_id] = $id_prefix . preg_replace('/[^A-Za-z0-9_-]/', '-', $old_id);
                }
            }

            if (!empty($id_map)) {
                $svg_html = preg_replace_callback('/(\sid\s*=\s*)(["\'])([^"\']+)\2/i', function($match) use ($id_map) {
                    return isset($id_map[$match[3]]) ? $match[1] . $match[2] . $id_map[$match[3]] . $match[2] : $match[0];
                }, $svg_html);

                foreach ($id_map as $old_id => $new_id) {
                    $svg_html = preg_replace('/url\(\s*(["\']?)#' . preg_quote($old_id, '/') . '\1\s*\)/i', 'url(#' . $new_id . ')', $svg_html);
                    $svg_html = preg_replace('/((?:xlink:)?href\s*=\s*["\'])#' . preg_quote($old_id, '/') . '(["\'])/i', '$1#' . $new_id . '$2', $svg_html);
                }
            }

            return preg_replace_callback('/<svg\b([^>]*)>/i', function($m){
                $attrs = $m[1];
                // drop width/height
                $attrs = preg_replace('/\swidth\s*=\s*"[^"]*"/i', '', $attrs);
                $attrs = preg_replace('/\sheight\s*=\s*"[^"]*"/i', '', $attrs);
                // clean inline style width/height
                $attrs = preg_replace_callback('/\sstyle\s*=\s*"([^"]*)"/i', function($sm){
                    $style = preg_replace('/(?:^|;|\s)(width|height)\s*:\s*[^;]+;?/i', ';', $sm[1]);
                    $style = preg_replace('/;{2,}/', ';', trim($style));
                    $style = trim($style, '; ');
                    return $style === '' ? '' : ' style="' . esc_attr($style) . '"';
                }, $attrs);
                return '<svg' . $attrs . ' width="100%" height="100%">';
            }, $svg_html, 1);
        }

        // normalize_root_svg_tag and to_number removed as legacy upload fallback is dropped
    }
}
