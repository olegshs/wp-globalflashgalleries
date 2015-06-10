<?php

class flgalleryWidget extends WP_Widget
{
	function flgalleryWidget()
	{
		$this->WP_Widget(
			false,
			'Flash Gallery',
			array(
				'description' => 'Global Flash Galleries'
			),
			array(
			)
		);
	}

	function widget( $args, $instance )
	{
		include FLGALLERY_GLOBALS;

		extract($args);
		$title = apply_filters('widget_title', $instance['title']);
		$gallery_id = (int)$instance['gallery'];

		echo $before_widget;

		if ( !empty($title) )
			echo $before_title. $title. $after_title;

		if ( !empty($gallery_id) )
		{
			$a = array(
				'id' => $gallery_id,
				'popup' => true
			);
			if ($instance['show'] == 'preview')
				$a['preview'] = $instance['preview'];
			else
				$a['text'] = $instance['text'];

			echo
				'<div class="textwidget">'.
					$plugin->flashGallery($a).
				'</div>';
		}

		echo $after_widget;
	}

	function update( $new_instance, $old_instance )
	{
		if ( empty($new_instance['text']) )
			$new_instance['text'] = __('To view the gallery in a popup window, #click here#.');

		return $new_instance;
	}

	function form( $instance )
	{
		include FLGALLERY_GLOBALS;

		$title = isset($instance['title']) ? esc_attr($instance['title']) : '';
		$selectedGallery = isset($instance['gallery']) ? esc_attr($instance['gallery']) : '';
		$show = isset($instance['show']) ? esc_attr($instance['show']) : '';
		$text = isset($instance['text']) ? esc_attr($instance['text']) : '';
		$preview = isset($instance['preview']) ? esc_attr($instance['preview']) : '';
?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
			<input type="text" class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo $title; ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('gallery'); ?>"><?php _e('Gallery:'); ?></label>
			<select class="widefat" id="<?php echo $this->get_field_id('gallery'); ?>" name="<?php echo $this->get_field_name('gallery'); ?>">
<?php
				$galleries = $wpdb->get_results("
					SELECT *
					FROM `{$plugin->dbGalleries}`
				");
				foreach ($galleries as $gallery)
				{
?>
					<option value="<?php echo $gallery->id; ?>"<?php if ($gallery->id == $selectedGallery) echo ' selected="selected"' ?>><?php echo esc_html(stripslashes($gallery->name)); ?></option>
<?php
				}
?>
			</select>
		</p>
		<p id="<?php echo $this->get_field_id('show'); ?>-field">
			<label for="<?php echo $this->get_field_id('show'); ?>"><?php _e('Show:'); ?></label><br />
			<label style="white-space:nowrap; margin-right:1em;">
				<input type="radio" class="radio" id="<?php echo $this->get_field_id('show'); ?>" name="<?php echo $this->get_field_name('show'); ?>" value="text"<?php if ($show != 'preview') echo ' checked="checked"'; ?> />
				<?php _e('Text link'); ?>
			</label>
			<label style="white-space:nowrap;">
				<input type="radio" class="radio" name="<?php echo $this->get_field_name('show'); ?>" value="preview"<?php if ($show == 'preview') echo ' checked="checked"'; ?> />
				<?php _e('Preview image'); ?>
			</label>
		</p>
		<p id="<?php echo $this->get_field_id('text'); ?>-field">
			<label for="<?php echo $this->get_field_id('text'); ?>"><?php _e('Text:'); ?></label><br />
			<textarea class="widefat" name="<?php echo $this->get_field_name('text'); ?>"><?php echo $text; ?></textarea>
		</p>
		<p id="<?php echo $this->get_field_id('preview'); ?>-field">
			<label for="<?php echo $this->get_field_id('preview'); ?>"><?php _e('Image URL:'); ?></label><br />
			<input type="text" class="widefat" id="<?php echo $this->get_field_id('preview'); ?>" name="<?php echo $this->get_field_name('preview'); ?>" value="<?php echo $preview; ?>" />
		</p>
		<script type="text/javascript">//<![CDATA[
		jQuery(document).ready(function($) {
			$("#<?php echo $this->get_field_id('show'); ?>-field input.radio").click(function() {
				if (this.value == 'preview') {
					$("#<?php echo $this->get_field_id('text'); ?>-field").hide();
					$("#<?php echo $this->get_field_id('preview'); ?>-field").show();
				}
				else {
					$("#<?php echo $this->get_field_id('preview'); ?>-field").hide();
					$("#<?php echo $this->get_field_id('text'); ?>-field").show();
				}
			});
			$("#<?php echo $this->get_field_id('show'); ?>-field input.radio[checked]").click();
		});
		//]]></script>
<?php
	}

}

?>