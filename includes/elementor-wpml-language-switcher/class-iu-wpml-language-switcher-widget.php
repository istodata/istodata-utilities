<?php
// Safety check
if (!defined('ABSPATH')) { exit; }

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

if (!class_exists('IU_WPML_Language_Switcher_Widget')) {
    class IU_WPML_Language_Switcher_Widget extends Widget_Base {
        public function get_name() {
            return 'iu_wpml_language_switcher';
        }

        public function get_title() {
            return __('WPML Language Switcher', 'istodata-utilities');
        }

        public function get_icon() {
            return 'eicon-globe';
        }

        public function get_categories() {
            return array('istodata-kit');
        }

        public function get_style_depends() {
            if (!wp_style_is('iu-elementor-wpml-switcher', 'registered')) {
                wp_register_style(
                    'iu-elementor-wpml-switcher',
                    IU_PLUGIN_URL . 'assets/css/elementor-wpml-language-switcher.css',
                    array(),
                    defined('IU_PLUGIN_VERSION') ? IU_PLUGIN_VERSION : null
                );
            }

            return array('iu-elementor-wpml-switcher');
        }

        public function get_script_depends() {
            if (!wp_script_is('iu-elementor-wpml-switcher', 'registered')) {
                wp_register_script(
                    'iu-elementor-wpml-switcher',
                    IU_PLUGIN_URL . 'assets/js/elementor-wpml-language-switcher.js',
                    array(),
                    defined('IU_PLUGIN_VERSION') ? IU_PLUGIN_VERSION : null,
                    true
                );
            }

            return array('iu-elementor-wpml-switcher');
        }

        protected function register_controls() {
            $this->start_controls_section('section_content', array(
                'label' => __('Content', 'istodata-utilities'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ));

            $this->add_control('label_source', array(
                'label' => __('Label Source', 'istodata-utilities'),
                'type' => Controls_Manager::SELECT,
                'options' => array(
                    'code' => __('Language Code', 'istodata-utilities'),
                    'native_name' => __('Native Name', 'istodata-utilities'),
                    'native_name_short' => __('Native Name (First 2 Letters)', 'istodata-utilities'),
                    'translated_name' => __('Translated Name', 'istodata-utilities'),
                ),
                'default' => 'code',
            ));

            $this->add_control('show_code_with_label', array(
                'label' => __('Show Code With Label', 'istodata-utilities'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'istodata-utilities'),
                'label_off' => __('No', 'istodata-utilities'),
                'return_value' => 'yes',
                'default' => '',
                'condition' => array(
                    'label_source' => array('native_name', 'translated_name'),
                ),
            ));

            $this->add_control('show_flags', array(
                'label' => __('Show Flags', 'istodata-utilities'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'istodata-utilities'),
                'label_off' => __('No', 'istodata-utilities'),
                'return_value' => 'yes',
                'default' => '',
            ));

            $this->add_control('show_arrow', array(
                'label' => __('Show Arrow', 'istodata-utilities'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'istodata-utilities'),
                'label_off' => __('No', 'istodata-utilities'),
                'return_value' => 'yes',
                'default' => '',
            ));

            $this->add_control('show_unavailable_languages', array(
                'label' => __('Show Unavailable Languages', 'istodata-utilities'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'istodata-utilities'),
                'label_off' => __('No', 'istodata-utilities'),
                'return_value' => 'yes',
                'default' => '',
            ));

            $this->add_control('open_direction', array(
                'label' => __('Open Direction', 'istodata-utilities'),
                'type' => Controls_Manager::CHOOSE,
                'options' => array(
                    'down' => array(
                        'title' => __('Down', 'istodata-utilities'),
                        'icon' => 'eicon-v-align-bottom',
                    ),
                    'up' => array(
                        'title' => __('Up', 'istodata-utilities'),
                        'icon' => 'eicon-v-align-top',
                    ),
                ),
                'default' => 'down',
                'toggle' => false,
            ));

            $this->add_responsive_control('alignment', array(
                'label' => __('Alignment', 'istodata-utilities'),
                'type' => Controls_Manager::CHOOSE,
                'options' => array(
                    'flex-start' => array(
                        'title' => __('Left', 'istodata-utilities'),
                        'icon' => 'eicon-text-align-left',
                    ),
                    'center' => array(
                        'title' => __('Center', 'istodata-utilities'),
                        'icon' => 'eicon-text-align-center',
                    ),
                    'flex-end' => array(
                        'title' => __('Right', 'istodata-utilities'),
                        'icon' => 'eicon-text-align-right',
                    ),
                ),
                'default' => 'flex-start',
                'selectors' => array(
                    '{{WRAPPER}} .iu-wpml-switcher-wrap' => 'justify-content: {{VALUE}};',
                ),
            ));

            $this->add_control('aria_label', array(
                'label' => __('Accessibility Label', 'istodata-utilities'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Change language', 'istodata-utilities'),
                'dynamic' => array('active' => true),
            ));

            $this->end_controls_section();

            $this->start_controls_section('section_trigger_style', array(
                'label' => __('Trigger', 'istodata-utilities'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ));

            $this->add_group_control(Group_Control_Typography::get_type(), array(
                'name' => 'trigger_typography',
                'selector' => '{{WRAPPER}} .iu-wpml-switcher__toggle, {{WRAPPER}} .iu-wpml-switcher__menu a, {{WRAPPER}} .iu-wpml-switcher__menu span',
            ));

            $this->add_control('trigger_text_color', array(
                'label' => __('Text Color', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .iu-wpml-switcher' => '--iu-wpml-trigger-color: {{VALUE}};',
                    '{{WRAPPER}} .iu-wpml-switcher__toggle' => 'color: {{VALUE}};',
                ),
            ));

            $this->add_control('trigger_background_color', array(
                'label' => __('Background', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .iu-wpml-switcher' => '--iu-wpml-trigger-background: {{VALUE}};',
                    '{{WRAPPER}} .iu-wpml-switcher__toggle' => 'background-color: {{VALUE}};',
                ),
            ));

            $this->add_control('trigger_hover_text_color', array(
                'label' => __('Hover Text Color', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .iu-wpml-switcher__toggle:hover, {{WRAPPER}} .iu-wpml-switcher__toggle:focus, {{WRAPPER}} .iu-wpml-switcher:hover .iu-wpml-switcher__toggle, {{WRAPPER}} .iu-wpml-switcher:focus-within .iu-wpml-switcher__toggle' => 'color: {{VALUE}};',
                ),
            ));

            $this->add_control('trigger_hover_background_color', array(
                'label' => __('Hover Background', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .iu-wpml-switcher__toggle:hover, {{WRAPPER}} .iu-wpml-switcher__toggle:focus, {{WRAPPER}} .iu-wpml-switcher:hover .iu-wpml-switcher__toggle, {{WRAPPER}} .iu-wpml-switcher:focus-within .iu-wpml-switcher__toggle' => 'background-color: {{VALUE}};',
                ),
            ));

            $this->add_control('trigger_hover_border_color', array(
                'label' => __('Hover Border Color', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .iu-wpml-switcher__toggle:hover, {{WRAPPER}} .iu-wpml-switcher__toggle:focus, {{WRAPPER}} .iu-wpml-switcher.is-open .iu-wpml-switcher__toggle' => 'border-color: {{VALUE}};',
                ),
            ));

            $this->add_group_control(Group_Control_Border::get_type(), array(
                'name' => 'trigger_border',
                'selector' => '{{WRAPPER}} .iu-wpml-switcher__toggle',
            ));

            $this->add_responsive_control('trigger_border_radius', array(
                'label' => __('Border Radius', 'istodata-utilities'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%'),
                'selectors' => array(
                    '{{WRAPPER}} .iu-wpml-switcher__toggle' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            ));

            $this->add_responsive_control('trigger_padding', array(
                'label' => __('Padding', 'istodata-utilities'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%', 'em', 'rem'),
                'selectors' => array(
                    '{{WRAPPER}} .iu-wpml-switcher__toggle' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            ));

            $this->add_responsive_control('trigger_text_alignment', array(
                'label' => __('Text Alignment', 'istodata-utilities'),
                'type' => Controls_Manager::CHOOSE,
                'options' => array(
                    'left' => array(
                        'title' => __('Left', 'istodata-utilities'),
                        'icon' => 'eicon-text-align-left',
                    ),
                    'center' => array(
                        'title' => __('Center', 'istodata-utilities'),
                        'icon' => 'eicon-text-align-center',
                    ),
                    'right' => array(
                        'title' => __('Right', 'istodata-utilities'),
                        'icon' => 'eicon-text-align-right',
                    ),
                ),
                'default' => 'left',
                'selectors_dictionary' => array(
                    'left' => 'justify-content:flex-start; text-align:left;',
                    'center' => 'justify-content:center; text-align:center;',
                    'right' => 'justify-content:flex-end; text-align:right;',
                ),
                'selectors' => array(
                    '{{WRAPPER}} .iu-wpml-switcher__toggle' => '{{VALUE}}',
                    '{{WRAPPER}} .iu-wpml-switcher__current' => '{{VALUE}} width: 100%;',
                ),
            ));

            $this->add_responsive_control('trigger_min_width', array(
                'label' => __('Min Width', 'istodata-utilities'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => array('px', '%'),
                'range' => array(
                    'px' => array('min' => 0, 'max' => 400),
                    '%' => array('min' => 0, 'max' => 100),
                ),
                'selectors' => array(
                    '{{WRAPPER}} .iu-wpml-switcher' => 'min-width: {{SIZE}}{{UNIT}};',
                ),
            ));

            $this->end_controls_section();

            $this->start_controls_section('section_arrow_style', array(
                'label' => __('Arrow', 'istodata-utilities'),
                'tab'   => Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'show_arrow' => 'yes',
                ),
            ));

            $this->add_control('arrow_color', array(
                'label' => __('Color', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .iu-wpml-switcher' => '--iu-wpml-arrow-color: {{VALUE}};',
                ),
                'condition' => array(
                    'show_arrow' => 'yes',
                ),
            ));

            $this->add_control('arrow_size', array(
                'label' => __('Size', 'istodata-utilities'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => array('px'),
                'range' => array(
                    'px' => array('min' => 4, 'max' => 18),
                ),
                'selectors' => array(
                    '{{WRAPPER}} .iu-wpml-switcher' => '--iu-wpml-arrow-size: {{SIZE}}{{UNIT}};',
                ),
                'condition' => array(
                    'show_arrow' => 'yes',
                ),
            ));

            $this->add_responsive_control('arrow_right_offset', array(
                'label' => __('Right Offset', 'istodata-utilities'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => array('px', '%'),
                'range' => array(
                    'px' => array('min' => 0, 'max' => 50),
                    '%' => array('min' => 0, 'max' => 50),
                ),
                'selectors' => array(
                    '{{WRAPPER}} .iu-wpml-switcher' => '--iu-wpml-arrow-right: {{SIZE}}{{UNIT}};',
                ),
                'condition' => array(
                    'show_arrow' => 'yes',
                ),
            ));

            $this->add_responsive_control('arrow_top_offset', array(
                'label' => __('Top Offset', 'istodata-utilities'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => array('px', '%'),
                'range' => array(
                    'px' => array('min' => 0, 'max' => 50),
                    '%' => array('min' => 0, 'max' => 100),
                ),
                'selectors' => array(
                    '{{WRAPPER}} .iu-wpml-switcher' => '--iu-wpml-arrow-top: {{SIZE}}{{UNIT}};',
                ),
                'condition' => array(
                    'show_arrow' => 'yes',
                ),
            ));

            $this->end_controls_section();

            $this->start_controls_section('section_dropdown_style', array(
                'label' => __('Dropdown Panel', 'istodata-utilities'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ));

            $this->add_control('menu_background_color', array(
                'label' => __('Panel Background', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .iu-wpml-switcher' => '--iu-wpml-menu-background: {{VALUE}};',
                    '{{WRAPPER}} .iu-wpml-switcher__menu' => 'background-color: {{VALUE}};',
                ),
            ));

            $this->add_group_control(Group_Control_Border::get_type(), array(
                'name' => 'menu_border',
                'selector' => '{{WRAPPER}} .iu-wpml-switcher__menu',
            ));

            $this->add_responsive_control('menu_border_radius', array(
                'label' => __('Panel Radius', 'istodata-utilities'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%'),
                'selectors' => array(
                    '{{WRAPPER}} .iu-wpml-switcher__menu' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}; overflow: hidden;',
                ),
            ));

            $this->add_responsive_control('menu_padding', array(
                'label' => __('Panel Padding', 'istodata-utilities'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%', 'em', 'rem'),
                'selectors' => array(
                    '{{WRAPPER}} .iu-wpml-switcher__menu' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            ));

            $this->add_responsive_control('menu_offset', array(
                'label' => __('Panel Offset', 'istodata-utilities'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => array('px'),
                'range' => array(
                    'px' => array('min' => 0, 'max' => 60),
                ),
                'selectors' => array(
                    '{{WRAPPER}} .iu-wpml-switcher' => '--iu-wpml-menu-offset: {{SIZE}}{{UNIT}};',
                ),
            ));

            $this->add_control('menu_z_index', array(
                'label' => __('Panel Z-Index', 'istodata-utilities'),
                'type' => Controls_Manager::NUMBER,
                'default' => 2147483640,
                'min' => 0,
                'step' => 1,
                'selectors' => array(
                    '{{WRAPPER}}' => 'position: relative;',
                    '{{WRAPPER}} > .elementor-widget-container' => 'position: relative; overflow: visible;',
                    '{{WRAPPER}}.iu-wpml-switcher-is-open' => 'z-index: {{VALUE}};',
                    '{{WRAPPER}}.iu-wpml-switcher-is-open > .elementor-widget-container' => 'z-index: {{VALUE}};',
                    '{{WRAPPER}} .iu-wpml-switcher.is-open' => 'z-index: {{VALUE}};',
                    '{{WRAPPER}} .iu-wpml-switcher.is-open .iu-wpml-switcher__menu' => 'z-index: {{VALUE}};',
                ),
            ));

            $this->add_group_control(Group_Control_Box_Shadow::get_type(), array(
                'name' => 'menu_box_shadow',
                'selector' => '{{WRAPPER}} .iu-wpml-switcher__menu',
            ));

            $this->add_control('item_text_color', array(
                'label' => __('Item Text Color', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .iu-wpml-switcher' => '--iu-wpml-item-color: {{VALUE}};',
                    '{{WRAPPER}} .iu-wpml-switcher__item > a, {{WRAPPER}} .iu-wpml-switcher__item > a span, {{WRAPPER}} .iu-wpml-switcher__item > span.is-unavailable' => 'color: {{VALUE}};',
                ),
            ));

            $this->add_control('item_background_color', array(
                'label' => __('Item Background', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .iu-wpml-switcher' => '--iu-wpml-item-background: {{VALUE}};',
                    '{{WRAPPER}} .iu-wpml-switcher__item > a, {{WRAPPER}} .iu-wpml-switcher__item > span.is-unavailable' => 'background-color: {{VALUE}};',
                ),
            ));

            $this->add_control('item_hover_text_color', array(
                'label' => __('Item Hover Text Color', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .iu-wpml-switcher' => '--iu-wpml-item-hover-color: {{VALUE}};',
                    '{{WRAPPER}} .iu-wpml-switcher__item:hover > a, {{WRAPPER}} .iu-wpml-switcher__item:hover > a span, {{WRAPPER}} .iu-wpml-switcher__item:focus-within > a, {{WRAPPER}} .iu-wpml-switcher__item:focus-within > a span' => 'color: {{VALUE}};',
                ),
            ));

            $this->add_control('item_hover_background_color', array(
                'label' => __('Item Hover Background', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .iu-wpml-switcher' => '--iu-wpml-item-hover-background: {{VALUE}};',
                    '{{WRAPPER}} .iu-wpml-switcher__item:hover > a, {{WRAPPER}} .iu-wpml-switcher__item:focus-within > a' => 'background-color: {{VALUE}};',
                ),
            ));

            $this->add_responsive_control('item_padding', array(
                'label' => __('Item Padding', 'istodata-utilities'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%', 'em', 'rem'),
                'selectors' => array(
                    '{{WRAPPER}} .iu-wpml-switcher__menu a, {{WRAPPER}} .iu-wpml-switcher__menu span' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            ));

            $this->add_responsive_control('item_text_alignment', array(
                'label' => __('Item Alignment', 'istodata-utilities'),
                'type' => Controls_Manager::CHOOSE,
                'options' => array(
                    'left' => array(
                        'title' => __('Left', 'istodata-utilities'),
                        'icon' => 'eicon-text-align-left',
                    ),
                    'center' => array(
                        'title' => __('Center', 'istodata-utilities'),
                        'icon' => 'eicon-text-align-center',
                    ),
                    'right' => array(
                        'title' => __('Right', 'istodata-utilities'),
                        'icon' => 'eicon-text-align-right',
                    ),
                ),
                'default' => 'left',
                'selectors_dictionary' => array(
                    'left' => 'justify-content:flex-start; text-align:left;',
                    'center' => 'justify-content:center; text-align:center;',
                    'right' => 'justify-content:flex-end; text-align:right;',
                ),
                'selectors' => array(
                    '{{WRAPPER}} .iu-wpml-switcher__item > a, {{WRAPPER}} .iu-wpml-switcher__item > span' => '{{VALUE}}',
                ),
            ));

            $this->end_controls_section();

            $this->start_controls_section('section_sticky_style', array(
                'label' => __('Sticky State', 'istodata-utilities'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ));

            $this->add_control('sticky_trigger_text_color', array(
                'label' => __('Trigger Text Color', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '.elementor-sticky--effects {{WRAPPER}} .iu-wpml-switcher' => '--iu-wpml-sticky-trigger-color: {{VALUE}};',
                    '.elementor-sticky--effects {{WRAPPER}} .iu-wpml-switcher__toggle' => 'color: {{VALUE}};',
                    '.elementor-sticky--effects {{WRAPPER}} .iu-wpml-switcher:hover .iu-wpml-switcher__toggle, .elementor-sticky--effects {{WRAPPER}} .iu-wpml-switcher:focus-within .iu-wpml-switcher__toggle, .elementor-sticky--effects {{WRAPPER}} .iu-wpml-switcher.is-open .iu-wpml-switcher__toggle' => 'color: {{VALUE}};',
                ),
            ));

            $this->add_control('sticky_trigger_background_color', array(
                'label' => __('Trigger Background', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '.elementor-sticky--effects {{WRAPPER}} .iu-wpml-switcher' => '--iu-wpml-sticky-trigger-background: {{VALUE}};',
                    '.elementor-sticky--effects {{WRAPPER}} .iu-wpml-switcher__toggle' => 'background-color: {{VALUE}};',
                    '.elementor-sticky--effects {{WRAPPER}} .iu-wpml-switcher:hover .iu-wpml-switcher__toggle, .elementor-sticky--effects {{WRAPPER}} .iu-wpml-switcher:focus-within .iu-wpml-switcher__toggle, .elementor-sticky--effects {{WRAPPER}} .iu-wpml-switcher.is-open .iu-wpml-switcher__toggle' => 'background-color: {{VALUE}};',
                ),
            ));

            $this->add_control('sticky_trigger_hover_text_color', array(
                'label' => __('Trigger Hover Text Color', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '.elementor-sticky--effects {{WRAPPER}} .iu-wpml-switcher:hover .iu-wpml-switcher__toggle, .elementor-sticky--effects {{WRAPPER}} .iu-wpml-switcher:focus-within .iu-wpml-switcher__toggle, .elementor-sticky--effects {{WRAPPER}} .iu-wpml-switcher.is-open .iu-wpml-switcher__toggle' => 'color: {{VALUE}};',
                ),
            ));

            $this->add_control('sticky_trigger_hover_background_color', array(
                'label' => __('Trigger Hover Background', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '.elementor-sticky--effects {{WRAPPER}} .iu-wpml-switcher:hover .iu-wpml-switcher__toggle, .elementor-sticky--effects {{WRAPPER}} .iu-wpml-switcher:focus-within .iu-wpml-switcher__toggle, .elementor-sticky--effects {{WRAPPER}} .iu-wpml-switcher.is-open .iu-wpml-switcher__toggle' => 'background-color: {{VALUE}};',
                ),
            ));

            $this->add_control('sticky_trigger_border_color', array(
                'label' => __('Trigger Border Color', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '.elementor-sticky--effects {{WRAPPER}} .iu-wpml-switcher__toggle' => 'border-color: {{VALUE}};',
                    '.elementor-sticky--effects {{WRAPPER}} .iu-wpml-switcher:hover .iu-wpml-switcher__toggle, .elementor-sticky--effects {{WRAPPER}} .iu-wpml-switcher:focus-within .iu-wpml-switcher__toggle, .elementor-sticky--effects {{WRAPPER}} .iu-wpml-switcher.is-open .iu-wpml-switcher__toggle' => 'border-color: {{VALUE}};',
                ),
            ));

            $this->add_control('sticky_trigger_hover_border_color', array(
                'label' => __('Trigger Hover Border Color', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '.elementor-sticky--effects {{WRAPPER}} .iu-wpml-switcher:hover .iu-wpml-switcher__toggle, .elementor-sticky--effects {{WRAPPER}} .iu-wpml-switcher:focus-within .iu-wpml-switcher__toggle, .elementor-sticky--effects {{WRAPPER}} .iu-wpml-switcher.is-open .iu-wpml-switcher__toggle' => 'border-color: {{VALUE}};',
                ),
            ));

            $this->add_control('sticky_arrow_color', array(
                'label' => __('Arrow Color', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '.elementor-sticky--effects {{WRAPPER}} .iu-wpml-switcher' => '--iu-wpml-sticky-arrow-color: {{VALUE}};',
                ),
                'condition' => array(
                    'show_arrow' => 'yes',
                ),
            ));

            $this->add_control('sticky_item_text_color', array(
                'label' => __('Item Text Color', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '.elementor-sticky--effects {{WRAPPER}} .iu-wpml-switcher' => '--iu-wpml-sticky-item-color: {{VALUE}};',
                    '.elementor-sticky--effects {{WRAPPER}} .iu-wpml-switcher__item > a, .elementor-sticky--effects {{WRAPPER}} .iu-wpml-switcher__item > a span, .elementor-sticky--effects {{WRAPPER}} .iu-wpml-switcher__item > span.is-unavailable' => 'color: {{VALUE}};',
                ),
            ));

            $this->add_control('sticky_menu_background_color', array(
                'label' => __('Panel Background', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '.elementor-sticky--effects {{WRAPPER}} .iu-wpml-switcher__menu' => 'background-color: {{VALUE}};',
                ),
            ));

            $this->add_control('sticky_item_background_color', array(
                'label' => __('Item Background', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '.elementor-sticky--effects {{WRAPPER}} .iu-wpml-switcher' => '--iu-wpml-sticky-item-background: {{VALUE}};',
                    '.elementor-sticky--effects {{WRAPPER}} .iu-wpml-switcher__item > a, .elementor-sticky--effects {{WRAPPER}} .iu-wpml-switcher__item > span.is-unavailable' => 'background-color: {{VALUE}};',
                ),
            ));

            $this->add_control('sticky_item_hover_text_color', array(
                'label' => __('Item Hover Text Color', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '.elementor-sticky--effects {{WRAPPER}} .iu-wpml-switcher' => '--iu-wpml-sticky-item-hover-color: {{VALUE}};',
                    '.elementor-sticky--effects {{WRAPPER}} .iu-wpml-switcher__item:hover > a, .elementor-sticky--effects {{WRAPPER}} .iu-wpml-switcher__item:hover > a span, .elementor-sticky--effects {{WRAPPER}} .iu-wpml-switcher__item:focus-within > a, .elementor-sticky--effects {{WRAPPER}} .iu-wpml-switcher__item:focus-within > a span' => 'color: {{VALUE}};',
                ),
            ));

            $this->add_control('sticky_item_hover_background_color', array(
                'label' => __('Item Hover Background', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '.elementor-sticky--effects {{WRAPPER}} .iu-wpml-switcher' => '--iu-wpml-sticky-item-hover-background: {{VALUE}};',
                    '.elementor-sticky--effects {{WRAPPER}} .iu-wpml-switcher__item:hover > a, .elementor-sticky--effects {{WRAPPER}} .iu-wpml-switcher__item:focus-within > a' => 'background-color: {{VALUE}};',
                ),
            ));

            $this->end_controls_section();
        }

        protected function render() {
            $settings = $this->get_settings_for_display();
            $is_editor = $this->is_editor_mode();

            if (!$this->is_wpml_available()) {
                if ($is_editor) {
                    echo '<div class="iu-wpml-switcher-notice">' . esc_html__('WPML is required for this widget.', 'istodata-utilities') . '</div>';
                }
                return;
            }

            $languages = $this->get_languages($settings);
            if (empty($languages) && $is_editor) {
                $languages = $this->get_preview_languages();
            }

            if (empty($languages)) {
                return;
            }

            $current = null;
            $items = array();
            foreach ($languages as $language) {
                if (!empty($language['active'])) {
                    $current = $language;
                } else {
                    $items[] = $language;
                }
            }

            if (!$current) {
                $current = reset($languages);
            }

            if (!$current) {
                return;
            }

            $dropdown_id = 'iu-wpml-switcher-menu-' . esc_attr($this->get_id());
            $has_items = !empty($items);

            $this->add_render_attribute('wrap', 'class', 'iu-wpml-switcher-wrap');
            $this->add_render_attribute('switcher', 'class', array(
                'iu-wpml-switcher',
                'iu-wpml-switcher--dir-' . (!empty($settings['open_direction']) && $settings['open_direction'] === 'up' ? 'up' : 'down'),
            ));
            if (!$has_items) {
                $this->add_render_attribute('switcher', 'class', 'iu-wpml-switcher--single');
            }
            if (!empty($settings['show_arrow'])) {
                $this->add_render_attribute('switcher', 'class', 'iu-wpml-switcher--has-arrow');
            }

            $this->add_render_attribute('toggle', 'class', 'iu-wpml-switcher__toggle');
            $this->add_render_attribute('toggle', 'type', 'button');
            $this->add_render_attribute('toggle', 'aria-expanded', 'false');
            $this->add_render_attribute('toggle', 'aria-haspopup', 'true');
            $this->add_render_attribute('toggle', 'aria-controls', $dropdown_id);
            $this->add_render_attribute('toggle', 'aria-label', !empty($settings['aria_label']) ? $settings['aria_label'] : __('Change language', 'istodata-utilities'));
            if (!$has_items) {
                $this->add_render_attribute('toggle', 'disabled', 'disabled');
            }

            echo '<div ' . $this->get_render_attribute_string('wrap') . '>';
            echo '<div ' . $this->get_render_attribute_string('switcher') . '>';
            echo $this->get_sticky_inline_css($settings); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo '<button ' . $this->get_render_attribute_string('toggle') . '>';
            echo '<span class="iu-wpml-switcher__current">';
            echo $this->get_flag_html($current, $settings); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo '<span class="iu-wpml-switcher__label">' . esc_html($this->get_language_label($current, $settings)) . '</span>';
            echo '</span>';
            if (!empty($settings['show_arrow'])) {
                echo '<span class="iu-wpml-switcher__arrow" aria-hidden="true"></span>';
            }
            echo '</button>';

            if ($has_items) {
                echo '<ul id="' . esc_attr($dropdown_id) . '" class="iu-wpml-switcher__menu">';
                foreach ($items as $language) {
                    echo '<li class="iu-wpml-switcher__item">';
                    if (!empty($language['url'])) {
                        echo '<a href="' . esc_url($language['url']) . '">';
                        echo $this->get_flag_html($language, $settings); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        echo '<span>' . esc_html($this->get_language_label($language, $settings)) . '</span>';
                        echo '</a>';
                    } else {
                        echo '<span class="is-unavailable">';
                        echo $this->get_flag_html($language, $settings); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        echo '<span>' . esc_html($this->get_language_label($language, $settings)) . '</span>';
                        echo '</span>';
                    }
                    echo '</li>';
                }
                echo '</ul>';
            }

            echo '</div>';
            echo '</div>';
        }

        private function get_sticky_inline_css($settings) {
            $widget_selector = '.elementor-element.elementor-element-' . preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $this->get_id());
            if ($widget_selector === '.elementor-element.elementor-element-') {
                return '';
            }

            $sticky_scope = '.elementor-sticky--effects ' . $widget_selector;
            $rules = array();

            if (!empty($settings['sticky_trigger_text_color'])) {
                $value = sanitize_hex_color($settings['sticky_trigger_text_color']) ?: $settings['sticky_trigger_text_color'];
                $rules[] = $sticky_scope . ' .iu-wpml-switcher__toggle{color:' . esc_attr($value) . ' !important;}';
                $rules[] = $sticky_scope . ' .iu-wpml-switcher:hover .iu-wpml-switcher__toggle,' . $sticky_scope . ' .iu-wpml-switcher:focus-within .iu-wpml-switcher__toggle,' . $sticky_scope . ' .iu-wpml-switcher.is-open .iu-wpml-switcher__toggle{color:' . esc_attr($value) . ' !important;}';
            }

            if (!empty($settings['sticky_trigger_background_color'])) {
                $value = sanitize_hex_color($settings['sticky_trigger_background_color']) ?: $settings['sticky_trigger_background_color'];
                $rules[] = $sticky_scope . ' .iu-wpml-switcher__toggle{background-color:' . esc_attr($value) . ' !important;}';
            }

            if (!empty($settings['sticky_trigger_border_color'])) {
                $value = sanitize_hex_color($settings['sticky_trigger_border_color']) ?: $settings['sticky_trigger_border_color'];
                $rules[] = $sticky_scope . ' .iu-wpml-switcher__toggle{border-color:' . esc_attr($value) . ' !important;}';
            }

            if (!empty($settings['sticky_trigger_hover_text_color'])) {
                $value = sanitize_hex_color($settings['sticky_trigger_hover_text_color']) ?: $settings['sticky_trigger_hover_text_color'];
                $rules[] = $sticky_scope . ' .iu-wpml-switcher:hover .iu-wpml-switcher__toggle,' . $sticky_scope . ' .iu-wpml-switcher:focus-within .iu-wpml-switcher__toggle,' . $sticky_scope . ' .iu-wpml-switcher.is-open .iu-wpml-switcher__toggle{color:' . esc_attr($value) . ' !important;}';
            }

            if (!empty($settings['sticky_trigger_hover_background_color'])) {
                $value = sanitize_hex_color($settings['sticky_trigger_hover_background_color']) ?: $settings['sticky_trigger_hover_background_color'];
                $rules[] = $sticky_scope . ' .iu-wpml-switcher:hover .iu-wpml-switcher__toggle,' . $sticky_scope . ' .iu-wpml-switcher:focus-within .iu-wpml-switcher__toggle,' . $sticky_scope . ' .iu-wpml-switcher.is-open .iu-wpml-switcher__toggle{background-color:' . esc_attr($value) . ' !important;}';
            }

            if (!empty($settings['sticky_trigger_hover_border_color'])) {
                $value = sanitize_hex_color($settings['sticky_trigger_hover_border_color']) ?: $settings['sticky_trigger_hover_border_color'];
                $rules[] = $sticky_scope . ' .iu-wpml-switcher:hover .iu-wpml-switcher__toggle,' . $sticky_scope . ' .iu-wpml-switcher:focus-within .iu-wpml-switcher__toggle,' . $sticky_scope . ' .iu-wpml-switcher.is-open .iu-wpml-switcher__toggle{border-color:' . esc_attr($value) . ' !important;}';
            }

            if (!empty($settings['sticky_arrow_color'])) {
                $value = sanitize_hex_color($settings['sticky_arrow_color']) ?: $settings['sticky_arrow_color'];
                $rules[] = $sticky_scope . ' .iu-wpml-switcher__arrow{--iu-wpml-arrow-color:' . esc_attr($value) . ' !important;background-color:' . esc_attr($value) . ' !important;}';
            }

            if (!empty($settings['sticky_menu_background_color'])) {
                $value = sanitize_hex_color($settings['sticky_menu_background_color']) ?: $settings['sticky_menu_background_color'];
                $rules[] = $sticky_scope . ' .iu-wpml-switcher__menu{background-color:' . esc_attr($value) . ' !important;}';
            }

            if (!empty($settings['sticky_item_text_color'])) {
                $value = sanitize_hex_color($settings['sticky_item_text_color']) ?: $settings['sticky_item_text_color'];
                $rules[] = $sticky_scope . ' .iu-wpml-switcher__item>a,' . $sticky_scope . ' .iu-wpml-switcher__item>a span,' . $sticky_scope . ' .iu-wpml-switcher__item>span.is-unavailable{color:' . esc_attr($value) . ' !important;}';
            }

            if (!empty($settings['sticky_item_background_color'])) {
                $value = sanitize_hex_color($settings['sticky_item_background_color']) ?: $settings['sticky_item_background_color'];
                $rules[] = $sticky_scope . ' .iu-wpml-switcher__item>a,' . $sticky_scope . ' .iu-wpml-switcher__item>span.is-unavailable{background-color:' . esc_attr($value) . ' !important;}';
            }

            if (!empty($settings['sticky_item_hover_text_color'])) {
                $value = sanitize_hex_color($settings['sticky_item_hover_text_color']) ?: $settings['sticky_item_hover_text_color'];
                $rules[] = $sticky_scope . ' .iu-wpml-switcher__item:hover>a,' . $sticky_scope . ' .iu-wpml-switcher__item:hover>a span,' . $sticky_scope . ' .iu-wpml-switcher__item:focus-within>a,' . $sticky_scope . ' .iu-wpml-switcher__item:focus-within>a span{color:' . esc_attr($value) . ' !important;}';
            }

            if (!empty($settings['sticky_item_hover_background_color'])) {
                $value = sanitize_hex_color($settings['sticky_item_hover_background_color']) ?: $settings['sticky_item_hover_background_color'];
                $rules[] = $sticky_scope . ' .iu-wpml-switcher__item:hover>a,' . $sticky_scope . ' .iu-wpml-switcher__item:focus-within>a{background-color:' . esc_attr($value) . ' !important;}';
            }

            if (empty($rules)) {
                return '';
            }

            return '<style>' . implode('', $rules) . '</style>';
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

        private function get_languages($settings) {
            $languages = apply_filters('wpml_active_languages', null, array(
                'skip_missing' => !empty($settings['show_unavailable_languages']) ? 0 : 1,
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

        private function get_preview_languages() {
            return array(
                array(
                    'language_code' => 'en',
                    'native_name' => 'English',
                    'translated_name' => 'English',
                    'country_flag_url' => '',
                    'url' => '#',
                    'active' => true,
                ),
                array(
                    'language_code' => 'el',
                    'native_name' => 'Ελληνικά',
                    'translated_name' => 'Greek',
                    'country_flag_url' => '',
                    'url' => '#',
                    'active' => false,
                ),
                array(
                    'language_code' => 'de',
                    'native_name' => 'Deutsch',
                    'translated_name' => 'German',
                    'country_flag_url' => '',
                    'url' => '#',
                    'active' => false,
                ),
            );
        }

        private function get_language_label($language, $settings) {
            $label_source = !empty($settings['label_source']) ? $settings['label_source'] : 'code';
            $code = !empty($language['language_code']) ? strtoupper((string) $language['language_code']) : '';

            if ($label_source === 'native_name' && !empty($language['native_name'])) {
                $label = (string) $language['native_name'];
            } else if ($label_source === 'native_name_short' && !empty($language['native_name'])) {
                $label = $this->get_native_name_initials($language['native_name']);
            } else if ($label_source === 'translated_name' && !empty($language['translated_name'])) {
                $label = (string) $language['translated_name'];
            } else {
                $label = $code;
            }

            if (in_array($label_source, array('native_name', 'translated_name'), true) && !empty($settings['show_code_with_label']) && $code !== '') {
                $label = $code . ' - ' . $label;
            }

            return $label !== '' ? $label : $code;
        }

        private function get_native_name_initials($native_name) {
            $characters = preg_split('//u', trim((string) $native_name), -1, PREG_SPLIT_NO_EMPTY);
            if (!is_array($characters) || empty($characters)) {
                return '';
            }

            $initials = implode('', array_slice($characters, 0, 2));
            return function_exists('mb_strtoupper') ? mb_strtoupper($initials, 'UTF-8') : strtoupper($initials);
        }

        private function get_flag_html($language, $settings) {
            if (empty($settings['show_flags']) || empty($language['country_flag_url'])) {
                return '';
            }

            return sprintf(
                '<img class="iu-wpml-switcher__flag" src="%1$s" alt="" loading="lazy" decoding="async" />',
                esc_url($language['country_flag_url'])
            );
        }
    }
}
