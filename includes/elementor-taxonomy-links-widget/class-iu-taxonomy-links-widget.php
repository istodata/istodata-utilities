<?php
// Safety check
if (!defined('ABSPATH')) { exit; }

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Widget_Base;

if (!class_exists('IU_Taxonomy_Links_Widget')) {
    class IU_Taxonomy_Links_Widget extends Widget_Base {
        public function get_name() {
            return 'iu_taxonomy_links';
        }

        public function get_title() {
            return __('Taxonomy Links', 'istodata-utilities');
        }

        public function get_icon() {
            return 'eicon-post-list';
        }

        public function get_categories() {
            return array('istodata-kit');
        }

        public function get_style_depends() {
            if (!wp_style_is('iu-taxonomy-links', 'registered')) {
                wp_register_style(
                    'iu-taxonomy-links',
                    IU_PLUGIN_URL . 'assets/css/taxonomy-links.css',
                    array(),
                    defined('IU_PLUGIN_VERSION') ? IU_PLUGIN_VERSION : null
                );
            }

            return array('iu-taxonomy-links');
        }

        protected function register_controls() {
            $taxonomy_options = $this->get_taxonomy_options();
            $default_taxonomy = isset($taxonomy_options['category']) ? 'category' : key($taxonomy_options);

            $this->start_controls_section('section_content', array(
                'label' => __('Content', 'istodata-utilities'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ));

            $this->add_control('taxonomy', array(
                'label' => __('Taxonomy Source', 'istodata-utilities'),
                'type' => Controls_Manager::SELECT,
                'options' => $taxonomy_options,
                'default' => $default_taxonomy ? $default_taxonomy : '',
            ));

            $this->add_control('display_mode', array(
                'label' => __('Display As', 'istodata-utilities'),
                'type' => Controls_Manager::SELECT,
                'options' => array(
                    'links' => __('Links / Buttons', 'istodata-utilities'),
                    'dropdown' => __('Native Dropdown', 'istodata-utilities'),
                    'dropdown_links' => __('Dropdown Style (Links)', 'istodata-utilities'),
                ),
                'default' => 'links',
            ));

            $this->add_control('hide_empty', array(
                'label' => __('Hide Empty Terms', 'istodata-utilities'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'istodata-utilities'),
                'label_off' => __('No', 'istodata-utilities'),
                'return_value' => 'yes',
                'default' => 'yes',
            ));

            $this->add_control('orderby', array(
                'label' => __('Order By', 'istodata-utilities'),
                'type' => Controls_Manager::SELECT,
                'options' => array(
                    'name' => __('Name', 'istodata-utilities'),
                    'slug' => __('Slug', 'istodata-utilities'),
                    'count' => __('Count', 'istodata-utilities'),
                    'term_id' => __('Term ID', 'istodata-utilities'),
                ),
                'default' => 'name',
            ));

            $this->add_control('order', array(
                'label' => __('Order', 'istodata-utilities'),
                'type' => Controls_Manager::SELECT,
                'options' => array(
                    'ASC' => __('ASC', 'istodata-utilities'),
                    'DESC' => __('DESC', 'istodata-utilities'),
                ),
                'default' => 'ASC',
            ));

            $this->add_control('show_all_link', array(
                'label' => __('Show "All" Link', 'istodata-utilities'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'istodata-utilities'),
                'label_off' => __('No', 'istodata-utilities'),
                'return_value' => 'yes',
                'default' => '',
            ));

            $this->add_control('all_link_text', array(
                'label' => __('"All" Label', 'istodata-utilities'),
                'type' => Controls_Manager::TEXT,
                'default' => __('All', 'istodata-utilities'),
                'dynamic' => array('active' => true),
                'condition' => array(
                    'show_all_link' => 'yes',
                ),
            ));

            $this->add_control('all_link', array(
                'label' => __('"All" URL', 'istodata-utilities'),
                'type' => Controls_Manager::URL,
                'dynamic' => array('active' => true),
                'default' => array(
                    'url' => home_url('/'),
                ),
                'condition' => array(
                    'show_all_link' => 'yes',
                ),
            ));

            $this->add_control('link_icon', array(
                'label' => __('Default Icon', 'istodata-utilities'),
                'type' => Controls_Manager::ICONS,
                'condition' => array(
                    'display_mode' => 'links',
                ),
            ));

            $this->add_control('link_active_icon', array(
                'label' => __('Active Icon', 'istodata-utilities'),
                'type' => Controls_Manager::ICONS,
                'condition' => array(
                    'display_mode' => 'links',
                ),
            ));

            $this->add_control('dropdown_placeholder', array(
                'label' => __('Dropdown Placeholder', 'istodata-utilities'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Select term', 'istodata-utilities'),
                'dynamic' => array('active' => true),
                'condition' => array(
                    'display_mode' => array('dropdown', 'dropdown_links'),
                    'show_all_link!' => 'yes',
                ),
            ));

            $this->add_control('dropdown_links_label', array(
                'label' => __('Dropdown Trigger Label', 'istodata-utilities'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Select term', 'istodata-utilities'),
                'dynamic' => array('active' => true),
                'condition' => array(
                    'display_mode' => 'dropdown_links',
                ),
            ));

            $this->add_control('dropdown_links_use_current_term', array(
                'label' => __('Use Current Term As Label', 'istodata-utilities'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'istodata-utilities'),
                'label_off' => __('No', 'istodata-utilities'),
                'return_value' => 'yes',
                'default' => 'yes',
                'condition' => array(
                    'display_mode' => 'dropdown_links',
                ),
            ));

            $this->end_controls_section();

            $this->start_controls_section('section_layout', array(
                'label' => __('Layout', 'istodata-utilities'),
                'tab'   => Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'display_mode' => 'links',
                ),
            ));

            $this->add_responsive_control('direction', array(
                'label' => __('Direction', 'istodata-utilities'),
                'type' => Controls_Manager::CHOOSE,
                'options' => array(
                    'row' => array(
                        'title' => __('Horizontal', 'istodata-utilities'),
                        'icon' => 'eicon-ellipsis-h',
                    ),
                    'column' => array(
                        'title' => __('Vertical', 'istodata-utilities'),
                        'icon' => 'eicon-ellipsis-v',
                    ),
                ),
                'default' => 'row',
                'selectors' => array(
                    '{{WRAPPER}} .iu-taxonomy-links' => 'flex-direction: {{VALUE}};',
                ),
                'condition' => array(
                    'display_mode' => 'links',
                ),
            ));

            $this->add_responsive_control('wrap', array(
                'label' => __('Allow Wrap', 'istodata-utilities'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'istodata-utilities'),
                'label_off' => __('No', 'istodata-utilities'),
                'return_value' => 'wrap',
                'default' => 'wrap',
                'selectors_dictionary' => array(
                    'wrap' => 'wrap',
                    '' => 'nowrap',
                ),
                'selectors' => array(
                    '{{WRAPPER}} .iu-taxonomy-links' => 'flex-wrap: {{VALUE}};',
                ),
                'condition' => array(
                    'display_mode' => 'links',
                ),
            ));

            $this->add_responsive_control('alignment', array(
                'label' => __('Alignment', 'istodata-utilities'),
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
                    'left' => 'flex-start; align-items:flex-start; text-align:left',
                    'center' => 'center; align-items:center; text-align:center',
                    'right' => 'flex-end; align-items:flex-end; text-align:right',
                ),
                'selectors' => array(
                    '{{WRAPPER}} .iu-taxonomy-links' => 'justify-content: {{VALUE}};',
                ),
                'condition' => array(
                    'display_mode' => 'links',
                ),
            ));

            $this->add_responsive_control('gap', array(
                'label' => __('Gap', 'istodata-utilities'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => array('px', 'em', 'rem'),
                'range' => array(
                    'px' => array('min' => 0, 'max' => 80),
                    'em' => array('min' => 0, 'max' => 5, 'step' => 0.1),
                    'rem' => array('min' => 0, 'max' => 5, 'step' => 0.1),
                ),
                'default' => array(
                    'size' => 12,
                    'unit' => 'px',
                ),
                'selectors' => array(
                    '{{WRAPPER}} .iu-taxonomy-links' => 'gap: {{SIZE}}{{UNIT}};',
                ),
                'condition' => array(
                    'display_mode' => 'links',
                ),
            ));

            $this->end_controls_section();

            $this->start_controls_section('section_style_links', array(
                'label' => __('Links', 'istodata-utilities'),
                'tab'   => Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'display_mode' => 'links',
                ),
            ));

            $this->add_group_control(Group_Control_Typography::get_type(), array(
                'name' => 'links_typography',
                'selector' => '{{WRAPPER}} .iu-taxonomy-links__link',
            ));

            $this->add_responsive_control('link_padding', array(
                'label' => __('Padding', 'istodata-utilities'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%', 'em', 'rem'),
                'selectors' => array(
                    '{{WRAPPER}} .iu-taxonomy-links__link' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            ));

            $this->add_responsive_control('link_border_radius', array(
                'label' => __('Border Radius', 'istodata-utilities'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%', 'em', 'rem'),
                'selectors' => array(
                    '{{WRAPPER}} .iu-taxonomy-links__link' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            ));

            $this->add_responsive_control('link_icon_size', array(
                'label' => __('Icon Size', 'istodata-utilities'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => array('px', 'em', 'rem'),
                'range' => array(
                    'px' => array('min' => 8, 'max' => 128),
                    'em' => array('min' => 0.4, 'max' => 5, 'step' => 0.05),
                    'rem' => array('min' => 0.4, 'max' => 5, 'step' => 0.05),
                ),
                'default' => array(
                    'size' => 16,
                    'unit' => 'px',
                ),
                'selectors' => array(
                    '{{WRAPPER}} .iu-taxonomy-links' => '--iu-tax-links-icon-size: {{SIZE}}{{UNIT}};',
                ),
            ));

            $this->add_responsive_control('link_icon_gap', array(
                'label' => __('Icon Gap', 'istodata-utilities'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => array('px', 'em', 'rem'),
                'range' => array(
                    'px' => array('min' => 0, 'max' => 64),
                    'em' => array('min' => 0, 'max' => 4, 'step' => 0.05),
                    'rem' => array('min' => 0, 'max' => 4, 'step' => 0.05),
                ),
                'default' => array(
                    'size' => 8,
                    'unit' => 'px',
                ),
                'selectors' => array(
                    '{{WRAPPER}} .iu-taxonomy-links' => '--iu-tax-links-icon-gap: {{SIZE}}{{UNIT}};',
                ),
            ));

            $this->add_control('transition_duration', array(
                'label' => __('Transition Duration (s)', 'istodata-utilities'),
                'type' => Controls_Manager::SLIDER,
                'range' => array(
                    'px' => array(
                        'min' => 0,
                        'max' => 2,
                        'step' => 0.05,
                    ),
                ),
                'default' => array(
                    'size' => 0.2,
                ),
                'selectors' => array(
                    '{{WRAPPER}} .iu-taxonomy-links' => '--iu-tax-links-transition: {{SIZE}}s;',
                ),
            ));

            $this->start_controls_tabs('tabs_link_states');

            $this->start_controls_tab('tab_link_normal', array(
                'label' => __('Normal', 'istodata-utilities'),
            ));

            $this->add_control('link_color', array(
                'label' => __('Text Color', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .iu-taxonomy-links__link' => 'color: {{VALUE}};',
                ),
            ));

            $this->add_group_control(Group_Control_Background::get_type(), array(
                'name' => 'link_background',
                'exclude' => array('image'),
                'selector' => '{{WRAPPER}} .iu-taxonomy-links__link',
            ));

            $this->add_group_control(Group_Control_Border::get_type(), array(
                'name' => 'link_border',
                'selector' => '{{WRAPPER}} .iu-taxonomy-links__link',
            ));

            $this->add_group_control(Group_Control_Box_Shadow::get_type(), array(
                'name' => 'link_box_shadow',
                'selector' => '{{WRAPPER}} .iu-taxonomy-links__link',
            ));

            $this->end_controls_tab();

            $this->start_controls_tab('tab_link_hover', array(
                'label' => __('Hover', 'istodata-utilities'),
            ));

            $this->add_control('link_hover_color', array(
                'label' => __('Text Color', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .iu-taxonomy-links__link:hover, {{WRAPPER}} .iu-taxonomy-links__link:focus-visible' => 'color: {{VALUE}};',
                ),
            ));

            $this->add_group_control(Group_Control_Background::get_type(), array(
                'name' => 'link_background_hover',
                'exclude' => array('image'),
                'selector' => '{{WRAPPER}} .iu-taxonomy-links__link:hover, {{WRAPPER}} .iu-taxonomy-links__link:focus-visible',
            ));

            $this->add_group_control(Group_Control_Border::get_type(), array(
                'name' => 'link_border_hover',
                'selector' => '{{WRAPPER}} .iu-taxonomy-links__link:hover, {{WRAPPER}} .iu-taxonomy-links__link:focus-visible',
            ));

            $this->add_group_control(Group_Control_Box_Shadow::get_type(), array(
                'name' => 'link_box_shadow_hover',
                'selector' => '{{WRAPPER}} .iu-taxonomy-links__link:hover, {{WRAPPER}} .iu-taxonomy-links__link:focus-visible',
            ));

            $this->end_controls_tab();

            $this->start_controls_tab('tab_link_active', array(
                'label' => __('Active', 'istodata-utilities'),
            ));

            $this->add_control('link_active_color', array(
                'label' => __('Text Color', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .iu-taxonomy-links__link.is-current, {{WRAPPER}} .iu-taxonomy-links__link[aria-current="page"]' => 'color: {{VALUE}};',
                ),
            ));

            $this->add_group_control(Group_Control_Background::get_type(), array(
                'name' => 'link_background_active',
                'exclude' => array('image'),
                'selector' => '{{WRAPPER}} .iu-taxonomy-links__link.is-current, {{WRAPPER}} .iu-taxonomy-links__link[aria-current="page"]',
            ));

            $this->add_group_control(Group_Control_Border::get_type(), array(
                'name' => 'link_border_active',
                'selector' => '{{WRAPPER}} .iu-taxonomy-links__link.is-current, {{WRAPPER}} .iu-taxonomy-links__link[aria-current="page"]',
            ));

            $this->add_group_control(Group_Control_Box_Shadow::get_type(), array(
                'name' => 'link_box_shadow_active',
                'selector' => '{{WRAPPER}} .iu-taxonomy-links__link.is-current, {{WRAPPER}} .iu-taxonomy-links__link[aria-current="page"]',
            ));

            $this->end_controls_tab();

            $this->end_controls_tabs();
            $this->end_controls_section();

            $this->start_controls_section('section_style_dropdown', array(
                'label' => __('Native Dropdown', 'istodata-utilities'),
                'tab'   => Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'display_mode' => 'dropdown',
                ),
            ));

            $this->add_group_control(Group_Control_Typography::get_type(), array(
                'name' => 'dropdown_typography',
                'selector' => '{{WRAPPER}} .iu-taxonomy-links__select',
            ));

            $this->add_responsive_control('dropdown_width', array(
                'label' => __('Width', 'istodata-utilities'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => array('%', 'px', 'rem'),
                'range' => array(
                    '%' => array('min' => 10, 'max' => 100),
                    'px' => array('min' => 120, 'max' => 1200),
                    'rem' => array('min' => 8, 'max' => 80, 'step' => 0.5),
                ),
                'default' => array(
                    'size' => 100,
                    'unit' => '%',
                ),
                'selectors' => array(
                    '{{WRAPPER}} .iu-taxonomy-links--dropdown' => 'width: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .iu-taxonomy-links__select' => 'width: 100%;',
                ),
            ));

            $this->add_responsive_control('dropdown_padding', array(
                'label' => __('Padding', 'istodata-utilities'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%', 'em', 'rem'),
                'selectors' => array(
                    '{{WRAPPER}} .iu-taxonomy-links__select' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            ));

            $this->add_responsive_control('dropdown_border_radius', array(
                'label' => __('Border Radius', 'istodata-utilities'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%', 'em', 'rem'),
                'selectors' => array(
                    '{{WRAPPER}} .iu-taxonomy-links__select' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            ));

            $this->start_controls_tabs('tabs_dropdown_states');

            $this->start_controls_tab('tab_dropdown_normal', array(
                'label' => __('Normal', 'istodata-utilities'),
            ));

            $this->add_control('dropdown_color', array(
                'label' => __('Text Color', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .iu-taxonomy-links__select' => 'color: {{VALUE}};',
                ),
            ));

            $this->add_group_control(Group_Control_Background::get_type(), array(
                'name' => 'dropdown_background',
                'exclude' => array('image'),
                'selector' => '{{WRAPPER}} .iu-taxonomy-links__select',
            ));

            $this->add_group_control(Group_Control_Border::get_type(), array(
                'name' => 'dropdown_border',
                'selector' => '{{WRAPPER}} .iu-taxonomy-links__select',
            ));

            $this->add_group_control(Group_Control_Box_Shadow::get_type(), array(
                'name' => 'dropdown_box_shadow',
                'selector' => '{{WRAPPER}} .iu-taxonomy-links__select',
            ));

            $this->end_controls_tab();

            $this->start_controls_tab('tab_dropdown_hover', array(
                'label' => __('Hover / Focus', 'istodata-utilities'),
            ));

            $this->add_control('dropdown_color_hover', array(
                'label' => __('Text Color', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .iu-taxonomy-links__select:hover, {{WRAPPER}} .iu-taxonomy-links__select:focus, {{WRAPPER}} .iu-taxonomy-links__select:focus-visible' => 'color: {{VALUE}};',
                ),
            ));

            $this->add_group_control(Group_Control_Background::get_type(), array(
                'name' => 'dropdown_background_hover',
                'exclude' => array('image'),
                'selector' => '{{WRAPPER}} .iu-taxonomy-links__select:hover, {{WRAPPER}} .iu-taxonomy-links__select:focus, {{WRAPPER}} .iu-taxonomy-links__select:focus-visible',
            ));

            $this->add_group_control(Group_Control_Border::get_type(), array(
                'name' => 'dropdown_border_hover',
                'selector' => '{{WRAPPER}} .iu-taxonomy-links__select:hover, {{WRAPPER}} .iu-taxonomy-links__select:focus, {{WRAPPER}} .iu-taxonomy-links__select:focus-visible',
            ));

            $this->add_group_control(Group_Control_Box_Shadow::get_type(), array(
                'name' => 'dropdown_box_shadow_hover',
                'selector' => '{{WRAPPER}} .iu-taxonomy-links__select:hover, {{WRAPPER}} .iu-taxonomy-links__select:focus, {{WRAPPER}} .iu-taxonomy-links__select:focus-visible',
            ));

            $this->end_controls_tab();

            $this->end_controls_tabs();
            $this->end_controls_section();

            $this->start_controls_section('section_style_dropdown_links_trigger', array(
                'label' => __('Dropdown Trigger', 'istodata-utilities'),
                'tab'   => Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'display_mode' => 'dropdown_links',
                ),
            ));

            $this->add_group_control(Group_Control_Typography::get_type(), array(
                'name' => 'dropdown_links_trigger_typography',
                'selector' => '{{WRAPPER}} .iu-taxonomy-links__dropdown-trigger',
            ));

            $this->add_responsive_control('dropdown_links_width', array(
                'label' => __('Width', 'istodata-utilities'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => array('%', 'px', 'rem'),
                'range' => array(
                    '%' => array('min' => 10, 'max' => 100),
                    'px' => array('min' => 120, 'max' => 1200),
                    'rem' => array('min' => 8, 'max' => 80, 'step' => 0.5),
                ),
                'default' => array(
                    'size' => 100,
                    'unit' => '%',
                ),
                'selectors' => array(
                    '{{WRAPPER}} .iu-taxonomy-links--dropdown-links' => 'width: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .iu-taxonomy-links__dropdown-trigger' => 'width: 100%;',
                ),
            ));

            $this->add_responsive_control('dropdown_links_trigger_padding', array(
                'label' => __('Padding', 'istodata-utilities'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%', 'em', 'rem'),
                'selectors' => array(
                    '{{WRAPPER}} .iu-taxonomy-links__dropdown-trigger' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            ));

            $this->add_responsive_control('dropdown_links_trigger_icon_size', array(
                'label' => __('Icon Size', 'istodata-utilities'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => array('px', 'em', 'rem'),
                'range' => array(
                    'px' => array('min' => 6, 'max' => 40),
                    'em' => array('min' => 0.4, 'max' => 3, 'step' => 0.05),
                    'rem' => array('min' => 0.4, 'max' => 3, 'step' => 0.05),
                ),
                'default' => array(
                    'size' => 10,
                    'unit' => 'px',
                ),
                'selectors' => array(
                    '{{WRAPPER}} .iu-taxonomy-links--dropdown-links' => '--iu-tax-dropdown-icon-size: {{SIZE}}{{UNIT}};',
                ),
            ));

            $this->add_responsive_control('dropdown_links_trigger_border_radius', array(
                'label' => __('Border Radius', 'istodata-utilities'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%', 'em', 'rem'),
                'selectors' => array(
                    '{{WRAPPER}} .iu-taxonomy-links__dropdown-trigger' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            ));

            $this->start_controls_tabs('tabs_dropdown_links_trigger_states');

            $this->start_controls_tab('tab_dropdown_links_trigger_normal', array(
                'label' => __('Normal', 'istodata-utilities'),
            ));

            $this->add_control('dropdown_links_trigger_color', array(
                'label' => __('Text Color', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .iu-taxonomy-links__dropdown-trigger' => 'color: {{VALUE}};',
                ),
            ));

            $this->add_group_control(Group_Control_Background::get_type(), array(
                'name' => 'dropdown_links_trigger_background',
                'exclude' => array('image'),
                'selector' => '{{WRAPPER}} .iu-taxonomy-links__dropdown-trigger',
            ));

            $this->add_group_control(Group_Control_Border::get_type(), array(
                'name' => 'dropdown_links_trigger_border',
                'selector' => '{{WRAPPER}} .iu-taxonomy-links__dropdown-trigger',
            ));

            $this->add_group_control(Group_Control_Box_Shadow::get_type(), array(
                'name' => 'dropdown_links_trigger_shadow',
                'selector' => '{{WRAPPER}} .iu-taxonomy-links__dropdown-trigger',
            ));

            $this->end_controls_tab();

            $this->start_controls_tab('tab_dropdown_links_trigger_hover', array(
                'label' => __('Hover / Open', 'istodata-utilities'),
            ));

            $this->add_control('dropdown_links_trigger_color_hover', array(
                'label' => __('Text Color', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .iu-taxonomy-links__dropdown-trigger:hover, {{WRAPPER}} .iu-taxonomy-links__dropdown-trigger:focus-visible, {{WRAPPER}} .iu-taxonomy-links__dropdown-trigger[aria-expanded="true"]' => 'color: {{VALUE}};',
                ),
            ));

            $this->add_group_control(Group_Control_Background::get_type(), array(
                'name' => 'dropdown_links_trigger_background_hover',
                'exclude' => array('image'),
                'selector' => '{{WRAPPER}} .iu-taxonomy-links__dropdown-trigger:hover, {{WRAPPER}} .iu-taxonomy-links__dropdown-trigger:focus-visible, {{WRAPPER}} .iu-taxonomy-links__dropdown-trigger[aria-expanded="true"]',
            ));

            $this->add_group_control(Group_Control_Border::get_type(), array(
                'name' => 'dropdown_links_trigger_border_hover',
                'selector' => '{{WRAPPER}} .iu-taxonomy-links__dropdown-trigger:hover, {{WRAPPER}} .iu-taxonomy-links__dropdown-trigger:focus-visible, {{WRAPPER}} .iu-taxonomy-links__dropdown-trigger[aria-expanded="true"]',
            ));

            $this->add_group_control(Group_Control_Box_Shadow::get_type(), array(
                'name' => 'dropdown_links_trigger_shadow_hover',
                'selector' => '{{WRAPPER}} .iu-taxonomy-links__dropdown-trigger:hover, {{WRAPPER}} .iu-taxonomy-links__dropdown-trigger:focus-visible, {{WRAPPER}} .iu-taxonomy-links__dropdown-trigger[aria-expanded="true"]',
            ));

            $this->end_controls_tab();
            $this->end_controls_tabs();
            $this->end_controls_section();

            $this->start_controls_section('section_style_dropdown_links_panel', array(
                'label' => __('Dropdown Panel', 'istodata-utilities'),
                'tab'   => Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'display_mode' => 'dropdown_links',
                ),
            ));

            $this->add_responsive_control('dropdown_links_panel_offset', array(
                'label' => __('Top Offset', 'istodata-utilities'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => array('px', 'em', 'rem'),
                'range' => array(
                    'px' => array('min' => 0, 'max' => 60),
                    'em' => array('min' => 0, 'max' => 5, 'step' => 0.1),
                    'rem' => array('min' => 0, 'max' => 5, 'step' => 0.1),
                ),
                'default' => array(
                    'size' => 8,
                    'unit' => 'px',
                ),
                'selectors' => array(
                    '{{WRAPPER}} .iu-taxonomy-links--dropdown-links' => '--iu-tax-dropdown-offset: {{SIZE}}{{UNIT}};',
                ),
            ));

            $this->add_responsive_control('dropdown_links_panel_padding', array(
                'label' => __('Padding', 'istodata-utilities'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%', 'em', 'rem'),
                'selectors' => array(
                    '{{WRAPPER}} .iu-taxonomy-links__dropdown-panel' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            ));

            $this->add_responsive_control('dropdown_links_panel_border_radius', array(
                'label' => __('Border Radius', 'istodata-utilities'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%', 'em', 'rem'),
                'selectors' => array(
                    '{{WRAPPER}} .iu-taxonomy-links__dropdown-panel' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            ));

            $this->add_group_control(Group_Control_Background::get_type(), array(
                'name' => 'dropdown_links_panel_background',
                'exclude' => array('image'),
                'selector' => '{{WRAPPER}} .iu-taxonomy-links__dropdown-panel',
            ));

            $this->add_group_control(Group_Control_Border::get_type(), array(
                'name' => 'dropdown_links_panel_border',
                'selector' => '{{WRAPPER}} .iu-taxonomy-links__dropdown-panel',
            ));

            $this->add_group_control(Group_Control_Box_Shadow::get_type(), array(
                'name' => 'dropdown_links_panel_shadow',
                'selector' => '{{WRAPPER}} .iu-taxonomy-links__dropdown-panel',
            ));

            $this->end_controls_section();

            $this->start_controls_section('section_style_dropdown_links_items', array(
                'label' => __('Dropdown Items', 'istodata-utilities'),
                'tab'   => Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'display_mode' => 'dropdown_links',
                ),
            ));

            $this->add_group_control(Group_Control_Typography::get_type(), array(
                'name' => 'dropdown_links_items_typography',
                'selector' => '{{WRAPPER}} .iu-taxonomy-links__dropdown-link',
            ));

            $this->add_responsive_control('dropdown_links_items_gap', array(
                'label' => __('Items Gap', 'istodata-utilities'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => array('px', 'em', 'rem'),
                'range' => array(
                    'px' => array('min' => 0, 'max' => 40),
                    'em' => array('min' => 0, 'max' => 3, 'step' => 0.1),
                    'rem' => array('min' => 0, 'max' => 3, 'step' => 0.1),
                ),
                'default' => array(
                    'size' => 4,
                    'unit' => 'px',
                ),
                'selectors' => array(
                    '{{WRAPPER}} .iu-taxonomy-links__dropdown-list' => 'gap: {{SIZE}}{{UNIT}};',
                ),
            ));

            $this->add_responsive_control('dropdown_links_item_padding', array(
                'label' => __('Padding', 'istodata-utilities'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%', 'em', 'rem'),
                'selectors' => array(
                    '{{WRAPPER}} .iu-taxonomy-links__dropdown-link' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            ));

            $this->add_responsive_control('dropdown_links_item_border_radius', array(
                'label' => __('Border Radius', 'istodata-utilities'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%', 'em', 'rem'),
                'selectors' => array(
                    '{{WRAPPER}} .iu-taxonomy-links__dropdown-link' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            ));

            $this->start_controls_tabs('tabs_dropdown_links_items_states');

            $this->start_controls_tab('tab_dropdown_links_items_normal', array(
                'label' => __('Normal', 'istodata-utilities'),
            ));

            $this->add_control('dropdown_links_item_color', array(
                'label' => __('Text Color', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .iu-taxonomy-links__dropdown-link' => 'color: {{VALUE}};',
                ),
            ));

            $this->add_group_control(Group_Control_Background::get_type(), array(
                'name' => 'dropdown_links_item_background',
                'exclude' => array('image'),
                'selector' => '{{WRAPPER}} .iu-taxonomy-links__dropdown-link',
            ));

            $this->add_group_control(Group_Control_Border::get_type(), array(
                'name' => 'dropdown_links_item_border',
                'selector' => '{{WRAPPER}} .iu-taxonomy-links__dropdown-link',
            ));

            $this->add_group_control(Group_Control_Box_Shadow::get_type(), array(
                'name' => 'dropdown_links_item_shadow',
                'selector' => '{{WRAPPER}} .iu-taxonomy-links__dropdown-link',
            ));

            $this->end_controls_tab();

            $this->start_controls_tab('tab_dropdown_links_items_hover', array(
                'label' => __('Hover / Current', 'istodata-utilities'),
            ));

            $this->add_control('dropdown_links_item_color_hover', array(
                'label' => __('Text Color', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .iu-taxonomy-links__dropdown-link:hover, {{WRAPPER}} .iu-taxonomy-links__dropdown-link:focus-visible, {{WRAPPER}} .iu-taxonomy-links__dropdown-link.is-current' => 'color: {{VALUE}};',
                ),
            ));

            $this->add_group_control(Group_Control_Background::get_type(), array(
                'name' => 'dropdown_links_item_background_hover',
                'exclude' => array('image'),
                'selector' => '{{WRAPPER}} .iu-taxonomy-links__dropdown-link:hover, {{WRAPPER}} .iu-taxonomy-links__dropdown-link:focus-visible, {{WRAPPER}} .iu-taxonomy-links__dropdown-link.is-current',
            ));

            $this->add_group_control(Group_Control_Border::get_type(), array(
                'name' => 'dropdown_links_item_border_hover',
                'selector' => '{{WRAPPER}} .iu-taxonomy-links__dropdown-link:hover, {{WRAPPER}} .iu-taxonomy-links__dropdown-link:focus-visible, {{WRAPPER}} .iu-taxonomy-links__dropdown-link.is-current',
            ));

            $this->add_group_control(Group_Control_Box_Shadow::get_type(), array(
                'name' => 'dropdown_links_item_shadow_hover',
                'selector' => '{{WRAPPER}} .iu-taxonomy-links__dropdown-link:hover, {{WRAPPER}} .iu-taxonomy-links__dropdown-link:focus-visible, {{WRAPPER}} .iu-taxonomy-links__dropdown-link.is-current',
            ));

            $this->end_controls_tab();
            $this->end_controls_tabs();
            $this->end_controls_section();
        }

        protected function render() {
            $settings = $this->get_settings_for_display();
            $taxonomy = isset($settings['taxonomy']) ? sanitize_key($settings['taxonomy']) : '';

            if (empty($taxonomy) || !taxonomy_exists($taxonomy)) {
                $this->render_editor_notice(__('Select a valid taxonomy to display links.', 'istodata-utilities'));
                return;
            }

            $terms = get_terms(array(
                'taxonomy' => $taxonomy,
                'hide_empty' => (!empty($settings['hide_empty']) && $settings['hide_empty'] === 'yes'),
                'orderby' => !empty($settings['orderby']) ? $settings['orderby'] : 'name',
                'order' => (!empty($settings['order']) && strtoupper($settings['order']) === 'DESC') ? 'DESC' : 'ASC',
            ));

            if (is_wp_error($terms)) {
                $this->render_editor_notice(__('Terms could not be loaded for the selected taxonomy.', 'istodata-utilities'));
                return;
            }

            if (empty($terms)) {
                $this->render_editor_notice(__('No terms were found for the selected taxonomy.', 'istodata-utilities'));
                return;
            }

            $taxonomy_object = get_taxonomy($taxonomy);
            $nav_label = $taxonomy_object && !empty($taxonomy_object->labels->name)
                ? sprintf(__('Browse %s', 'istodata-utilities'), $taxonomy_object->labels->name)
                : __('Browse taxonomy terms', 'istodata-utilities');
            $display_mode = !empty($settings['display_mode']) ? $settings['display_mode'] : 'links';
            $current_term_id = $this->get_current_term_id($taxonomy);

            if ($display_mode === 'dropdown') {
                $this->render_dropdown($settings, $terms, $taxonomy, $nav_label);
                return;
            }

            if ($display_mode === 'dropdown_links') {
                $this->render_dropdown_links($settings, $terms, $taxonomy, $taxonomy_object, $nav_label);
                return;
            }

            echo '<div class="iu-taxonomy-links" role="navigation" aria-label="' . esc_attr($nav_label) . '">';

            if (!empty($settings['show_all_link']) && $settings['show_all_link'] === 'yes') {
                $all_is_current = $this->is_all_link_current($settings, $current_term_id);
                $all_text = !empty($settings['all_link_text']) ? $settings['all_link_text'] : __('All', 'istodata-utilities');
                $all_link = !empty($settings['all_link']['url']) ? $settings['all_link'] : array('url' => home_url('/'));

                $this->add_render_attribute('all_link', 'class', 'iu-taxonomy-links__link iu-taxonomy-links__link--all');
                if ($all_is_current) {
                    $this->add_render_attribute('all_link', 'class', 'is-current');
                    $this->add_render_attribute('all_link', 'aria-current', 'page');
                }
                $this->add_link_attributes('all_link', $all_link);

                echo '<a ' . $this->get_render_attribute_string('all_link') . '>';
                $this->render_link_label($settings, $all_text, $all_is_current, 'all_link');
                echo '</a>';
            }

            foreach ($terms as $index => $term) {
                $term_url = get_term_link($term);
                if (is_wp_error($term_url)) {
                    continue;
                }

                $attr_key = 'term_link_' . $index;
                $this->add_render_attribute($attr_key, 'class', 'iu-taxonomy-links__link iu-taxonomy-links__link--term');
                if ($current_term_id === (int) $term->term_id) {
                    $this->add_render_attribute($attr_key, 'class', 'is-current');
                    $this->add_render_attribute($attr_key, 'aria-current', 'page');
                }
                $this->add_render_attribute($attr_key, 'href', esc_url($term_url));
                $this->add_render_attribute($attr_key, 'data-taxonomy', $taxonomy);
                $this->add_render_attribute($attr_key, 'data-term-id', (string) absint($term->term_id));

                echo '<a ' . $this->get_render_attribute_string($attr_key) . '>';
                $this->render_link_label($settings, $term->name, $current_term_id === (int) $term->term_id, 'term_link_' . $term->term_id);
                echo '</a>';
            }

            echo '</div>';
        }

        private function render_dropdown($settings, $terms, $taxonomy, $nav_label) {
            $select_id = 'iu-taxonomy-links-select-' . $this->get_id();
            $current_term_id = $this->get_current_term_id($taxonomy);
            $has_selected_term = false;
            $all_is_current = $this->is_all_link_current($settings, $current_term_id);
            $all_text = !empty($settings['all_link_text']) ? $settings['all_link_text'] : __('All', 'istodata-utilities');
            $all_url = (!empty($settings['all_link']['url']) && filter_var($settings['all_link']['url'], FILTER_VALIDATE_URL))
                ? $settings['all_link']['url']
                : home_url('/');
            $show_all_link = !empty($settings['show_all_link']) && $settings['show_all_link'] === 'yes';
            $placeholder = !empty($settings['dropdown_placeholder']) ? $settings['dropdown_placeholder'] : __('Select term', 'istodata-utilities');

            foreach ($terms as $term) {
                if ($current_term_id === (int) $term->term_id) {
                    $has_selected_term = true;
                    break;
                }
            }

            echo '<div class="iu-taxonomy-links iu-taxonomy-links--dropdown" role="navigation" aria-label="' . esc_attr($nav_label) . '">';
            echo '<label class="screen-reader-text" for="' . esc_attr($select_id) . '">' . esc_html($nav_label) . '</label>';
            echo '<select id="' . esc_attr($select_id) . '" class="iu-taxonomy-links__select">';

            if ($show_all_link) {
                if (!$all_is_current && !$has_selected_term) {
                    echo '<option value="" selected="selected" disabled="disabled">' . esc_html($placeholder) . '</option>';
                }
                $selected = $all_is_current ? ' selected="selected"' : '';
                echo '<option value="' . esc_url($all_url) . '"' . $selected . '>' . esc_html($all_text) . '</option>';
            } else {
                $selected = !$has_selected_term ? ' selected="selected"' : '';
                echo '<option value=""' . $selected . ' disabled="disabled">' . esc_html($placeholder) . '</option>';
            }

            foreach ($terms as $term) {
                $term_url = get_term_link($term);
                if (is_wp_error($term_url)) {
                    continue;
                }

                $selected = $current_term_id === (int) $term->term_id ? ' selected="selected"' : '';
                echo '<option value="' . esc_url($term_url) . '"' . $selected . '>' . esc_html($term->name) . '</option>';
            }

            echo '</select>';
            echo '</div>';
            ?>
            <script>
                (function() {
                    var select = document.getElementById('<?php echo esc_js($select_id); ?>');
                    if (!select || select.dataset.iuBound === '1') {
                        return;
                    }
                    select.dataset.iuBound = '1';
                    select.addEventListener('change', function() {
                        if (this.value) {
                            window.location.href = this.value;
                        }
                    });
                })();
            </script>
            <?php
        }

        private function render_dropdown_links($settings, $terms, $taxonomy, $taxonomy_object, $nav_label) {
            $instance_id = 'iu-taxonomy-links-dropdown-' . $this->get_id();
            $button_id = $instance_id . '-button';
            $panel_id = $instance_id . '-panel';
            $current_term_id = $this->get_current_term_id($taxonomy);
            $all_is_current = $this->is_all_link_current($settings, $current_term_id);
            $trigger_label = $this->get_dropdown_links_trigger_label($settings, $taxonomy_object, $taxonomy, $current_term_id, $all_is_current);
            $show_all_link = !empty($settings['show_all_link']) && $settings['show_all_link'] === 'yes';
            $all_text = !empty($settings['all_link_text']) ? $settings['all_link_text'] : __('All', 'istodata-utilities');
            $all_link = !empty($settings['all_link']['url']) ? $settings['all_link'] : array('url' => home_url('/'));

            echo '<div id="' . esc_attr($instance_id) . '" class="iu-taxonomy-links iu-taxonomy-links--dropdown-links" role="navigation" aria-label="' . esc_attr($nav_label) . '">';
            echo '<button id="' . esc_attr($button_id) . '" class="iu-taxonomy-links__dropdown-trigger" type="button" aria-expanded="false" aria-controls="' . esc_attr($panel_id) . '">';
            echo '<span class="iu-taxonomy-links__dropdown-trigger-label">' . esc_html($trigger_label) . '</span>';
            echo '<span class="iu-taxonomy-links__dropdown-trigger-icon" aria-hidden="true"></span>';
            echo '</button>';
            echo '<div id="' . esc_attr($panel_id) . '" class="iu-taxonomy-links__dropdown-panel" hidden="hidden">';
            echo '<div class="iu-taxonomy-links__dropdown-list">';

            if ($show_all_link) {
                $this->add_render_attribute('dropdown_all_link', 'class', 'iu-taxonomy-links__dropdown-link');
                if ($all_is_current) {
                    $this->add_render_attribute('dropdown_all_link', 'class', 'is-current');
                    $this->add_render_attribute('dropdown_all_link', 'aria-current', 'page');
                }
                $this->add_link_attributes('dropdown_all_link', $all_link);
                echo '<a ' . $this->get_render_attribute_string('dropdown_all_link') . '>' . esc_html($all_text) . '</a>';
            }

            foreach ($terms as $index => $term) {
                $term_url = get_term_link($term);
                if (is_wp_error($term_url)) {
                    continue;
                }

                $attr_key = 'dropdown_term_link_' . $index;
                $this->add_render_attribute($attr_key, 'class', 'iu-taxonomy-links__dropdown-link');
                if ($current_term_id === (int) $term->term_id) {
                    $this->add_render_attribute($attr_key, 'class', 'is-current');
                    $this->add_render_attribute($attr_key, 'aria-current', 'page');
                }
                $this->add_render_attribute($attr_key, 'href', esc_url($term_url));
                $this->add_render_attribute($attr_key, 'data-taxonomy', $taxonomy);
                $this->add_render_attribute($attr_key, 'data-term-id', (string) absint($term->term_id));

                echo '<a ' . $this->get_render_attribute_string($attr_key) . '>' . esc_html($term->name) . '</a>';
            }

            echo '</div>';
            echo '</div>';
            echo '</div>';
            ?>
            <script>
                (function() {
                    var root = document.getElementById('<?php echo esc_js($instance_id); ?>');
                    if (!root || root.dataset.iuDropdownLinksBound === '1') {
                        return;
                    }
                    root.dataset.iuDropdownLinksBound = '1';

                    var button = document.getElementById('<?php echo esc_js($button_id); ?>');
                    var panel = document.getElementById('<?php echo esc_js($panel_id); ?>');

                    if (!button || !panel) {
                        return;
                    }

                    var closePanel = function() {
                        button.setAttribute('aria-expanded', 'false');
                        panel.hidden = true;
                    };

                    var openPanel = function() {
                        button.setAttribute('aria-expanded', 'true');
                        panel.hidden = false;
                    };

                    button.addEventListener('click', function() {
                        if (button.getAttribute('aria-expanded') === 'true') {
                            closePanel();
                        } else {
                            openPanel();
                        }
                    });

                    document.addEventListener('click', function(event) {
                        if (!root.contains(event.target)) {
                            closePanel();
                        }
                    });

                    document.addEventListener('keydown', function(event) {
                        if (event.key === 'Escape') {
                            closePanel();
                        }
                    });
                })();
            </script>
            <?php
        }

        private function get_taxonomy_options() {
            $options = array();
            $taxonomies = get_taxonomies(array(
                'public' => true,
            ), 'objects');
            $excluded = array('nav_menu', 'link_category', 'post_format');

            foreach ($taxonomies as $taxonomy => $taxonomy_object) {
                if (in_array($taxonomy, $excluded, true)) {
                    continue;
                }

                if (isset($taxonomy_object->show_ui) && !$taxonomy_object->show_ui) {
                    continue;
                }

                $label = !empty($taxonomy_object->labels->singular_name)
                    ? $taxonomy_object->labels->singular_name
                    : $taxonomy_object->label;

                $options[$taxonomy] = sprintf('%s (%s)', $label, $taxonomy);
            }

            asort($options);

            return $options;
        }

        private function render_editor_notice($message) {
            if ($this->is_editor_mode()) {
                echo '<div style="padding:12px;background:#fff3cd;border:1px solid #ffe69c;color:#664d03;">' . esc_html($message) . '</div>';
            }
        }

        private function render_link_label($settings, $text, $is_current, $scope) {
            $icon_key = '';

            if ($is_current && !empty($settings['link_active_icon']['value'])) {
                $icon_key = 'link_active_icon';
            } elseif (!empty($settings['link_icon']['value'])) {
                $icon_key = 'link_icon';
            }

            if ($icon_key !== '') {
                $this->render_link_icon($settings[$icon_key], $settings, $scope);
            }

            echo '<span class="iu-taxonomy-links__link-text">' . esc_html($text) . '</span>';
        }

        private function render_link_icon($icon, $settings, $scope) {
            if (empty($icon['value'])) {
                return;
            }

            $icon_size = $this->get_control_css_size($settings, 'link_icon_size', '16px');
            $icon_gap = $this->get_control_css_size($settings, 'link_icon_gap', '8px');
            $wrapper_style = sprintf(
                '--iu-tax-links-icon-size:%1$s;--iu-tax-links-icon-gap:%2$s;width:%1$s;height:%1$s;margin-right:%2$s;font-size:%1$s;',
                esc_attr($icon_size),
                esc_attr($icon_gap)
            );

            ob_start();
            Icons_Manager::render_icon($icon, array('aria-hidden' => 'true'));
            $icon_html = $this->iu_svg_normalize(ob_get_clean(), $scope);
            $icon_html = $this->iu_icon_set_dimensions($icon_html, $icon_size);

            if (is_string($icon_html) && $icon_html !== '') {
                echo '<span class="iu-taxonomy-links__link-icon" style="' . $wrapper_style . '">' . $icon_html . '</span>';
            }
        }

        private function get_current_term_id($taxonomy) {
            $queried_object = get_queried_object();

            if ($queried_object instanceof \WP_Term && isset($queried_object->taxonomy) && $queried_object->taxonomy === $taxonomy) {
                return (int) $queried_object->term_id;
            }

            return 0;
        }

        private function get_dropdown_links_trigger_label($settings, $taxonomy_object, $taxonomy, $current_term_id, $all_is_current = false) {
            if ($all_is_current && !empty($settings['show_all_link']) && $settings['show_all_link'] === 'yes') {
                return !empty($settings['all_link_text']) ? $settings['all_link_text'] : __('All', 'istodata-utilities');
            }

            if (!empty($settings['dropdown_links_use_current_term']) && $settings['dropdown_links_use_current_term'] === 'yes' && $current_term_id > 0) {
                $current_term = get_term($current_term_id, $taxonomy);
                if ($current_term && !is_wp_error($current_term) && !empty($current_term->name)) {
                    return $current_term->name;
                }
            }

            if (!empty($settings['dropdown_links_label'])) {
                return $settings['dropdown_links_label'];
            }

            if ($taxonomy_object && !empty($taxonomy_object->labels->singular_name)) {
                return $taxonomy_object->labels->singular_name;
            }

            return __('Select term', 'istodata-utilities');
        }

        private function is_all_link_current($settings, $current_term_id) {
            if ($current_term_id > 0) {
                return false;
            }

            $all_url = !empty($settings['all_link']['url']) ? $settings['all_link']['url'] : home_url('/');
            $current_url = $this->get_current_request_url();

            if ($this->normalize_url($all_url) === $this->normalize_url($current_url)) {
                return true;
            }

            if (is_home()) {
                $posts_page_id = (int) get_option('page_for_posts');
                if ($posts_page_id > 0 && $this->normalize_url($all_url) === $this->normalize_url(get_permalink($posts_page_id))) {
                    return true;
                }

                if ($posts_page_id === 0 && $this->normalize_url($all_url) === $this->normalize_url(home_url('/'))) {
                    return true;
                }
            }

            return false;
        }

        private function get_current_request_url() {
            global $wp;

            if (isset($wp) && isset($wp->request)) {
                $path = $wp->request;
                $query = isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== '' ? '?' . wp_unslash($_SERVER['QUERY_STRING']) : '';

                return home_url('/' . ltrim($path, '/')) . $query;
            }

            $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '/';

            return home_url($request_uri);
        }

        private function normalize_url($url) {
            $parts = wp_parse_url($url);
            if (!is_array($parts)) {
                return '';
            }

            $host = isset($parts['host']) ? strtolower($parts['host']) : '';
            $path = isset($parts['path']) ? untrailingslashit($parts['path']) : '';
            $path = $path === '' ? '/' : $path;
            $query = '';

            if (!empty($parts['query'])) {
                parse_str($parts['query'], $query_args);
                ksort($query_args);
                $query = '?' . http_build_query($query_args);
            }

            return $host . $path . $query;
        }

        private function get_control_css_size($settings, $key, $fallback) {
            if (empty($settings[$key]) || !is_array($settings[$key])) {
                return $fallback;
            }

            $size = isset($settings[$key]['size']) ? $settings[$key]['size'] : null;
            $unit = isset($settings[$key]['unit']) ? $settings[$key]['unit'] : 'px';

            if ($size === null || $size === '') {
                return $fallback;
            }

            return $size . $unit;
        }

        private function iu_icon_set_dimensions($icon_html, $icon_size) {
            if (!is_string($icon_html) || !preg_match('/<(?:svg|i)\b/i', $icon_html)) {
                return $icon_html;
            }

            $icon_size = esc_attr($icon_size);

            return preg_replace_callback('/<(svg|i)\b([^>]*)>/i', function($match) use ($icon_size) {
                $attributes = preg_replace('/\s(?:width|height)\s*=\s*(["\']).*?\1/i', '', $match[2]);
                $inline_style = 'display:block;width:' . $icon_size . ';height:' . $icon_size . ';font-size:' . $icon_size . ';line-height:1;color:currentColor;';

                if (preg_match('/\sstyle\s*=\s*(["\'])(.*?)\1/i', $attributes, $style_match)) {
                    $existing_style = preg_replace('/(?:^|;)\s*(?:display|width|height|font-size|line-height|color)\s*:[^;]*;?/i', ';', $style_match[2]);
                    $existing_style = trim(preg_replace('/;{2,}/', ';', $existing_style), '; ');
                    $replacement = ' style="' . esc_attr(($existing_style === '' ? '' : $existing_style . ';') . $inline_style) . '"';
                    $attributes = preg_replace('/\sstyle\s*=\s*(["\']).*?\1/i', $replacement, $attributes, 1);
                } else {
                    $attributes .= ' style="' . $inline_style . '"';
                }

                return '<' . $match[1] . $attributes . '>';
            }, $icon_html, 1);
        }

        private function iu_svg_normalize($svg_html, $scope = 'icon') {
            if (!is_string($svg_html) || stripos($svg_html, '<svg') === false) {
                return $svg_html;
            }

            $scope = preg_replace('/[^A-Za-z0-9_-]/', '-', (string) $scope);
            $element_id = preg_replace('/[^A-Za-z0-9_-]/', '-', (string) $this->get_id());
            $id_prefix = 'iu-taxonomy-links-' . $element_id . '-' . $scope . '-';
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

            return preg_replace_callback('/<svg\b([^>]*)>/i', function($match) {
                $attrs = $match[1];
                $attrs = preg_replace('/\swidth\s*=\s*"[^"]*"/i', '', $attrs);
                $attrs = preg_replace('/\sheight\s*=\s*"[^"]*"/i', '', $attrs);
                $attrs = preg_replace_callback('/\sstyle\s*=\s*"([^"]*)"/i', function($style_match) {
                    $style = preg_replace('/(?:^|;|\s)(width|height)\s*:\s*[^;]+;?/i', ';', $style_match[1]);
                    $style = preg_replace('/;{2,}/', ';', trim($style));
                    $style = trim($style, '; ');
                    return $style === '' ? '' : ' style="' . esc_attr($style) . '"';
                }, $attrs);
                return '<svg' . $attrs . ' width="100%" height="100%">';
            }, $svg_html, 1);
        }

        private function is_editor_mode() {
            return class_exists('Elementor\\Plugin')
                && \Elementor\Plugin::$instance
                && \Elementor\Plugin::$instance->editor
                && \Elementor\Plugin::$instance->editor->is_edit_mode();
        }
    }
}
