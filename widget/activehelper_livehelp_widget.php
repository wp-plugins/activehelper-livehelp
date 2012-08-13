<?php
/**
 * @package ActiveHelper Live Help External Widget
 */
/*
Plugin Name: ActiveHelper Live Help External Widget
Plugin URI: http://www.activehelper.com
Description: Provide superior service by real time chat with your website visitors and interact them through your website. Create a more efficient connection with your website visitors, increase your sales and customer satisfaction.
Version: 1.0
Author: ActiveHelper Inc
Author URI: http://www.activehelper.com
*/

add_action('init', 'activeHelper_liveHelp_externalWidgetLanguages');
add_action('widgets_init', create_function('', 'return register_widget("activeHelper_liveHelp_externalWidget");'));

function activeHelper_liveHelp_externalWidgetLanguages()
{
	if (!defined('WP_PLUGIN_DIR'))
		load_plugin_textdomain('activehelper_livehelp_externalwidget', 'activehelper_livehelp_widget');
	else
		load_plugin_textdomain('activehelper_livehelp_externalwidget', false, 'activehelper_livehelp_widget');
}

class activeHelper_liveHelp_externalWidget extends WP_Widget
{
	function activeHelper_liveHelp_externalWidget()
	{
		parent::WP_Widget(
			strtolower('activeHelper_liveHelp_externalWidget'),
			$name = 'ActiveHelper Live Help External',
			array('description' => __('Use this widget to add your Live Help status on any sidebar.', 'activehelper_livehelp_externalwidget' ))
		);
	}

	function widget($args, $instance)
	{
		extract( $args );

		$title = apply_filters( 'widget_title', $instance['title'] );

		$instance['script_language'] = !empty($instance['script_language']) ? $instance['script_language'] : 'en';
		$instance['script_tracking'] = isset($instance['script_tracking']) ? $instance['script_tracking'] : 1;
		$instance['script_status'] = isset($instance['script_status']) ? $instance['script_status'] : 1;

		// pinrt widget
		echo $before_widget;

		if ($title)
			echo $before_title . $title . $after_title;

		echo '<script type="text/javascript" src="{liveHelp_externalWidget_serverUrl}/import/javascript.php"></script>
<script type="text/javascript">
	_vlDomain = {liveHelp_externalWidget_domain};
	_vlService = 1;
	_vlLanguage = "' . $instance['script_language'] . '";
	_vlTracking = ' . $instance['script_tracking'] . ';
	_vlStatus_indicator = ' . $instance['script_status'] . ';
	startLivehelp();
</script>';

		echo $after_widget;
	}

	function update( $new_instance, $old_instance )
	{
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['script_language'] = strip_tags($new_instance['script_language']);
		$instance['script_tracking'] = strip_tags($new_instance['script_tracking']);
		$instance['script_status'] = strip_tags($new_instance['script_status']);
		return $instance;
	}

	function form( $instance )
	{
		if ( $instance )
			$title = esc_attr( $instance[ 'title' ] );
		else
			$title = __( 'ActiveHelper Live Help External Widget', 'activehelper_livehelp_externalwidget' );

		$instance['script_language'] = !empty($instance['script_language']) ? $instance['script_language'] : 'en';
		$instance['script_tracking'] = isset($instance['script_tracking']) ? $instance['script_tracking'] : 1;
		$instance['script_status'] = isset($instance['script_status']) ? $instance['script_status'] : 1;

		echo '
		<p>
			<label for="' . $this->get_field_id( 'title' ) . '">' . __( 'Title', 'activehelper_livehelp_externalwidget' ) . ':</label> 
			<input class="widefat" id="' . $this->get_field_id( 'title' ) . '" name="' . $this->get_field_name('title') . '" type="text" value="' . $title . '" />
		</p>
		<p>
			<label for="' . $this->get_field_id( 'script_language' ) . '">' . __( 'Language', 'activehelper_livehelp_externalwidget' ) . ':</label> 
			<select class="widefat" style="width:100%;" id="' . $this->get_field_id( 'script_language' ) . '" name="' . $this->get_field_name('script_language') . '">';

		$__text = array(
			'en' => __('English', 'activehelper_livehelp_externalwidget'),
			'sp' => __('Spanish', 'activehelper_livehelp_externalwidget'),
			'de' => __('Deutsch', 'activehelper_livehelp_externalwidget'),
			'pt' => __('Portuguese', 'activehelper_livehelp_externalwidget'),
			'it' => __('Italian', 'activehelper_livehelp_externalwidget'),
			'fr' => __('French', 'activehelper_livehelp_externalwidget'),
			'cz' => __('Czech', 'activehelper_livehelp_externalwidget'),
			'se' => __('Swedish', 'activehelper_livehelp_externalwidget'),
			'no' => __('Norwegian', 'activehelper_livehelp_externalwidget'),
			'tr' => __('Turkey', 'activehelper_livehelp_externalwidget'),
			'gr' => __('Greek', 'activehelper_livehelp_externalwidget'),
			'he' => __('Hebrew', 'activehelper_livehelp_externalwidget'),
			'fa' => __('Farsi', 'activehelper_livehelp_externalwidget'),
			'sr' => __('Serbian', 'activehelper_livehelp_externalwidget'),
			'ru' => __('Rusian', 'activehelper_livehelp_externalwidget'),
			'hu' => __('Hungarian', 'activehelper_livehelp_externalwidget'),
			'zh' => __('Traditional Chinese', 'activehelper_livehelp_externalwidget'),
			'ar' => __('Arab', 'activehelper_livehelp_externalwidget'),
			'nl' => __('Dutch', 'activehelper_livehelp_externalwidget'),
			'fi' => __('Finnish', 'activehelper_livehelp_externalwidget'),
			'dk' => __('Danish', 'activehelper_livehelp_externalwidget'),
			'pl' => __('Polish', 'activehelper_livehelp_externalwidget'),
			'cn' => __('Simplified Chinese', 'activehelper_livehelp_externalwidget'),
            'bg' => __('Bulgarian', 'activehelper_livehelp_externalwidget'),
            'sk' => __('Slovak', 'activehelper_livehelp_externalwidget'),
            'cr' => __('Croatian', 'activehelper_livehelp_externalwidget')
		);

		foreach ($__text as $code => $name)
			echo '
				<option ' . ($code == $instance['script_language'] ? 'selected="selected"' : '') . ' value="' . $code . '">' . $name . '</option>';

		echo '
			</select>
		</p>
		<p>
			<label>' . __( 'Tracking', 'activehelper_livehelp_externalwidget' ) . ':</label> 
			<br /><label><input style="width: auto;" class="widefat" ' . ($instance['script_tracking'] == 1 ? 'checked="checked"' : '') . ' name="' . $this->get_field_name('script_tracking') . '" type="radio" value="1" /> ' . __( 'Enable', 'activehelper_livehelp_externalwidget' ) . '</label> 
			<label style="padding-left: 4px;"><input style="width: auto;" class="widefat" ' . ($instance['script_tracking'] == 0 ? 'checked="checked"' : '') . ' name="' . $this->get_field_name('script_tracking') . '" type="radio" value="0" /> ' . __( 'Disable', 'activehelper_livehelp_externalwidget' ) . '</label> 
		</p>
		<p>
			<label>' . __( 'Status indicator', 'activehelper_livehelp_externalwidget' ) . ':</label> 
			<br /><label><input style="width: auto;" class="widefat" ' . ($instance['script_status'] == 1 ? 'checked="checked"' : '') . ' name="' . $this->get_field_name('script_status') . '" type="radio" value="1" /> ' . __( 'Enable', 'activehelper_livehelp_externalwidget' ) . '</label> 
			<label style="padding-left: 4px;"><input style="width: auto;" class="widefat" ' . ($instance['script_status'] == 0 ? 'checked="checked"' : '') . ' name="' . $this->get_field_name('script_status') . '" type="radio" value="0" /> ' . __( 'Disable', 'activehelper_livehelp_externalwidget' ) . '</label> 
		</p>';
	}
}

