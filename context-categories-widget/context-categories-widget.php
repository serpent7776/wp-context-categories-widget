<?php

defined('ABSPATH') or die("No script kiddies please!");

/**
 * Plugin Name: context-categories-widget
 * Description: show widget with different categories depending on page user is viewng
 * Version: 1.0.0
 * Author: Serpent7776
 * License: 2-clause BSD
 */

/*
 * Copyright © 2015 Serpent7776. All Rights Reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *	1. Redistributions of source code must retain the above copyright
 *	   notice, this list of conditions and the following disclaimer.
 *	2. Redistributions in binary form must reproduce the above copyright
 *	   notice, this list of conditions and the following disclaimer in the
 *	   documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND
 * FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE AUTHOR
 * BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

add_action('widgets_init', 'context_category_widget_register');

function context_category_widget_register() {
    register_widget('context_category_widget_class');
}

class context_category_widget_class extends WP_Widget {

	public function __construct() {
		parent::__construct('context_category_widget', 'Context categories widget');
	}

	public function widget($args, $instance) {
		$title = (!empty($instance['title'])) ? apply_filters('widget_title', $instance['title']) : 'Categories';
		$max_depth = (!empty($instance['max_depth'])) ? $instance['max_depth'] : 1;
		$default_category = (!empty($instance['default_category'])) ? $instance['default_category'] : 0;
		$show_post_count = (!empty($instance['show_post_count'])) ? $instance['show_post_count'] : 1;
		$cat_id = 0;
		if (is_category() || is_single()) {
			if (is_category()) {
				$id = get_query_var('cat');
				$category = get_category($id);
			} else {
				$category = get_the_category();
				$category = $category[0];
			}
			while (isset($category) && isset($category->cat_ID)) {
				if ($category->category_parent == 0) {
					$cat_id = $category->cat_ID;
					break;
				}
				$category = get_category($category->category_parent);
			}
		}
		if ($cat_id == 0) {
			$cat_id = $default_category;
		}
		$opts_childs = array(
			'orderby' => 'term_group',
			'order' => 'ASC',
			'child_of' => $cat_id,
			'style' => 'list',
			'show_count' => $show_post_count,
			'hide_empty' => false,
			'hierarchical' => 1,
			'feed_image' => '',
			'title_li' => '',
			'depth' => $max_depth,
		);
		if ($cat_id != 0) {
			$opts_main = array(
				'orderby' => 'name',
				'order' => 'ASC',
				'include' => array($cat_id),
				'style' => 'list',
				'show_count' => 0,
				'hide_empty' => false,
				'hierarchical' => 1,
				'title_li' => '',
			);
			$cat_main = get_categories($opts_main);
			if (count($cat_main) > 0) {
				$current_cat = get_query_var('cat');
				if ($current_cat != $cat_id) {
					$link = get_category_link($cat_id);
					$title = '<a href="' . esc_url($link) . '" title="' . esc_attr($cat_main[0]->cat_name) . '">' . esc_html($cat_main[0]->cat_name) . '</a>';
				} else {
					$title = esc_html($cat_main[0]->cat_name);
				}
			}
		}
		//
		// generate widget code
		//
		echo $args['before_widget'];
		if (!empty($title)) {
			echo $args['before_title'] . $title . $args['after_title'];
		}
		echo '<ul>';
		wp_list_categories($opts_childs);
		echo '</ul>';
		echo $args['after_widget'];
	}

	public function form($instance) {
		$title = (!empty($instance['title'])) ? $instance['title'] : 'Categories';
		$max_depth = (!empty($instance['max_depth'])) ? $instance['max_depth'] : 1;
		$show_post_count = (isset($instance['show_post_count'])) ? (bool)$instance['show_post_count'] : true;
		$default_category = (isset($instance['default_category'])) ? $instance['default_category'] : 0;
		$categories = get_categories(array('type' => 'post', 'hide_empty' => 1, 'orderby' => 'name', 'order' => 'ASC', 'taxonomy' => 'category'));
?>
<p>
	<label><span>Title:</span>
	<input type="text" id="<?php echo $this->get_field_id('title') ?>" name="<?php echo $this->get_field_name('title') ?>" class="widefat" value="<?php echo esc_attr($title) ?>">
	</label>
</p>
<p>
	<label><span>Max depth:</span>
	<input type="number" required id="<?php echo $this->get_field_id('max_depth') ?>" name="<?php echo $this->get_field_name('max_depth') ?>" class="widefat" value="<?php echo esc_attr($max_depth) ?>">
	</label>
</p>
<p>
	<label><span>Default category:</span>
	<select id="<?php echo $this->get_field_id('default_category'); ?>" name="<?php echo $this->get_field_name('default_category'); ?>">
		<option value="0"<?php if($default_category == 0) { ?> selected="selected"<?php } ?>>Top categories</option>
		<?php foreach ($categories as $cat) { ?>
		<option value="<?php echo $cat->term_id ?>"<?php if ($default_category == $cat->term_id) { ?> selected="selected"<?php } ?>><?php echo $cat->name ?></option>
		<?php } ?>
	</select>
	</label>
</p>
<p>
	<label>
	<input type="checkbox" id="<?php echo $this->get_field_id('show_post_count') ?>" name="<?php echo $this->get_field_name('show_post_count') ?>" class="widefat" value="<?php echo "1" ?>" <?php if ($show_post_count == true) {echo 'checked="checked"';} ?>>
	<span>Show post count:</span>
	</label>
</p>
<?php
	}

	public function update($new_instance, $old_instance) {
		$instance = array();
		$instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
		$instance['max_depth'] = (!empty($new_instance['max_depth']) && is_numeric($new_instance['max_depth'])) ? $new_instance['max_depth'] : 1;
		$instance['default_category'] = (!empty($new_instance['default_category'])) ? $new_instance['default_category'] : 0;
		$instance['show_post_count'] = (!empty($new_instance['show_post_count']) && is_numeric($new_instance['show_post_count'])) ? (bool)$new_instance['show_post_count'] : false;
		return $instance;
	}

}
