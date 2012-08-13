<?php
/**
 * @package ActiveHelper Live Help
 */

if (!defined('ACTIVEHELPER_LIVEHELP'))
	die('Hi there! I\'m just a plugin, not much I can do when called directly.');

function activeHelper_liveHelp_serverSettingsPost()
{
	global $wpdb, $activeHelper_liveHelp;
	
	if ( isset( $_GET['reset'] ) ) {
		activeHelper_liveHelp_resetSettings();

		wp_redirect('admin.php?page=' . strtolower('activeHelper_liveHelp_serverSettings') . '&update');
		exit;
	}

	$_POST['connection_timeout'] = !empty($_POST['connection_timeout']) ? (int) $_POST['connection_timeout'] : 60;
	$_POST['keep_alive_timeout'] = !empty($_POST['keep_alive_timeout']) ? (int) $_POST['keep_alive_timeout'] : 30;
	$_POST['guest_login_timeout'] = !empty($_POST['guest_login_timeout']) ? (int) $_POST['guest_login_timeout'] : 60;
	$_POST['chat_refresh_rate'] = !empty($_POST['chat_refresh_rate']) ? (int) $_POST['chat_refresh_rate'] : 6;
	$_POST['sound_alert_new_message'] = !empty($_POST['sound_alert_new_message']) ? (int) $_POST['sound_alert_new_message'] : 1;

	include($activeHelper_liveHelp['importDir'] . '/constants.php');
	if (!isset($_POST['submit']))
	{
		$_POST = array(
			'connection_timeout' => $connection_timeout,
			'keep_alive_timeout' => $keep_alive_timeout,
			'guest_login_timeout' => $guest_login_timeout,
			'chat_refresh_rate' => $chat_refresh_rate,
			'sound_alert_new_message' => $sound_alert_new_message
		);

		return;
	}

	// 
	$settingsFile = $activeHelper_liveHelp['importDir'] . '/constants.php';

	$fhandle = fopen($settingsFile, "r");
	$content = fread($fhandle, filesize($settingsFile));

	$content = str_replace('$connection_timeout = ' . $connection_timeout . ';',
		'$connection_timeout = ' . $_POST['connection_timeout'] . ';', $content);
	$content = str_replace('$keep_alive_timeout = ' . $keep_alive_timeout . ';',
		'$keep_alive_timeout = ' . $_POST['keep_alive_timeout'] . ';', $content);
	$content = str_replace('$guest_login_timeout= ' . $guest_login_timeout . ';',
		'$guest_login_timeout= ' . $_POST['guest_login_timeout'] . ';', $content);
	$content = str_replace('$chat_refresh_rate = ' . $chat_refresh_rate . ';',
		'$chat_refresh_rate = ' . $_POST['chat_refresh_rate'] . ';', $content);
	$content = str_replace('$sound_alert_new_message = ' . $sound_alert_new_message . ';',
		'$sound_alert_new_message = ' . $_POST['sound_alert_new_message'] . ';', $content);

	$fhandle = fopen($settingsFile, "w");
	fwrite($fhandle, $content);
	fclose($fhandle);

	wp_redirect('admin.php?page=' . strtolower('activeHelper_liveHelp_serverSettings') . '&update');
	exit;
}

function activeHelper_liveHelp_serverSettings()
{
	global $wpdb, $activeHelper_liveHelp;

	$tabindex = 1;

	echo '
<div class="wrap">
	<div id="icon-edit" class="icon32 icon32-posts-post"><br /></div>
	<h2>
		LiveHelp » ' . __('Server settings', 'activehelper_livehelp') . '
		<a class="button add-new-h2" href="admin.php?page=' . strtolower('activeHelper_liveHelp_serverSettings') . '&amp;reset">' . __('reset settings', 'activehelper_livehelp') . '</a>
	</h2>';

	if (isset($_GET['update']))
		echo '
	<div class="updated below-h2" id="message">
		<p>' . sprintf(__('The %s were successfully updated.', 'activehelper_livehelp'), __('server settings', 'activehelper_livehelp')) . '</p>
	</div>';

	echo '
	<form action="admin.php?page=' . strtolower('activeHelper_liveHelp_serverSettings') . '" method="post" accept-charset="utf-8" id="activeHelper_liveHelp_form" enctype="multipart/form-data">
		<div id="poststuff" class="metabox-holder has-right-sidebar">
			<div class="inner-sidebar"><div class="meta-box-sortables ui-sortable">
				<div id="submitdiv" class="postbox">
					<div class="handlediv" title="' . __('Click to toggle', 'activehelper_livehelp') . '"><br /></div>
					<h3 style="cursor: default;"><span style="cursor: default;">
						' . __('Update', 'activehelper_livehelp') . '</span></h3>
					<div class="inside"><div class="submitbox">
						<div id="major-publishing-actions" style="padding: 1ex;">
							<div id="publishing-action">
								<input name="submit" value="' . __('Update', 'activehelper_livehelp') . '" type="submit" accesskey="p" tabindex="999" class="button-primary">
							</div>
							<div class="clear"></div>
						</div>
						<div class="clear"></div>
					</div></div>
				</div>
			</div></div>
			<div id="post-body"><div id="post-body-content"><div class="meta-box-sortables ui-sortable">
				<div class="stuffbox postbox">
					<div class="handlediv" title="' . __('Click to toggle', 'activehelper_livehelp') . '"><br /></div>
					<h3 style="cursor: pointer;">' . __('Server settings', 'activehelper_livehelp') . '</h3>
					<div class="inside"><div id="postcustomstuff" style="padding: .6ex 0;">

						<table><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							<label for="connection_timeout">' . __('Connection Timeout (sec)', 'activehelper_livehelp') . '</label>
						</th></thead><tbody><tr><td id="newmetaleft" class="left">
							<input tabindex="' . $tabindex++ . '" maxlength="255" type="text" style="width: 96%;" value="' . $_POST['connection_timeout'] . '" id="connection_timeout" name="connection_timeout" />
						</td></tr></tbody></table>
						
						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							<label for="keep_alive_timeout">' . __('Keep Alive Timeout (sec)', 'activehelper_livehelp') . '</label>
						</th></thead><tbody><tr><td id="newmetaleft" class="left">
							<input tabindex="' . $tabindex++ . '" maxlength="255" type="text" style="width: 96%;" value="' . $_POST['keep_alive_timeout'] . '" id="keep_alive_timeout" name="keep_alive_timeout" />
						</td></tr></tbody></table>
						
						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							<label for="guest_login_timeout">' . __('Guest Login Timeout (sec)', 'activehelper_livehelp') . '</label>
						</th></thead><tbody><tr><td id="newmetaleft" class="left">
							<input tabindex="' . $tabindex++ . '" maxlength="255" type="text" style="width: 96%;" value="' . $_POST['guest_login_timeout'] . '" id="guest_login_timeout" name="guest_login_timeout" />
						</td></tr></tbody></table>
						
						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							<label for="chat_refresh_rate">' . __('Chat Refresh Rate (sec)', 'activehelper_livehelp') . '</label>
						</th></thead><tbody><tr><td id="newmetaleft" class="left">
							<input tabindex="' . $tabindex++ . '" maxlength="255" type="text" style="width: 96%;" value="' . $_POST['chat_refresh_rate'] . '" id="chat_refresh_rate" name="chat_refresh_rate" />
						</td></tr></tbody></table>
						
						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							<label for="sound_alert_new_message">' . __('Sound alert when a new message arrive', 'activehelper_livehelp') . '</label>
						</th></thead><tbody><tr><td id="newmetaleft" class="left">
							<select tabindex="' . $tabindex++ . '"  style="width: 150px;" id="sound_alert_new_message" name="sound_alert_new_message">
								<option value="1" ' . ( $_POST['sound_alert_new_message'] == '1' ? 'selected="selected"' : '' ) . '>' . __( 'Enable', 'activehelper_livehelp' ) . '</option>
								<option value="0" ' . ( $_POST['sound_alert_new_message'] == '0' ? 'selected="selected"' : '' ) . '>' . __( 'Disable', 'activehelper_livehelp' ) . '</option>
							</select>
						</td></tr></tbody></table>

					</div></div>
				</div>
			</div></div></div>
			<br />
		</div>';

	echo '
	</form>
	<script type="text/javascript">
		jQuery(document).ready(function($){
			$(".meta-box-sortables .postbox").each(function(){
				var postbox = $(this);
				$("h3", postbox).click(function(){
					$("div.inside", postbox).toggle();
				});
				$("div.handlediv", postbox).click(function(){
					$("div.inside", postbox).toggle();
				});
			});
		});
	</script>
</div>';
}

