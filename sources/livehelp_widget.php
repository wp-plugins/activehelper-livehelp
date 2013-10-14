<?php
/**
 * @package ActiveHelper Live Help
 * @version   : 2.9.5
 * @author    : ActiveHelper Inc.
 * @copyright : (C) 2013- ActiveHelper Inc.
 * @license   : GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

if (!defined('ACTIVEHELPER_LIVEHELP'))
	die('Hi there! I\'m just a plugin, not much I can do when called directly.');

add_action('widgets_init', create_function('', 'return register_widget("activeHelper_liveHelp_widget");'));

class activeHelper_liveHelp_widget extends WP_Widget
{
	function activeHelper_liveHelp_widget()
	{
		parent::WP_Widget(
			strtolower('activeHelper_liveHelp_widget'),
			$name = 'ActiveHelper LiveHelp',
			array('description' => __('Use this widget to add your LiveHelp status on any sidebar.', 'activehelper_livehelp' ))
		);
	}

	function widget($args, $instance)
	{
		global $wpdb, $activeHelper_liveHelp;

		extract( $args );

		$title = apply_filters( 'widget_title', $instance['title'] );

		$domains = $wpdb->get_results("
			SELECT name, id_domain
			FROM {$wpdb->prefix}livehelp_domains
			WHERE status = 1
			ORDER BY id_domain
		", ARRAY_A);

		$defaultDomain = 0;
		foreach ($domains as $domain)
			if (strstr(get_bloginfo('url'), '//' . $domain['name']))
			{
				$defaultDomain = $domain['id_domain'];
				$defaultDomainName = $domain['name'];
				break;
			}

		if (empty($defaultDomain))
			return;

		$instance['script_domain'] = !empty($instance['script_domain']) ? $instance['script_domain'] : $defaultDomain;
		$instance['script_agent'] = !empty($instance['script_agent']) ? $instance['script_agent'] : 0;
		$instance['script_language'] = !empty($instance['script_language']) ? $instance['script_language'] : 'en';
		$instance['script_tracking'] = isset($instance['script_tracking']) ? $instance['script_tracking'] : 1;
		$instance['script_status'] = isset($instance['script_status']) ? $instance['script_status'] : 1;

		// pinrt widget
		echo $before_widget;

		if ($title)
			echo $before_title . $title . $after_title;

		echo '<script type="text/javascript" src="' . $activeHelper_liveHelp['serverUrl'] . '/import/javascript.php"></script>
<script type="text/javascript">
	_vlDomain = ' . $instance['script_domain'] . ';
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
		$instance['script_domain'] = strip_tags($new_instance['script_domain']);
		$instance['script_agent'] = strip_tags($new_instance['script_agent']);
		$instance['script_language'] = strip_tags($new_instance['script_language']);
		$instance['script_tracking'] = strip_tags($new_instance['script_tracking']);
		$instance['script_status'] = strip_tags($new_instance['script_status']);
		return $instance;
	}

	function form( $instance )
	{
		global $wpdb;

		if ( $instance )
			$title = esc_attr( $instance[ 'title' ] );
		else
			$title = __( 'ActiveHelper LiveHelp Widget', 'activehelper_livehelp' );

		$domains = $wpdb->get_results("
			SELECT name, id_domain
			FROM {$wpdb->prefix}livehelp_domains
			WHERE status = 1
			ORDER BY id_domain
		", ARRAY_A);

		$defaultDomain = 0;
		foreach ($domains as $domain)
			if (strstr(get_bloginfo('url'), '//' . $domain['name']))
			{
				$defaultDomain = $domain['id_domain'];
				$defaultDomainName = $domain['name'];
				break;
			}
			
		$domainName = parse_url(get_bloginfo('url'));
		$domainName = $domainName['host'];

		$instance['script_domain'] = !empty($instance['script_domain']) ? $instance['script_domain'] : $defaultDomain;
		$instance['script_agent'] = !empty($instance['script_agent']) ? $instance['script_agent'] : 0;
		$instance['script_language'] = !empty($instance['script_language']) ? $instance['script_language'] : 'en';
		$instance['script_tracking'] = isset($instance['script_tracking']) ? $instance['script_tracking'] : 1;
		$instance['script_status'] = isset($instance['script_status']) ? $instance['script_status'] : 1;

		echo '
		<p>
			<label for="' . $this->get_field_id( 'title' ) . '">' . __( 'Title', 'activehelper_livehelp' ) . ':</label> 
			<input class="widefat" id="' . $this->get_field_id( 'title' ) . '" name="' . $this->get_field_name('title') . '" type="text" value="' . $title . '" />
		</p>';

		if (!empty($defaultDomain))
			echo '
		<p>
			<label>' . __( 'Domain', 'activehelper_livehelp' ) . ':</label>
			<br />' . $defaultDomainName . '
		</p>';
		else
			echo '
		<p>
			<label>' . __( 'Domain', 'activehelper_livehelp' ) . ':</label>
			<br /><span style="color: #f00;">' . sprintf(__( 'You must register %s to use this widget', 'activehelper_livehelp' ), $domainName) . '</span>
		</p>';

		echo '
		<p>
			<label for="' . $this->get_field_id( 'script_agent' ) . '">' . __( 'Agent', 'activehelper_livehelp' ) . ':</label> 
			<input class="widefat" id="' . $this->get_field_id( 'script_agent' ) . '" name="' . $this->get_field_name('script_agent') . '" type="text" value="' . $instance['script_agent'] . '" />
		</p>';

		/*
		echo '
		<p>
			<label for="' . $this->get_field_id( 'script_domain' ) . '">' . __( 'Domain', 'activehelper_livehelp' ) . ':</label> 
			<select class="widefat" style="width:100%;" id="' . $this->get_field_id( 'script_domain' ) . '" name="' . $this->get_field_name('script_domain') . '">';

		foreach ($domains as $domain)
			echo '
				<option ' . ($domain['id_domain'] == $instance['script_domain'] ? 'selected="selected"' : '') . ' value="' . $domain['id_domain'] . '">' . $domain['name'] . '</option>';

		echo '
			</select>
		</p>';
		*/

		echo '
		<p>
			<label for="' . $this->get_field_id( 'script_language' ) . '">' . __( 'Language', 'activehelper_livehelp' ) . ':</label> 
			<select class="widefat" style="width:100%;" id="' . $this->get_field_id( 'script_language' ) . '" name="' . $this->get_field_name('script_language') . '">';

		$__text = array(
			'en' => __('English', 'activehelper_livehelp'),
			'sp' => __('Spanish', 'activehelper_livehelp'),
			'de' => __('Deutsch', 'activehelper_livehelp'),
			'pt' => __('Portuguese', 'activehelper_livehelp'),
			'it' => __('Italian', 'activehelper_livehelp'),
			'fr' => __('French', 'activehelper_livehelp'),
			'cz' => __('Czech', 'activehelper_livehelp'),
			'se' => __('Swedish', 'activehelper_livehelp'),
			'no' => __('Norwegian', 'activehelper_livehelp'),
			'tr' => __('Turkey', 'activehelper_livehelp'),
			'gr' => __('Greek', 'activehelper_livehelp'),
			'he' => __('Hebrew', 'activehelper_livehelp'),
			'fa' => __('Farsi', 'activehelper_livehelp'),
			'sr' => __('Serbian', 'activehelper_livehelp'),
			'ru' => __('Rusian', 'activehelper_livehelp'),
			'hu' => __('Hungarian', 'activehelper_livehelp'),
			'zh' => __('Traditional Chinese', 'activehelper_livehelp'),
			'ar' => __('Arab', 'activehelper_livehelp'),
			'nl' => __('Dutch', 'activehelper_livehelp'),
			'fi' => __('Finnish', 'activehelper_livehelp'),
			'dk' => __('Danish', 'activehelper_livehelp'),
			'pl' => __('Polish', 'activehelper_livehelp'),
			'cn' => __('Simplified Chinese', 'activehelper_livehelp'),
            'bg' => __('Bulgarian', 'activehelper_livehelp'),
            'sk' => __('Slovak', 'activehelper_livehelp'),
            'cr' => __('Croatian', 'activehelper_livehelp'),
            'id' => __('Indonesian', 'activehelper_livehelp'),
            'lt' => __('Lithuanian', 'activehelper_livehelp'), 
            'ro' => __('Romanian', 'activehelper_livehelp'),
			'sl' => __('Slovenian', 'activehelper_livehelp'),
			'et' => __('Estonian', 'activehelper_livehelp'),
            'lv' => __('Latvian', 'activehelper_livehelp')
		);

		foreach ($__text as $code => $name)
			echo '
				<option ' . ($code == $instance['script_language'] ? 'selected="selected"' : '') . ' value="' . $code . '">' . $name . '</option>';

		echo '
			</select>
		</p>
		<p>
			<label>' . __( 'Tracking', 'activehelper_livehelp' ) . ':</label> 
			<br /><label><input style="width: auto;" class="widefat" ' . ($instance['script_tracking'] == 1 ? 'checked="checked"' : '') . ' name="' . $this->get_field_name('script_tracking') . '" type="radio" value="1" /> ' . __( 'Enable', 'activehelper_livehelp' ) . '</label> 
			<label style="padding-left: 4px;"><input style="width: auto;" class="widefat" ' . ($instance['script_tracking'] == 0 ? 'checked="checked"' : '') . ' name="' . $this->get_field_name('script_tracking') . '" type="radio" value="0" /> ' . __( 'Disable', 'activehelper_livehelp' ) . '</label> 
		</p>
		<p>
			<label>' . __( 'Status indicator', 'activehelper_livehelp' ) . ':</label> 
			<br /><label><input style="width: auto;" class="widefat" ' . ($instance['script_status'] == 1 ? 'checked="checked"' : '') . ' name="' . $this->get_field_name('script_status') . '" type="radio" value="1" /> ' . __( 'Enable', 'activehelper_livehelp' ) . '</label> 
			<label style="padding-left: 4px;"><input style="width: auto;" class="widefat" ' . ($instance['script_status'] == 0 ? 'checked="checked"' : '') . ' name="' . $this->get_field_name('script_status') . '" type="radio" value="0" /> ' . __( 'Disable', 'activehelper_livehelp' ) . '</label> 
		</p>';
	}
}

