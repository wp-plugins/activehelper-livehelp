<?php
/**
 * @package ActiveHelper Live Help External Widget
 * @version   : 3.6
 * @author    : ActiveHelper Inc.
 * @copyright : (C) 2014- ActiveHelper Inc.
 * @license   : GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */
/*
Plugin Name: ActiveHelper Live Help External Widget
Plugin URI: http://www.activehelper.com
Description: WordPress Live Chat widget for the ActiveHelper LiveHelp Server. Displays the chat button in your website.  
Version: 2.0
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

		$instance['script_agent'] = !empty($instance['script_agent']) ? $instance['script_agent'] : 0;
		$instance['script_language'] = !empty($instance['script_language']) ? $instance['script_language'] : 'en';
		$instance['script_tracking'] = isset($instance['script_tracking']) ? $instance['script_tracking'] : 1;
		$instance['script_status'] = isset($instance['script_status']) ? $instance['script_status'] : 1;
        $instance['script_footer'] = !empty($instance['script_footer']) ? $instance['script_footer'] : 0;

		// pinrt widget
		echo $before_widget;

		if ($title)
			echo $before_title . $title . $after_title;
            
       if ($instance['script_footer'] ==1)
          echo '<p class="pin"><span style="font-size: 10pt;"><div style="position: fixed; bottom: 0px; right:0px; z-index:999999999999; display:block;"> ';
         
       if ($instance['script_footer'] ==2)
      	  echo '<p class="pin"><span style="font-size: 10pt;"><div style="position: fixed; center: 0px; right:0px; z-index:999999999999; display:block;"> ';              

       if ($instance['script_footer'] ==3)
      	  echo '<p class="pin"><span style="font-size: 10pt;"><div style="position: fixed; top: 0px; right:0px; z-index:999999999999; display:block;"> '; 

       if ($instance['script_footer'] ==4)
      	  echo '<p class="pin"><span style="font-size: 10pt;"><div style="position: fixed; bottom: 0px; center:0px; z-index:999999999999; display:block;"> '; 

       if ($instance['script_footer'] ==5)
      	  echo '<p class="pin"><span style="font-size: 10pt;"><div style="position: fixed; top: 0px; center:0px; z-index:999999999999; display:block;"> '; 

       if ($instance['script_footer'] ==6)
      	  echo '<p class="pin"><span style="font-size: 10pt;"><div style="position: fixed; bottom: 0px; left:0px; z-index:999999999999; display:block;"> '; 

       if ($instance['script_footer'] ==7)
      	  echo '<p class="pin"><span style="font-size: 10pt;"><div style="position: fixed; Center: 0px; left:0px; z-index:999999999999; display:block;"> '; 

       if ($instance['script_footer'] ==8)
      	  echo '<p class="pin"><span style="font-size: 10pt;"><div style="position: fixed; top: 0px; left:0px; z-index:999999999999; display:block;"> '; 
   

		echo '<script type="text/javascript" src="{liveHelp_externalWidget_serverUrl}/import/javascript.php"></script>
<script type="text/javascript">
	_vlDomain = {liveHelp_externalWidget_domain};
	_vlAgent = ' . $instance['script_agent'] . ';
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
		$instance['script_agent'] = strip_tags($new_instance['script_agent']);
		$instance['script_language'] = strip_tags($new_instance['script_language']);
		$instance['script_tracking'] = strip_tags($new_instance['script_tracking']);
		$instance['script_status'] = strip_tags($new_instance['script_status']);
        $instance['script_footer'] = strip_tags($new_instance['script_footer']);
		return $instance;
	}

	function form( $instance )
	{
		if ( $instance )
			$title = esc_attr( $instance[ 'title' ] );
		else
			$title = __( 'ActiveHelper Live Help External Widget', 'activehelper_livehelp_externalwidget' );

		$instance['script_agent'] = !empty($instance['script_agent']) ? $instance['script_agent'] : 0;
		$instance['script_language'] = !empty($instance['script_language']) ? $instance['script_language'] : 'en';
		$instance['script_tracking'] = isset($instance['script_tracking']) ? $instance['script_tracking'] : 1;
		$instance['script_status'] = isset($instance['script_status']) ? $instance['script_status'] : 1;
        $instance['script_footer'] = isset($instance['script_footer']) ? $instance['script_footer'] : 0;

		echo '
		<p>
			<label for="' . $this->get_field_id( 'title' ) . '">' . __( 'Title', 'activehelper_livehelp_externalwidget' ) . ':</label> 
			<input class="widefat" id="' . $this->get_field_id( 'title' ) . '" name="' . $this->get_field_name('title') . '" type="text" value="' . $title . '" />
		</p>
		<p>
			<label for="' . $this->get_field_id( 'script_agent' ) . '">' . __( 'Agent', 'activehelper_livehelp' ) . ':</label> 
			<input class="widefat" id="' . $this->get_field_id( 'script_agent' ) . '" name="' . $this->get_field_name('script_agent') . '" type="text" value="' . $instance['script_agent'] . '" />
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
            'cr' => __('Croatian', 'activehelper_livehelp_externalwidget'),
            'id' => __('Indonesian', 'activehelper_livehelp_externalwidget'),
            'lt' => __('Lithuanian', 'activehelper_livehelp_externalwidget'),
            'ro' => __('Romanian', 'activehelper_livehelp_externalwidget'),
			'sl' => __('Slovenian', 'activehelper_livehelp'),
			'et' => __('Estonian', 'activehelper_livehelp'),
			'lv' => __('Latvian', 'activehelper_livehelp'),
            'ge' => __('Georgian', 'activehelper_livehelp')
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
		</p>
         <p>
			<label for="' . $this->get_field_id( 'script_footer' ) . '">' . __( ' Absolute Position', 'activehelper_livehelp_externalwidget' ) . ':</label> 
			<select class="widefat" style="width:100%;" id="' . $this->get_field_id( 'script_footer' ) . '" name="' . $this->get_field_name('script_footer') . '">';

		$__text = array(
			0 => __('None', 'activehelper_livehelp_externalwidget'),
			1 => __('Right_Bottom', 'activehelper_livehelp_externalwidget'),
			2 => __('Right_Center', 'activehelper_livehelp_externalwidget'),
            3 => __('Right_Top', 'activehelper_livehelp_externalwidget'),
            4 => __('Center_Bottom', 'activehelper_livehelp_externalwidget'),
            5 => __('Center_Top', 'activehelper_livehelp_externalwidget'),
            6 => __('Left_Bottom', 'activehelper_livehelp_externalwidget'),
            7 => __('Left_Center', 'activehelper_livehelp_externalwidget'),
            8 => __('Left_Top', 'activehelper_livehelp_externalwidget')                          
		);

		foreach ($__text as $code => $name)
			echo '
				<option ' . ($code == $instance['script_footer'] ? 'selected="selected"' : '') . ' value="' . $code . '">' . $name . '</option>';

		echo '
			</select>
            	</p>';                
	}
}

