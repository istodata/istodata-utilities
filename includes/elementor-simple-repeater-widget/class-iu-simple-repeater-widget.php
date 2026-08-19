<?php
if (!defined('ABSPATH')) { exit; }

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

if (!class_exists('IU_Simple_Repeater_Widget')) {
    class IU_Simple_Repeater_Widget extends Widget_Base {
        public function get_name() {
            return 'iu_simple_repeater';
        }

        public function get_title() {
            return __('Simple Repeater', 'istodata-utilities');
        }

        public function get_icon() {
            return 'eicon-gallery-grid';
        }

        public function get_categories() {
            return array('istodata-kit');
        }

        public function get_style_depends() {
            if (!wp_style_is('iu-simple-repeater', 'registered')) {
                wp_register_style(
                    'iu-simple-repeater',
                    IU_PLUGIN_URL . 'assets/css/simple-repeater.css',
                    array(),
                    defined('IU_PLUGIN_VERSION') ? IU_PLUGIN_VERSION : null
                );
            }

            return array('iu-simple-repeater');
        }

        protected function register_controls() {
            $grid_layouts = array('grid', 'cards', 'list');
            $image_layouts = array('grid', 'cards', 'list', 'logos');

            $this->start_controls_section('section_content', array(
                'label' => __('Content', 'istodata-utilities'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ));

            $this->add_control('field_name', array(
                'label' => __('ACF Field Name', 'istodata-utilities'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => 'services',
                'description' => __('Use the field name of an ISTODATA Simple Repeater ACF field.', 'istodata-utilities'),
            ));

            $this->add_control('data_source', array(
                'label' => __('Data Source', 'istodata-utilities'),
                'type' => Controls_Manager::SELECT,
                'options' => array(
                    'post' => __('Current Post', 'istodata-utilities'),
                    'taxonomy_term' => __('Current Taxonomy Term', 'istodata-utilities'),
                ),
                'default' => 'post',
                'description' => __('Use Current Taxonomy Term in taxonomy archive templates.', 'istodata-utilities'),
            ));

            $this->add_control('preview_term_id', array(
                'label' => __('Preview Term', 'istodata-utilities'),
                'type' => Controls_Manager::SELECT2,
                'options' => $this->get_taxonomy_term_options(),
                'description' => __('Used only in the Elementor editor preview. The frontend always uses the current taxonomy term.', 'istodata-utilities'),
                'condition' => array(
                    'data_source' => 'taxonomy_term',
                ),
            ));

            $this->add_control('layout', array(
                'label' => __('Layout', 'istodata-utilities'),
                'type' => Controls_Manager::SELECT,
                'options' => array(
                    'grid' => __('Grid', 'istodata-utilities'),
                    'accordion' => __('Accordion', 'istodata-utilities'),
                    'logos' => __('Logos', 'istodata-utilities'),
                    'buttons' => __('Buttons', 'istodata-utilities'),
                ),
                'default' => 'grid',
            ));

            $this->add_responsive_control('columns', array(
                'label' => __('Columns', 'istodata-utilities'),
                'type' => Controls_Manager::SELECT,
                'options' => array(
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6',
                ),
                'default' => '3',
                'tablet_default' => '2',
                'mobile_default' => '1',
                'selectors' => array(
                    '{{WRAPPER}} .iu-simple-repeater--grid' => '--iu-simple-repeater-columns: {{VALUE}};',
                ),
                'condition' => array(
                    'layout' => $image_layouts,
                ),
            ));

            $this->add_responsive_control('image_size', array(
                'label' => __('Image Size', 'istodata-utilities'),
                'type' => Controls_Manager::SELECT,
                'options' => $this->get_image_size_options(),
                'default' => 'medium',
                'condition' => array(
                    'layout' => $image_layouts,
                ),
            ));

            $this->add_responsive_control('image_position', array(
                'label' => __('Image Position', 'istodata-utilities'),
                'type' => Controls_Manager::CHOOSE,
                'options' => array(
                    'top' => array(
                        'title' => __('Top', 'istodata-utilities'),
                        'icon' => 'eicon-v-align-top',
                    ),
                    'left' => array(
                        'title' => __('Left', 'istodata-utilities'),
                        'icon' => 'eicon-h-align-left',
                    ),
                    'right' => array(
                        'title' => __('Right', 'istodata-utilities'),
                        'icon' => 'eicon-h-align-right',
                    ),
                ),
                'default' => 'top',
                'selectors_dictionary' => array(
                    'top' => 'column',
                    'left' => 'row',
                    'right' => 'row-reverse',
                ),
                'selectors' => array(
                    '{{WRAPPER}} .iu-simple-repeater__item' => 'flex-direction: {{VALUE}};',
                ),
                'condition' => array(
                    'layout' => $grid_layouts,
                ),
            ));

            $this->add_control('title_tag', array(
                'label' => __('Title HTML Tag', 'istodata-utilities'),
                'type' => Controls_Manager::SELECT,
                'options' => array(
                    'h2' => 'H2',
                    'h3' => 'H3',
                    'h4' => 'H4',
                    'h5' => 'H5',
                    'h6' => 'H6',
                    'span' => 'span',
                    'div' => 'div',
                ),
                'default' => 'h3',
                'condition' => array(
                    'layout' => $grid_layouts,
                ),
            ));

            $this->add_control('link_type', array(
                'label' => __('Link Type', 'istodata-utilities'),
                'type' => Controls_Manager::SELECT,
                'options' => array(
                    'text' => __('Text Link', 'istodata-utilities'),
                    'item' => __('Whole Item', 'istodata-utilities'),
                ),
                'default' => 'text',
                'condition' => array(
                    'layout' => $grid_layouts,
                ),
            ));

            $this->add_control('button_text', array(
                'label' => __('Link Text', 'istodata-utilities'),
                'type' => Controls_Manager::TEXT,
                'default' => __('More', 'istodata-utilities'),
                'dynamic' => array('active' => true),
                'condition' => array(
                    'layout' => $grid_layouts,
                    'link_type' => 'text',
                ),
            ));

            $this->add_control('link_target_blank', array(
                'label' => __('Open Links In New Tab', 'istodata-utilities'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'istodata-utilities'),
                'label_off' => __('No', 'istodata-utilities'),
                'return_value' => 'yes',
                'default' => '',
                'condition' => array(
                    'layout' => array('grid', 'cards', 'list', 'logos', 'buttons'),
                ),
            ));

            $this->end_controls_section();

            $this->start_controls_section('section_layout', array(
                'label' => __('Layout', 'istodata-utilities'),
                'tab' => Controls_Manager::TAB_STYLE,
            ));

            $this->add_responsive_control('row_gap', array(
                'label' => __('Row Gap', 'istodata-utilities'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => array('px', 'em', 'rem'),
                'range' => array(
                    'px' => array('min' => 0, 'max' => 80),
                    'em' => array('min' => 0, 'max' => 5, 'step' => 0.1),
                    'rem' => array('min' => 0, 'max' => 5, 'step' => 0.1),
                ),
                'selectors' => array(
                    '{{WRAPPER}} .iu-simple-repeater' => '--iu-simple-repeater-row-gap: {{SIZE}}{{UNIT}}; row-gap: {{SIZE}}{{UNIT}};',
                ),
            ));

            $this->add_responsive_control('column_gap', array(
                'label' => __('Column Gap', 'istodata-utilities'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => array('px', 'em', 'rem'),
                'range' => array(
                    'px' => array('min' => 0, 'max' => 80),
                    'em' => array('min' => 0, 'max' => 5, 'step' => 0.1),
                    'rem' => array('min' => 0, 'max' => 5, 'step' => 0.1),
                ),
                'selectors' => array(
                    '{{WRAPPER}} .iu-simple-repeater' => '--iu-simple-repeater-column-gap: {{SIZE}}{{UNIT}}; column-gap: {{SIZE}}{{UNIT}};',
                ),
            ));

            $this->add_responsive_control('vertical_align', array(
                'label' => __('Vertical Align', 'istodata-utilities'),
                'type' => Controls_Manager::CHOOSE,
                'options' => array(
                    'flex-start' => array(
                        'title' => __('Top', 'istodata-utilities'),
                        'icon' => 'eicon-v-align-top',
                    ),
                    'center' => array(
                        'title' => __('Middle', 'istodata-utilities'),
                        'icon' => 'eicon-v-align-middle',
                    ),
                    'flex-end' => array(
                        'title' => __('Bottom', 'istodata-utilities'),
                        'icon' => 'eicon-v-align-bottom',
                    ),
                    'stretch' => array(
                        'title' => __('Stretch', 'istodata-utilities'),
                        'icon' => 'eicon-v-align-stretch',
                    ),
                ),
                'default' => 'flex-start',
                'selectors' => array(
                    '{{WRAPPER}} .iu-simple-repeater__item' => '--iu-simple-repeater-vertical-align: {{VALUE}};',
                ),
                'condition' => array(
                    'layout' => $grid_layouts,
                ),
            ));

            $this->add_responsive_control('image_content_gap', array(
                'label' => __('Image / Content Gap', 'istodata-utilities'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => array('px', 'em', 'rem'),
                'range' => array(
                    'px' => array('min' => 0, 'max' => 80),
                    'em' => array('min' => 0, 'max' => 5, 'step' => 0.1),
                    'rem' => array('min' => 0, 'max' => 5, 'step' => 0.1),
                ),
                'default' => array('size' => 16, 'unit' => 'px'),
                'selectors' => array(
                    '{{WRAPPER}} .iu-simple-repeater__item' => 'gap: {{SIZE}}{{UNIT}};',
                ),
                'condition' => array(
                    'layout' => $grid_layouts,
                    'image_position!' => '',
                ),
            ));

            $this->add_responsive_control('title_text_gap', array(
                'label' => __('Title / Description Gap', 'istodata-utilities'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => array('px', 'em', 'rem'),
                'range' => array(
                    'px' => array('min' => 0, 'max' => 60),
                    'em' => array('min' => 0, 'max' => 4, 'step' => 0.1),
                    'rem' => array('min' => 0, 'max' => 4, 'step' => 0.1),
                ),
                'default' => array('size' => 8, 'unit' => 'px'),
                'selectors' => array(
                    '{{WRAPPER}} .iu-simple-repeater__content' => 'gap: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .iu-simple-repeater__accordion-item .iu-simple-repeater__content' => 'margin-top: {{SIZE}}{{UNIT}};',
                ),
                'condition' => array(
                    'layout' => array('grid', 'cards', 'list', 'accordion'),
                ),
            ));

            $this->add_responsive_control('alignment', array(
                'label' => __('Alignment', 'istodata-utilities'),
                'type' => Controls_Manager::CHOOSE,
                'options' => array(
                    'left' => array('title' => __('Left', 'istodata-utilities'), 'icon' => 'eicon-text-align-left'),
                    'center' => array('title' => __('Center', 'istodata-utilities'), 'icon' => 'eicon-text-align-center'),
                    'right' => array('title' => __('Right', 'istodata-utilities'), 'icon' => 'eicon-text-align-right'),
                ),
                'default' => 'left',
                'selectors_dictionary' => array(
                    'left' => 'left; --iu-simple-repeater-align: flex-start',
                    'center' => 'center; --iu-simple-repeater-align: center',
                    'right' => 'right; --iu-simple-repeater-align: flex-end',
                ),
                'selectors' => array(
                    '{{WRAPPER}} .iu-simple-repeater' => 'text-align: {{VALUE}};',
                ),
            ));

            $this->end_controls_section();

            $this->start_controls_section('section_divider_style', array(
                'label' => __('Divider', 'istodata-utilities'),
                'tab' => Controls_Manager::TAB_STYLE,
            ));

            $this->add_control('show_divider', array(
                'label' => __('Show Divider', 'istodata-utilities'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'istodata-utilities'),
                'label_off' => __('No', 'istodata-utilities'),
                'return_value' => 'yes',
                'default' => '',
            ));

            $this->add_responsive_control('divider_orientation', array(
                'label' => __('Orientation', 'istodata-utilities'),
                'type' => Controls_Manager::SELECT,
                'options' => array(
                    'horizontal' => __('Horizontal', 'istodata-utilities'),
                    'vertical' => __('Vertical', 'istodata-utilities'),
                ),
                'default' => 'horizontal',
                'selectors_dictionary' => array(
                    'horizontal' => '--iu-simple-repeater-divider-border-top-width: var(--iu-simple-repeater-divider-thickness); --iu-simple-repeater-divider-border-left-width: 0; --iu-simple-repeater-divider-top: calc(var(--iu-simple-repeater-row-gap) / -2); --iu-simple-repeater-divider-bottom: auto; --iu-simple-repeater-divider-left: 50%; --iu-simple-repeater-divider-right: auto; --iu-simple-repeater-divider-width: var(--iu-simple-repeater-divider-length); --iu-simple-repeater-divider-height: 0; --iu-simple-repeater-divider-transform: translateX(-50%)',
                    'vertical' => '--iu-simple-repeater-divider-border-top-width: 0; --iu-simple-repeater-divider-border-left-width: var(--iu-simple-repeater-divider-thickness); --iu-simple-repeater-divider-top: 50%; --iu-simple-repeater-divider-bottom: auto; --iu-simple-repeater-divider-left: calc(var(--iu-simple-repeater-column-gap) / -2); --iu-simple-repeater-divider-right: auto; --iu-simple-repeater-divider-width: 0; --iu-simple-repeater-divider-height: var(--iu-simple-repeater-divider-length); --iu-simple-repeater-divider-transform: translateY(-50%)',
                ),
                'selectors' => array(
                    '{{WRAPPER}} .iu-simple-repeater' => '{{VALUE}};',
                ),
                'condition' => array(
                    'show_divider' => 'yes',
                ),
            ));

            $this->add_control('divider_color', array(
                'label' => __('Color', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .iu-simple-repeater--has-divider > * + *::before' => 'border-color: {{VALUE}};',
                ),
                'condition' => array(
                    'show_divider' => 'yes',
                ),
            ));

            $this->add_responsive_control('divider_thickness', array(
                'label' => __('Thickness', 'istodata-utilities'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => array('px'),
                'range' => array(
                    'px' => array('min' => 1, 'max' => 20),
                ),
                'default' => array('size' => 1, 'unit' => 'px'),
                'selectors' => array(
                    '{{WRAPPER}} .iu-simple-repeater' => '--iu-simple-repeater-divider-thickness: {{SIZE}}{{UNIT}};',
                ),
                'condition' => array(
                    'show_divider' => 'yes',
                ),
            ));

            $this->add_responsive_control('divider_length', array(
                'label' => __('Length', 'istodata-utilities'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => array('%', 'px', 'em', 'rem'),
                'range' => array(
                    '%' => array('min' => 1, 'max' => 100),
                    'px' => array('min' => 1, 'max' => 1200),
                    'em' => array('min' => 1, 'max' => 80, 'step' => 0.1),
                    'rem' => array('min' => 1, 'max' => 80, 'step' => 0.1),
                ),
                'default' => array('size' => 100, 'unit' => '%'),
                'selectors' => array(
                    '{{WRAPPER}} .iu-simple-repeater' => '--iu-simple-repeater-divider-length: {{SIZE}}{{UNIT}};',
                ),
                'condition' => array(
                    'show_divider' => 'yes',
                ),
            ));

            $this->end_controls_section();

            $this->start_controls_section('section_image_style', array(
                'label' => __('Image', 'istodata-utilities'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'layout' => $image_layouts,
                ),
            ));

            $this->add_responsive_control('image_border_radius', array(
                'label' => __('Border Radius', 'istodata-utilities'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%', 'em', 'rem'),
                'selectors' => array(
                    '{{WRAPPER}} .iu-simple-repeater__image img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            ));

            $this->add_responsive_control('image_width', array(
                'label' => __('Width', 'istodata-utilities'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => array('%', 'px', 'em', 'rem'),
                'range' => array(
                    '%' => array('min' => 10, 'max' => 100),
                    'px' => array('min' => 1, 'max' => 800),
                    'em' => array('min' => 1, 'max' => 50, 'step' => 0.1),
                    'rem' => array('min' => 1, 'max' => 50, 'step' => 0.1),
                ),
                'selectors' => array(
                    '{{WRAPPER}} .iu-simple-repeater__image' => 'width: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .iu-simple-repeater__item--image-left .iu-simple-repeater__image, {{WRAPPER}} .iu-simple-repeater__item--image-right .iu-simple-repeater__image' => 'flex-basis: {{SIZE}}{{UNIT}};',
                ),
            ));

            $this->add_responsive_control('image_height', array(
                'label' => __('Height', 'istodata-utilities'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => array('px', 'em', 'rem', 'vh'),
                'range' => array(
                    'px' => array('min' => 1, 'max' => 800),
                    'em' => array('min' => 1, 'max' => 50, 'step' => 0.1),
                    'rem' => array('min' => 1, 'max' => 50, 'step' => 0.1),
                    'vh' => array('min' => 1, 'max' => 100),
                ),
                'selectors' => array(
                    '{{WRAPPER}} .iu-simple-repeater__image img' => 'height: {{SIZE}}{{UNIT}};',
                ),
            ));

            $this->add_control('image_fit', array(
                'label' => __('Object Fit', 'istodata-utilities'),
                'type' => Controls_Manager::SELECT,
                'options' => array(
                    'contain' => __('Contain', 'istodata-utilities'),
                    'cover' => __('Cover', 'istodata-utilities'),
                    'fill' => __('Fill', 'istodata-utilities'),
                    'none' => __('None', 'istodata-utilities'),
                ),
                'default' => 'contain',
                'selectors' => array(
                    '{{WRAPPER}} .iu-simple-repeater__image img' => 'object-fit: {{VALUE}};',
                ),
            ));

            $this->end_controls_section();

            $this->start_controls_section('section_card_style', array(
                'label' => __('Items', 'istodata-utilities'),
                'tab' => Controls_Manager::TAB_STYLE,
            ));

            $this->add_responsive_control('item_padding', array(
                'label' => __('Padding', 'istodata-utilities'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%', 'em', 'rem'),
                'selectors' => array(
                    '{{WRAPPER}} .iu-simple-repeater__item, {{WRAPPER}} .iu-simple-repeater__accordion-item, {{WRAPPER}} .iu-simple-repeater__button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            ));

            $this->add_group_control(Group_Control_Background::get_type(), array(
                'name' => 'item_background',
                'exclude' => array('image'),
                'selector' => '{{WRAPPER}} .iu-simple-repeater__item, {{WRAPPER}} .iu-simple-repeater__accordion-item, {{WRAPPER}} .iu-simple-repeater__button',
            ));

            $this->add_group_control(Group_Control_Border::get_type(), array(
                'name' => 'item_border',
                'selector' => '{{WRAPPER}} .iu-simple-repeater__item, {{WRAPPER}} .iu-simple-repeater__accordion-item, {{WRAPPER}} .iu-simple-repeater__button',
            ));

            $this->add_responsive_control('item_border_radius', array(
                'label' => __('Border Radius', 'istodata-utilities'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%', 'em', 'rem'),
                'selectors' => array(
                    '{{WRAPPER}} .iu-simple-repeater__item, {{WRAPPER}} .iu-simple-repeater__accordion-item, {{WRAPPER}} .iu-simple-repeater__button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            ));

            $this->add_group_control(Group_Control_Box_Shadow::get_type(), array(
                'name' => 'item_box_shadow',
                'selector' => '{{WRAPPER}} .iu-simple-repeater__item, {{WRAPPER}} .iu-simple-repeater__accordion-item, {{WRAPPER}} .iu-simple-repeater__button',
            ));

            $this->end_controls_section();

            $this->start_controls_section('section_title_style', array(
                'label' => __('Title', 'istodata-utilities'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'layout' => array('grid', 'cards', 'list', 'accordion', 'buttons'),
                ),
            ));

            $this->add_group_control(Group_Control_Typography::get_type(), array(
                'name' => 'title_typography',
                'selector' => '{{WRAPPER}} .iu-simple-repeater__title, {{WRAPPER}} .iu-simple-repeater__accordion-title, {{WRAPPER}} .iu-simple-repeater__button',
            ));

            $this->add_control('title_color', array(
                'label' => __('Title Color', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .iu-simple-repeater__title, {{WRAPPER}} .iu-simple-repeater__accordion-title, {{WRAPPER}} .iu-simple-repeater__button' => 'color: {{VALUE}};',
                ),
            ));

            $this->end_controls_section();

            $this->start_controls_section('section_description_style', array(
                'label' => __('Description', 'istodata-utilities'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'layout' => array('grid', 'cards', 'list', 'accordion'),
                ),
            ));

            $this->add_group_control(Group_Control_Typography::get_type(), array(
                'name' => 'text_typography',
                'selector' => '{{WRAPPER}} .iu-simple-repeater__text',
            ));

            $this->add_control('text_color', array(
                'label' => __('Description Color', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .iu-simple-repeater__text' => 'color: {{VALUE}};',
                ),
            ));

            $this->end_controls_section();

            $this->start_controls_section('section_link_style', array(
                'label' => __('Links', 'istodata-utilities'),
                'tab' => Controls_Manager::TAB_STYLE,
                'conditions' => array(
                    'relation' => 'or',
                    'terms' => array(
                        array(
                            'terms' => array(
                                array(
                                    'name' => 'layout',
                                    'operator' => 'in',
                                    'value' => $grid_layouts,
                                ),
                                array(
                                    'name' => 'link_type',
                                    'operator' => '===',
                                    'value' => 'text',
                                ),
                            ),
                        ),
                        array(
                            'name' => 'layout',
                            'operator' => '===',
                            'value' => 'buttons',
                        ),
                    ),
                ),
            ));

            $this->add_group_control(Group_Control_Typography::get_type(), array(
                'name' => 'link_typography',
                'selector' => '{{WRAPPER}} .iu-simple-repeater__link, {{WRAPPER}} .iu-simple-repeater__button',
            ));

            $this->add_control('link_color', array(
                'label' => __('Link Color', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .iu-simple-repeater__link, {{WRAPPER}} .iu-simple-repeater__button' => 'color: {{VALUE}};',
                ),
            ));

            $this->add_control('link_hover_color', array(
                'label' => __('Hover Color', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .iu-simple-repeater__link:hover, {{WRAPPER}} .iu-simple-repeater__button:hover' => 'color: {{VALUE}};',
                ),
            ));

            $this->end_controls_section();
        }

        protected function render() {
            $settings = $this->get_settings_for_display();
            $field_name = isset($settings['field_name']) ? sanitize_key($settings['field_name']) : '';
            if (!$field_name) {
                $this->render_editor_notice(__('Set an ACF field name.', 'istodata-utilities'));
                return;
            }

            $data_source = isset($settings['data_source']) && $settings['data_source'] === 'taxonomy_term' ? 'taxonomy_term' : 'post';
            if ($data_source === 'taxonomy_term') {
                $term = $this->get_current_taxonomy_term();
                $preview_term_id = isset($settings['preview_term_id']) ? absint($settings['preview_term_id']) : 0;
                if ($preview_term_id > 0 && $this->is_editor_edit_mode()) {
                    $preview_term = get_term($preview_term_id);
                    if ($preview_term instanceof \WP_Term) {
                        $term = $preview_term;
                    }
                }
                if (!($term instanceof \WP_Term)) {
                    $this->render_editor_notice(__('Select a Preview Term for this widget.', 'istodata-utilities'));
                    return;
                }

                $items = function_exists('get_field') ? get_field($field_name, $term) : null;
                if (!is_array($items) || empty($items)) {
                    $items = get_term_meta($term->term_id, $field_name, true);
                }
            } else {
                $post_id = get_the_ID();
                $items = function_exists('get_field') ? get_field($field_name, $post_id) : get_post_meta($post_id, $field_name, true);
            }
            if (!is_array($items) || empty($items)) {
                $this->render_editor_notice(__('No repeater items found.', 'istodata-utilities'));
                return;
            }

            $layout = isset($settings['layout']) ? $settings['layout'] : 'grid';
            if (in_array($layout, array('cards', 'list'), true)) {
                $layout = 'grid';
            }

            $classes = array('iu-simple-repeater', 'iu-simple-repeater--' . sanitize_html_class($layout));
            if (in_array($layout, array('grid', 'logos'), true)) {
                $classes[] = 'iu-simple-repeater--grid';
                $classes[] = 'iu-simple-repeater--columns-' . $this->get_columns_class_value(isset($settings['columns']) ? $settings['columns'] : '3');
                $classes[] = 'iu-simple-repeater--columns-tablet-' . $this->get_columns_class_value(isset($settings['columns_tablet']) ? $settings['columns_tablet'] : '2');
                $classes[] = 'iu-simple-repeater--columns-mobile-' . $this->get_columns_class_value(isset($settings['columns_mobile']) ? $settings['columns_mobile'] : '1');
            }
            if (!empty($settings['show_divider'])) {
                $divider_orientation = $this->get_divider_orientation_class_value(isset($settings['divider_orientation']) ? $settings['divider_orientation'] : 'horizontal');
                $divider_orientation_tablet = $this->get_divider_orientation_class_value(isset($settings['divider_orientation_tablet']) ? $settings['divider_orientation_tablet'] : $divider_orientation);
                $divider_orientation_mobile = $this->get_divider_orientation_class_value(isset($settings['divider_orientation_mobile']) ? $settings['divider_orientation_mobile'] : $divider_orientation_tablet);
                $classes[] = 'iu-simple-repeater--has-divider';
                $classes[] = 'iu-simple-repeater--divider-desktop-' . $divider_orientation;
                $classes[] = 'iu-simple-repeater--divider-tablet-' . $divider_orientation_tablet;
                $classes[] = 'iu-simple-repeater--divider-mobile-' . $divider_orientation_mobile;
            }

            $raw_settings = $this->get_data('settings');
            $legacy_gap_css = $this->get_legacy_gap_css($settings, is_array($raw_settings) ? $raw_settings : array());
            if ($legacy_gap_css !== '') {
                echo '<style>' . $legacy_gap_css . '</style>';
            }

            echo '<div class="' . esc_attr(implode(' ', $classes)) . '">';
            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }

                if ($layout === 'accordion') {
                    $this->render_accordion_item($item);
                } elseif ($layout === 'logos') {
                    $this->render_logo_item($item, $settings);
                } elseif ($layout === 'buttons') {
                    $this->render_button_item($item, $settings);
                } else {
                    $this->render_content_item($item, $settings, $layout);
                }
            }
            echo '</div>';
        }

        private function render_content_item($item, $settings, $layout) {
            $position = !empty($settings['image_position']) ? $settings['image_position'] : 'top';
            if (!in_array($position, array('top', 'left', 'right'), true)) {
                $position = 'top';
            }

            $link = isset($item['link']) ? trim($item['link']) : '';
            $link_type = !empty($settings['link_type']) ? $settings['link_type'] : 'text';
            $wrap_item = ($layout === 'grid' && $link_type === 'item' && $link !== '');
            $tag = $wrap_item ? 'a' : 'div';
            $attrs = $wrap_item ? ' href="' . esc_url($link) . '"' . $this->get_link_attrs($settings) : '';

            echo '<' . $tag . ' class="iu-simple-repeater__item iu-simple-repeater__item--image-' . esc_attr($position) . ' iu-simple-repeater__item--link-' . esc_attr($link_type) . '"' . $attrs . '>';
            $this->render_image($item, $settings);
            echo '<div class="iu-simple-repeater__content">';
            $this->render_title($item, $settings);
            $this->render_text($item);
            if ($layout === 'grid' && !$wrap_item) {
                $this->render_card_link($item, $settings);
            }
            echo '</div>';
            echo '</' . $tag . '>';
        }

        private function render_accordion_item($item) {
            $title = isset($item['title']) ? trim($item['title']) : '';
            $text = isset($item['text']) ? trim($item['text']) : '';
            if ($title === '' && $text === '') {
                return;
            }

            echo '<details class="iu-simple-repeater__accordion-item">';
            echo '<summary class="iu-simple-repeater__accordion-title">' . esc_html($title ? $title : __('Item', 'istodata-utilities')) . '</summary>';
            if ($text !== '') {
                echo '<div class="iu-simple-repeater__content"><div class="iu-simple-repeater__text">' . wp_kses_post(wpautop($text)) . '</div></div>';
            }
            echo '</details>';
        }

        private function render_logo_item($item, $settings) {
            $link = isset($item['link']) ? trim($item['link']) : '';
            $has_link = $link !== '';
            echo '<div class="iu-simple-repeater__item iu-simple-repeater__logo">';
            if ($has_link) {
                echo '<a class="iu-simple-repeater__logo-link" href="' . esc_url($link) . '"' . $this->get_link_attrs($settings) . '>';
            }
            $this->render_image($item, $settings);
            if ($has_link) {
                echo '</a>';
            }
            echo '</div>';
        }

        private function render_button_item($item, $settings) {
            $title = isset($item['title']) ? trim($item['title']) : '';
            $link = isset($item['link']) ? trim($item['link']) : '';
            if ($title === '' && $link === '') {
                return;
            }

            if ($link !== '') {
                echo '<a class="iu-simple-repeater__button" href="' . esc_url($link) . '"' . $this->get_link_attrs($settings) . '>' . esc_html($title ? $title : $link) . '</a>';
            } else {
                echo '<span class="iu-simple-repeater__button">' . esc_html($title) . '</span>';
            }
        }

        private function render_title($item, $settings) {
            if (empty($item['title'])) {
                return;
            }

            $tag = !empty($settings['title_tag']) ? $settings['title_tag'] : 'h3';
            $allowed = array('h2', 'h3', 'h4', 'h5', 'h6', 'span', 'div');
            if (!in_array($tag, $allowed, true)) {
                $tag = 'h3';
            }

            echo '<' . esc_attr($tag) . ' class="iu-simple-repeater__title">' . esc_html($item['title']) . '</' . esc_attr($tag) . '>';
        }

        private function render_text($item) {
            if (empty($item['text'])) {
                return;
            }

            echo '<div class="iu-simple-repeater__text">' . wp_kses_post(wpautop($item['text'])) . '</div>';
        }

        private function render_image($item, $settings) {
            $image_id = isset($item['image']) ? absint($item['image']) : 0;
            if (!$image_id) {
                return;
            }

            $desktop_size = !empty($settings['image_size']) ? $settings['image_size'] : 'medium';
            $tablet_size = !empty($settings['image_size_tablet']) ? $settings['image_size_tablet'] : $desktop_size;
            $mobile_size = !empty($settings['image_size_mobile']) ? $settings['image_size_mobile'] : $tablet_size;
            $desktop_src = wp_get_attachment_image_src($image_id, $desktop_size);
            if (!$desktop_src) {
                return;
            }

            $alt = get_post_meta($image_id, '_wp_attachment_image_alt', true);
            $alt = $alt !== '' ? $alt : get_the_title($image_id);

            echo '<div class="iu-simple-repeater__image">';
            echo '<picture>';
            $this->render_image_source($image_id, $mobile_size, '(max-width: 767px)');
            $this->render_image_source($image_id, $tablet_size, '(max-width: 1024px)');
            echo '<img src="' . esc_url($desktop_src[0]) . '" alt="' . esc_attr($alt) . '" loading="lazy" decoding="async" />';
            echo '</picture>';
            echo '</div>';
        }

        private function render_image_source($image_id, $size, $media) {
            $srcset = wp_get_attachment_image_srcset($image_id, $size);
            if (!$srcset) {
                $src = wp_get_attachment_image_src($image_id, $size);
                $srcset = $src ? $src[0] : '';
            }

            if ($srcset) {
                echo '<source media="' . esc_attr($media) . '" srcset="' . esc_attr($srcset) . '" />';
            }
        }

        private function render_card_link($item, $settings) {
            $link = isset($item['link']) ? trim($item['link']) : '';
            if ($link === '') {
                return;
            }

            $text = !empty($settings['button_text']) ? $settings['button_text'] : __('More', 'istodata-utilities');
            echo '<a class="iu-simple-repeater__link" href="' . esc_url($link) . '"' . $this->get_link_attrs($settings) . '>' . esc_html($text) . '</a>';
        }

        private function get_link_attrs($settings) {
            if (empty($settings['link_target_blank'])) {
                return '';
            }

            return ' target="_blank" rel="noopener noreferrer"';
        }

        private function get_legacy_gap_css($settings, $raw_settings) {
            $selector = '.elementor-element-' . sanitize_html_class($this->get_id()) . ' .iu-simple-repeater';
            $breakpoints = array(
                '' => '',
                '_tablet' => '@media (max-width: 1024px)',
                '_mobile' => '@media (max-width: 767px)',
            );
            $css = '';

            foreach ($breakpoints as $suffix => $media) {
                $legacy_key = 'gap' . $suffix;
                $legacy_setting = array_key_exists($legacy_key, $raw_settings) ? $raw_settings[$legacy_key] : (isset($settings[$legacy_key]) ? $settings[$legacy_key] : null);
                $legacy_gap = $this->get_dimension_css_value($legacy_setting);
                if ($legacy_gap === '') {
                    continue;
                }

                $row_gap = $this->get_dimension_css_value(isset($settings['row_gap' . $suffix]) ? $settings['row_gap' . $suffix] : null);
                $column_gap = $this->get_dimension_css_value(isset($settings['column_gap' . $suffix]) ? $settings['column_gap' . $suffix] : null);
                if ($row_gap !== '' && $column_gap !== '') {
                    continue;
                }

                $rules = array();
                if ($row_gap === '') {
                    $rules[] = '--iu-simple-repeater-row-gap:' . $legacy_gap;
                    $rules[] = 'row-gap:' . $legacy_gap;
                }
                if ($column_gap === '') {
                    $rules[] = '--iu-simple-repeater-column-gap:' . $legacy_gap;
                    $rules[] = 'column-gap:' . $legacy_gap;
                }

                $rule = $selector . '{' . implode(';', $rules) . ';}';
                $css .= $media ? $media . '{' . $rule . '}' : $rule;
            }

            return $css;
        }

        private function get_dimension_css_value($setting) {
            if (!is_array($setting) || !isset($setting['size']) || $setting['size'] === '') {
                return '';
            }

            $unit = isset($setting['unit']) ? $setting['unit'] : 'px';
            if (!in_array($unit, array('px', 'em', 'rem', '%', 'vh', 'vw'), true)) {
                $unit = 'px';
            }

            return (float) $setting['size'] . $unit;
        }

        private function get_columns_class_value($columns) {
            $columns = absint($columns);
            if ($columns < 1 || $columns > 6) {
                return 1;
            }

            return $columns;
        }

        private function get_divider_orientation_class_value($orientation) {
            return $orientation === 'vertical' ? 'vertical' : 'horizontal';
        }

        private function get_current_taxonomy_term() {
            $term = get_queried_object();
            if ($term instanceof \WP_Term) {
                return $term;
            }

            $queries = array();
            if (isset($GLOBALS['wp_query']) && $GLOBALS['wp_query'] instanceof \WP_Query) {
                $queries[] = $GLOBALS['wp_query'];
            }
            if (isset($GLOBALS['wp_the_query']) && $GLOBALS['wp_the_query'] instanceof \WP_Query) {
                $queries[] = $GLOBALS['wp_the_query'];
            }

            foreach ($queries as $query) {
                $term = $query->get_queried_object();
                if ($term instanceof \WP_Term) {
                    return $term;
                }

                $term_id = $query->get_queried_object_id();
                if ($term_id) {
                    $term = get_term($term_id);
                    if ($term instanceof \WP_Term) {
                        return $term;
                    }
                }
            }

            return null;
        }

        private function get_taxonomy_term_options() {
            $taxonomies = get_taxonomies(array('public' => true), 'names');
            if (empty($taxonomies)) {
                return array();
            }

            $terms = get_terms(array(
                'taxonomy' => array_values($taxonomies),
                'hide_empty' => false,
                'orderby' => 'name',
                'order' => 'ASC',
            ));
            if (is_wp_error($terms)) {
                return array();
            }

            $options = array();
            foreach ($terms as $term) {
                if ($term instanceof \WP_Term) {
                    $options[$term->term_id] = $term->name . ' (' . $term->taxonomy . ')';
                }
            }

            return $options;
        }

        private function is_editor_edit_mode() {
            return class_exists('Elementor\\Plugin') && \Elementor\Plugin::$instance->editor && \Elementor\Plugin::$instance->editor->is_edit_mode();
        }

        private function render_editor_notice($message) {
            if ($this->is_editor_edit_mode()) {
                echo '<div class="iu-simple-repeater__notice">' . esc_html($message) . '</div>';
            }
        }

        private function get_image_size_options() {
            $options = array(
                'thumbnail' => __('Thumbnail', 'istodata-utilities'),
                'medium' => __('Medium', 'istodata-utilities'),
                'medium_large' => __('Medium Large', 'istodata-utilities'),
                'large' => __('Large', 'istodata-utilities'),
                'full' => __('Full', 'istodata-utilities'),
            );

            foreach (get_intermediate_image_sizes() as $size) {
                if (!isset($options[$size])) {
                    $options[$size] = $size;
                }
            }

            return $options;
        }
    }
}
