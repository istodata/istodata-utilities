<?php
// Safety check
if (!defined('ABSPATH')) { exit; }

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

if (!class_exists('IU_Query_Posts_Widget')) {
    class IU_Query_Posts_Widget extends Widget_Base {
        public function get_name() {
            return 'iu_query_posts';
        }

        public function get_title() {
            return __('Query Posts', 'istodata-utilities');
        }

        public function get_icon() {
            return 'eicon-post-list';
        }

        public function get_categories() {
            return array('istodata-kit');
        }

        public function get_style_depends() {
            if (!wp_style_is('iu-query-posts', 'registered')) {
                wp_register_style(
                    'iu-query-posts',
                    IU_PLUGIN_URL . 'assets/css/query-posts.css',
                    array(),
                    defined('IU_PLUGIN_VERSION') ? IU_PLUGIN_VERSION : null
                );
            }

            return array('iu-query-posts');
        }

        protected function register_controls() {
            $post_type_options = $this->get_post_type_options();
            $default_post_type = isset($post_type_options['post']) ? 'post' : key($post_type_options);

            $this->start_controls_section('section_query', array(
                'label' => __('Query', 'istodata-utilities'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ));

            $this->add_control('post_type', array(
                'label' => __('Post Type', 'istodata-utilities'),
                'type' => Controls_Manager::SELECT,
                'options' => $post_type_options,
                'default' => $default_post_type ? $default_post_type : 'post',
            ));

            $this->add_control('posts_per_page', array(
                'label' => __('Posts To Show', 'istodata-utilities'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 50,
                'step' => 1,
                'default' => 5,
            ));

            $this->add_control('orderby', array(
                'label' => __('Order By', 'istodata-utilities'),
                'type' => Controls_Manager::SELECT,
                'options' => array(
                    'date' => __('Date', 'istodata-utilities'),
                    'modified' => __('Last Modified', 'istodata-utilities'),
                    'title' => __('Title', 'istodata-utilities'),
                    'menu_order' => __('Menu Order', 'istodata-utilities'),
                    'rand' => __('Random', 'istodata-utilities'),
                ),
                'default' => 'date',
            ));

            $this->add_control('order', array(
                'label' => __('Order', 'istodata-utilities'),
                'type' => Controls_Manager::SELECT,
                'options' => array(
                    'DESC' => __('Descending', 'istodata-utilities'),
                    'ASC' => __('Ascending', 'istodata-utilities'),
                ),
                'default' => 'DESC',
                'condition' => array(
                    'orderby!' => 'rand',
                ),
            ));

            $this->add_control('offset', array(
                'label' => __('Offset', 'istodata-utilities'),
                'type' => Controls_Manager::NUMBER,
                'min' => 0,
                'max' => 100,
                'step' => 1,
                'default' => 0,
            ));

            $this->add_control('term_slugs', array(
                'label' => __('Terms', 'istodata-utilities'),
                'type' => Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => $this->get_term_slug_options(),
                'description' => __('Select one or more terms. Each option shows its taxonomy.', 'istodata-utilities'),
                'separator' => 'before',
            ));

            $this->add_control('ignore_sticky_posts', array(
                'label' => __('Ignore Sticky Posts', 'istodata-utilities'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'istodata-utilities'),
                'label_off' => __('No', 'istodata-utilities'),
                'return_value' => 'yes',
                'default' => 'yes',
                'condition' => array(
                    'post_type' => 'post',
                ),
            ));

            $this->add_control('show_date', array(
                'label' => __('Show Date', 'istodata-utilities'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'istodata-utilities'),
                'label_off' => __('No', 'istodata-utilities'),
                'return_value' => 'yes',
                'default' => '',
            ));

            $this->add_control('show_extra_link', array(
                'label' => __('Show Extra Link', 'istodata-utilities'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'istodata-utilities'),
                'label_off' => __('No', 'istodata-utilities'),
                'return_value' => 'yes',
                'default' => '',
                'separator' => 'before',
            ));

            $this->add_control('extra_link_text', array(
                'label' => __('Extra Link Text', 'istodata-utilities'),
                'type' => Controls_Manager::TEXT,
                'default' => __('More', 'istodata-utilities'),
                'dynamic' => array('active' => true),
                'condition' => array(
                    'show_extra_link' => 'yes',
                ),
            ));

            $this->add_control('extra_link_url', array(
                'label' => __('Extra Link URL', 'istodata-utilities'),
                'type' => Controls_Manager::URL,
                'dynamic' => array('active' => true),
                'condition' => array(
                    'show_extra_link' => 'yes',
                ),
            ));

            $this->add_control('extra_link_position', array(
                'label' => __('Extra Link Position', 'istodata-utilities'),
                'type' => Controls_Manager::SELECT,
                'options' => array(
                    'first' => __('First', 'istodata-utilities'),
                    'last' => __('Last', 'istodata-utilities'),
                ),
                'default' => 'last',
                'condition' => array(
                    'show_extra_link' => 'yes',
                ),
            ));

            $this->end_controls_section();

            $this->start_controls_section('section_layout', array(
                'label' => __('Layout', 'istodata-utilities'),
                'tab' => Controls_Manager::TAB_STYLE,
            ));

            $this->add_responsive_control('gap', array(
                'label' => __('Gap Between Items', 'istodata-utilities'),
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
                    '{{WRAPPER}} .iu-query-posts' => 'gap: {{SIZE}}{{UNIT}};',
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
                    'left' => 'left; --iu-query-posts-align-items: flex-start',
                    'center' => 'center; --iu-query-posts-align-items: center',
                    'right' => 'right; --iu-query-posts-align-items: flex-end',
                ),
                'selectors' => array(
                    '{{WRAPPER}} .iu-query-posts' => 'text-align: {{VALUE}};',
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
                    '{{WRAPPER}} .iu-query-posts' => '--iu-query-posts-transition: {{SIZE}}s;',
                ),
            ));

            $this->end_controls_section();

            $this->start_controls_section('section_style_item', array(
                'label' => __('Items', 'istodata-utilities'),
                'tab' => Controls_Manager::TAB_STYLE,
            ));

            $this->add_group_control(Group_Control_Typography::get_type(), array(
                'name' => 'title_typography',
                'selector' => '{{WRAPPER}} .iu-query-posts__link',
            ));

            $this->add_responsive_control('item_padding', array(
                'label' => __('Padding', 'istodata-utilities'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%', 'em', 'rem'),
                'selectors' => array(
                    '{{WRAPPER}} .iu-query-posts__link' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            ));

            $this->add_responsive_control('item_border_radius', array(
                'label' => __('Border Radius', 'istodata-utilities'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%', 'em', 'rem'),
                'selectors' => array(
                    '{{WRAPPER}} .iu-query-posts__link' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            ));

            $this->start_controls_tabs('tabs_item_states');

            $this->start_controls_tab('tab_item_normal', array(
                'label' => __('Normal', 'istodata-utilities'),
            ));

            $this->add_control('title_color', array(
                'label' => __('Text Color', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .iu-query-posts__link' => 'color: {{VALUE}};',
                ),
            ));

            $this->add_group_control(Group_Control_Background::get_type(), array(
                'name' => 'item_background',
                'exclude' => array('image'),
                'selector' => '{{WRAPPER}} .iu-query-posts__link',
            ));

            $this->add_group_control(Group_Control_Border::get_type(), array(
                'name' => 'item_border',
                'selector' => '{{WRAPPER}} .iu-query-posts__link',
            ));

            $this->add_group_control(Group_Control_Box_Shadow::get_type(), array(
                'name' => 'item_box_shadow',
                'selector' => '{{WRAPPER}} .iu-query-posts__link',
            ));

            $this->end_controls_tab();

            $this->start_controls_tab('tab_item_hover', array(
                'label' => __('Hover', 'istodata-utilities'),
            ));

            $this->add_control('title_hover_color', array(
                'label' => __('Text Color', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .iu-query-posts__link:hover, {{WRAPPER}} .iu-query-posts__link:focus-visible' => 'color: {{VALUE}};',
                ),
            ));

            $this->add_group_control(Group_Control_Background::get_type(), array(
                'name' => 'item_background_hover',
                'exclude' => array('image'),
                'selector' => '{{WRAPPER}} .iu-query-posts__link:hover, {{WRAPPER}} .iu-query-posts__link:focus-visible',
            ));

            $this->add_group_control(Group_Control_Border::get_type(), array(
                'name' => 'item_border_hover',
                'selector' => '{{WRAPPER}} .iu-query-posts__link:hover, {{WRAPPER}} .iu-query-posts__link:focus-visible',
            ));

            $this->add_group_control(Group_Control_Box_Shadow::get_type(), array(
                'name' => 'item_box_shadow_hover',
                'selector' => '{{WRAPPER}} .iu-query-posts__link:hover, {{WRAPPER}} .iu-query-posts__link:focus-visible',
            ));

            $this->end_controls_tab();

            $this->start_controls_tab('tab_item_active', array(
                'label' => __('Active', 'istodata-utilities'),
            ));

            $this->add_control('title_active_color', array(
                'label' => __('Text Color', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .iu-query-posts__link.is-current, {{WRAPPER}} .iu-query-posts__link[aria-current="page"]' => 'color: {{VALUE}};',
                ),
            ));

            $this->add_group_control(Group_Control_Background::get_type(), array(
                'name' => 'item_background_active',
                'exclude' => array('image'),
                'selector' => '{{WRAPPER}} .iu-query-posts__link.is-current, {{WRAPPER}} .iu-query-posts__link[aria-current="page"]',
            ));

            $this->add_group_control(Group_Control_Border::get_type(), array(
                'name' => 'item_border_active',
                'selector' => '{{WRAPPER}} .iu-query-posts__link.is-current, {{WRAPPER}} .iu-query-posts__link[aria-current="page"]',
            ));

            $this->add_group_control(Group_Control_Box_Shadow::get_type(), array(
                'name' => 'item_box_shadow_active',
                'selector' => '{{WRAPPER}} .iu-query-posts__link.is-current, {{WRAPPER}} .iu-query-posts__link[aria-current="page"]',
            ));

            $this->end_controls_tab();
            $this->end_controls_tabs();
            $this->end_controls_section();

            $this->start_controls_section('section_style_date', array(
                'label' => __('Date', 'istodata-utilities'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'show_date' => 'yes',
                ),
            ));

            $this->add_group_control(Group_Control_Typography::get_type(), array(
                'name' => 'date_typography',
                'selector' => '{{WRAPPER}} .iu-query-posts__date',
            ));

            $this->add_control('date_color', array(
                'label' => __('Color', 'istodata-utilities'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .iu-query-posts__date' => 'color: {{VALUE}};',
                ),
            ));

            $this->add_responsive_control('date_gap', array(
                'label' => __('Title / Date Gap', 'istodata-utilities'),
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
                    '{{WRAPPER}} .iu-query-posts__link' => '--iu-query-posts-date-gap: {{SIZE}}{{UNIT}};',
                ),
            ));

            $this->end_controls_section();
        }

        protected function render() {
            $settings = $this->get_settings_for_display();
            $post_type = isset($settings['post_type']) ? sanitize_key($settings['post_type']) : 'post';

            if (empty($post_type) || !post_type_exists($post_type)) {
                $this->render_editor_notice(__('Select a valid post type.', 'istodata-utilities'));
                return;
            }

            $orderby = !empty($settings['orderby']) ? sanitize_key($settings['orderby']) : 'date';
            $allowed_orderby = array('date', 'modified', 'title', 'menu_order', 'rand');
            if (!in_array($orderby, $allowed_orderby, true)) {
                $orderby = 'date';
            }

            $query_args = array(
                'post_type' => $post_type,
                'post_status' => 'publish',
                'posts_per_page' => isset($settings['posts_per_page']) ? max(1, min(50, absint($settings['posts_per_page']))) : 5,
                'orderby' => $orderby,
                'order' => (!empty($settings['order']) && strtoupper($settings['order']) === 'ASC') ? 'ASC' : 'DESC',
                'offset' => isset($settings['offset']) ? max(0, absint($settings['offset'])) : 0,
                'ignore_sticky_posts' => (!empty($settings['ignore_sticky_posts']) && $settings['ignore_sticky_posts'] === 'yes'),
                'no_found_rows' => true,
            );

            $tax_query = $this->build_tax_query(isset($settings['term_slugs']) ? $settings['term_slugs'] : '');
            if (!empty($tax_query)) {
                $query_args['tax_query'] = $tax_query;
            }

            if ($orderby === 'rand') {
                unset($query_args['order']);
            }

            $query = new \WP_Query($query_args);

            if (!$query->have_posts()) {
                $this->render_editor_notice(__('No posts were found for this query.', 'istodata-utilities'));
                return;
            }

            $current_id = (int) get_queried_object_id();
            $show_date = !empty($settings['show_date']) && $settings['show_date'] === 'yes';

            echo '<div class="iu-query-posts" role="list">';

            if ($this->should_render_extra_link($settings) && (!isset($settings['extra_link_position']) || $settings['extra_link_position'] === 'first')) {
                $this->render_extra_link($settings);
            }

            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                $attr_key = 'post_link_' . $post_id;

                $this->add_render_attribute($attr_key, 'class', 'iu-query-posts__link');
                $this->add_render_attribute($attr_key, 'href', esc_url(get_permalink()));
                if ($current_id === (int) $post_id) {
                    $this->add_render_attribute($attr_key, 'class', 'is-current');
                    $this->add_render_attribute($attr_key, 'aria-current', 'page');
                }

                echo '<article class="iu-query-posts__item" role="listitem">';
                echo '<a ' . $this->get_render_attribute_string($attr_key) . '>';
                echo '<span class="iu-query-posts__title">' . esc_html(get_the_title()) . '</span>';
                if ($show_date) {
                    echo '<time class="iu-query-posts__date" datetime="' . esc_attr(get_the_date(DATE_W3C)) . '">' . esc_html(get_the_date()) . '</time>';
                }
                echo '</a>';
                echo '</article>';
            }

            if ($this->should_render_extra_link($settings) && isset($settings['extra_link_position']) && $settings['extra_link_position'] === 'last') {
                $this->render_extra_link($settings);
            }

            echo '</div>';

            wp_reset_postdata();
        }

        private function get_post_type_options() {
            $options = array();
            $post_types = get_post_types(array(
                'public' => true,
            ), 'objects');
            $excluded = array('attachment', 'elementor_library', 'e-floating-buttons');

            foreach ($post_types as $post_type => $post_type_object) {
                if (in_array($post_type, $excluded, true)) {
                    continue;
                }

                if (isset($post_type_object->show_ui) && !$post_type_object->show_ui) {
                    continue;
                }

                $label = !empty($post_type_object->labels->singular_name)
                    ? $post_type_object->labels->singular_name
                    : $post_type_object->label;

                $options[$post_type] = sprintf('%s (%s)', $label, $post_type);
            }

            asort($options);

            if (empty($options)) {
                $options['post'] = __('Posts', 'istodata-utilities');
            }

            return $options;
        }

        private function get_term_slug_options() {
            $options = array();
            $taxonomies = get_taxonomies(array(
                'public' => true,
            ), 'names');
            $excluded = array('nav_menu', 'link_category', 'post_format');

            foreach ($taxonomies as $taxonomy) {
                if (in_array($taxonomy, $excluded, true)) {
                    continue;
                }

                $terms = get_terms(array(
                    'taxonomy' => $taxonomy,
                    'hide_empty' => false,
                ));

                if (is_wp_error($terms) || empty($terms)) {
                    continue;
                }

                foreach ($terms as $term) {
                    $options[$taxonomy . '|' . $term->slug] = sprintf('%s (%s) - %s', $term->name, $term->slug, $taxonomy);
                }
            }

            asort($options);

            return $options;
        }

        private function build_tax_query($value) {
            if (!is_array($value) || empty($value)) {
                return array();
            }

            $terms_by_taxonomy = array();
            foreach ($value as $term_value) {
                if (!is_string($term_value) || strpos($term_value, '|') === false) {
                    continue;
                }

                list($taxonomy, $slug) = array_map('trim', explode('|', $term_value, 2));
                $taxonomy = sanitize_key($taxonomy);
                $slug = sanitize_title($slug);

                if (!$taxonomy || !$slug || !taxonomy_exists($taxonomy)) {
                    continue;
                }

                if (!isset($terms_by_taxonomy[$taxonomy])) {
                    $terms_by_taxonomy[$taxonomy] = array();
                }

                $terms_by_taxonomy[$taxonomy][] = $slug;
            }

            if (empty($terms_by_taxonomy)) {
                return array();
            }

            $tax_query = array();
            if (count($terms_by_taxonomy) > 1) {
                $tax_query['relation'] = 'AND';
            }

            foreach ($terms_by_taxonomy as $taxonomy => $slugs) {
                $tax_query[] = array(
                    'taxonomy' => $taxonomy,
                    'field' => 'slug',
                    'terms' => array_values(array_unique($slugs)),
                );
            }

            return $tax_query;
        }

        private function should_render_extra_link($settings) {
            return !empty($settings['show_extra_link'])
                && $settings['show_extra_link'] === 'yes'
                && !empty($settings['extra_link_text'])
                && !empty($settings['extra_link_url']['url']);
        }

        private function render_extra_link($settings) {
            $this->add_render_attribute('extra_link', 'class', 'iu-query-posts__link iu-query-posts__link--extra');
            $this->add_link_attributes('extra_link', $settings['extra_link_url']);

            echo '<article class="iu-query-posts__item iu-query-posts__item--extra" role="listitem">';
            echo '<a ' . $this->get_render_attribute_string('extra_link') . '>';
            echo '<span class="iu-query-posts__title">' . esc_html($settings['extra_link_text']) . '</span>';
            echo '</a>';
            echo '</article>';
        }

        private function render_editor_notice($message) {
            if ($this->is_editor_mode()) {
                echo '<div style="padding:12px;background:#fff3cd;border:1px solid #ffe69c;color:#664d03;">' . esc_html($message) . '</div>';
            }
        }

        private function is_editor_mode() {
            return class_exists('Elementor\\Plugin')
                && \Elementor\Plugin::$instance
                && \Elementor\Plugin::$instance->editor
                && \Elementor\Plugin::$instance->editor->is_edit_mode();
        }
    }
}
