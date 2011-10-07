<?php
/**
 * @package ActiveHelper Live Help
 */

if (!defined('ACTIVEHELPER_LIVEHELP'))
	die('Hi there! I\'m just a plugin, not much I can do when called directly.');

function activeHelper_liveHelp_timeByChat()
{
	global $wpdb, $activeHelper_liveHelp;

	$actions = array(
		'list' => 'activeHelper_liveHelp_timeByChatList',
		'view' => 'activeHelper_liveHelp_timeByChatView',
		'email' => 'activeHelper_liveHelp_timeByChatEmail'
	);
	if (!empty($_REQUEST['action']) && isset($actions[$_REQUEST['action']]))
		return $actions[$_REQUEST['action']]();

	return $actions['list']();
}

function activeHelper_liveHelp_timeByChatPost()
{
	$actions = array(
		'export' => 'activeHelper_liveHelp_timeByChatExportPost',
		'delete' => 'activeHelper_liveHelp_timeByChatDeletePost',
		'list' => 'activeHelper_liveHelp_timeByChatListPost',
		'view' => 'activeHelper_liveHelp_timeByChatViewPost',
		'viewExport' => 'activeHelper_liveHelp_timeByChatViewExportPost',
		'email' => 'activeHelper_liveHelp_timeByChatEmailPost'
	);

	if (!empty($_REQUEST['action']) && isset($actions[$_REQUEST['action']]))
		return $actions[$_REQUEST['action']]();

	if (empty($_REQUEST['action']))
		return $actions['list']();
}

function activeHelper_liveHelp_timeByChatEmailPost()
{
	global $wpdb, $activeHelper_liveHelp;

	$_REQUEST['id'] = !empty($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
	$_POST['email_subject'] = !empty($_POST['email_subject']) ? (string) $_POST['email_subject'] : '';
	$_POST['email_from_name'] = !empty($_POST['email_from_name']) ? (string) $_POST['email_from_name'] : '';
	$_POST['email_from'] = !empty($_POST['email_from']) ? (string) $_POST['email_from'] : '';
	$_POST['email_to'] = !empty($_POST['email_to']) ? (string) $_POST['email_to'] : '';

	if (empty($_REQUEST['id']))
	{
		wp_redirect('admin.php?page=' . strtolower('activeHelper_liveHelp_timeByChat') . '&miss');
		exit;
	}

	$_POST['content'] = !empty($_POST['content']) ? (string) $_POST['content'] : '';

	if (!isset($_POST['submit']))
	{
		$_POST['content'] = '';

		$statistic = $wpdb->get_row("
			SELECT
				CONCAT(jlu.firstname, ' ', jlu.lastname) AS agent, jls.department, jls.server,
				jls.username, jls.email, DATE_FORMAT(jls.datetime, '%Y-%m-%d') AS date,
				if(jls.rating = -1, '" . __('Not rate', 'activehelper_livehelp') . "', jls.rating) AS rating,
				jlr.country, jlr.city
			FROM
				{$wpdb->prefix}livehelp_sessions AS jls,
				{$wpdb->prefix}livehelp_users AS jlu,
				{$wpdb->prefix}livehelp_requests AS jlr
			WHERE jls.id = '{$_REQUEST['id']}'
				AND jls.id_user = jlu.id
				AND jls.request = jlr.id
		", ARRAY_A);

		if (!empty($statistic))
		{
			$_POST['content'] = '<p><strong>' . __('Statistic', 'activehelper_livehelp') . '</strong></p>' . "\n";

			$_POST['content'] .= '<p style="padding-left: 30px;"><strong>Domain:</strong> ' . $statistic['server'] . "\n" .
				'<strong>Department:</strong> ' . $statistic['department'] . "\n" .
				'<strong>Agent:</strong> ' . $statistic['agent'] . "\n" .
				'<strong>Visitor:</strong> ' . $statistic['username'] . "\n" .
				'<strong>Country:</strong> ' . $statistic['country'] . "\n" .
				'<strong>City:</strong> ' . $statistic['city'] . "\n" .
				'<strong>Email:</strong> ' . $statistic['email'] . "\n" .
				'<strong>Date:</strong> ' . $statistic['date'] . "\n" .
				'<strong>Rating:</strong> ' . $statistic['rating'] . "</p>\n";
		}

		$chats = $wpdb->get_results("
			SELECT username, message, TIME_FORMAT(jlm.datetime, '%H:%i:%s') AS time
			FROM {$wpdb->prefix}livehelp_messages AS jlm
			WHERE session = '{$_REQUEST['id']}'
			ORDER BY id
		", ARRAY_A);

		if (!empty($chats))
		{
			$_POST['content'] .= '<p><strong>' . __('Full chat', 'activehelper_livehelp') . '</strong></p>' . "\n";
			$_POST['content'] .= '<p style="padding-left: 30px;">';

			foreach ($chats as $chat)
				$_POST['content'] .= '[' . $chat['time'] . '] <strong>' .
					$chat['username'] . ':</strong> ' . $chat['message'] . "\n";

			$_POST['content'] .= '</p>';
		}

		$session = $wpdb->get_row("
			SELECT ls.email, ls.username, lu.firstname, lu.lastname, DATE_FORMAT(ls.datetime, '%Y-%m-%d') AS datetime
			FROM {$wpdb->prefix}livehelp_sessions AS ls
				LEFT JOIN {$wpdb->prefix}livehelp_users AS lu ON(lu.id = ls.id_user)
			WHERE ls.id = '{$_REQUEST['id']}'
			LIMIT 1
		", ARRAY_A);

		if (!empty($session))
		{
			if (!empty($session['lastname']))
				$session['firstname'] = $session['firstname'] . ' ' . $session['lastname'];

			$_POST['email_subject'] = 'LiveHelp - Time by chat: Within ' . $session['username'] . ' and ' . $session['firstname'] . ' on ' . $session['datetime'];
			$_POST['email_from_name'] = $session['username'];
			$_POST['email_from'] = $session['email'];
		}
	}

	$errors = array();
	$activeHelper_liveHelp['errors'] = &$errors;

	$_POST['content'] = stripcslashes($_POST['content']);

	while (isset($_POST['submit']))
	{
		unset($_POST['submit']);

		if (empty($_POST['email_subject']))
			$errors['email_subject'] = sprintf(__('You must insert a %s', 'activehelper_livehelp'), __('subject', 'activehelper_livehelp')); // error

		if (empty($_POST['email_to']))
			$errors['email_to'] = sprintf(__('You must insert an %s', 'activehelper_livehelp'), __('email', 'activehelper_livehelp')); // error

		if (empty($_POST['email_from']))
			$errors['email_from'] = sprintf(__('You must insert an %s', 'activehelper_livehelp'), __('email', 'activehelper_livehelp')); // error

		// errors ...
		if (!empty($errors))
			break;

		if (!preg_match("/^[\_]*([a-z0-9]+(\.|\_*)?)+@([a-z][a-z0-9\-]+(\.|\-*\.))+[a-z]{2,6}$/", $_POST['email_to']))
			$errors['email_to'] = sprintf(__('You must insert a %s', 'activehelper_livehelp'), __('valid email', 'activehelper_livehelp')); // error

		if (!preg_match("/^[\_]*([a-z0-9]+(\.|\_*)?)+@([a-z][a-z0-9\-]+(\.|\-*\.))+[a-z]{2,6}$/", $_POST['email_from']))
			$errors['email_from'] = sprintf(__('You must insert a %s', 'activehelper_livehelp'), __('valid email', 'activehelper_livehelp')); // error

		// errors ...
		if (!empty($errors))
			break;

		if (!empty($_POST['email_from_name']))
			$_POST['email_from'] = $_POST['email_from_name'] . '<' . $_POST['email_from'] . '>';

		$to      = $_POST['email_to'];
		$subject = $_POST['email_subject'];
		$message = apply_filters('the_content', $_POST['content']);

		$headers = 'From: ' . $_POST['email_from'] . '' . "\r\n" .
			'Reply-To: ' . $_POST['email_from'] . '' . "\r\n" .
			'Content-type: text/html; charset=utf-8' . "\r\n" .
			'X-Mailer: PHP/' . phpversion();

		wp_mail($to, $subject, $message, $headers);
		
		wp_redirect('admin.php?page=' . strtolower('activeHelper_liveHelp_timeByChat') . '&action=email&id=' . $_REQUEST['id'] . '&email');
		exit;
	}

	add_action('wp_enqueue_scripts', 'activeHelper_liveHelp_timeByChatEmailEditor');
	add_action('admin_head', 'activeHelper_liveHelp_timeByChatEmailEditorHead');
	add_filter('mce_buttons', 'activeHelper_liveHelp_timeByChatEmailEditorButtons');
}

function activeHelper_liveHelp_timeByChatEmailEditorButtons($buttons)
{
	array_unshift($buttons, 'fullscreen');

	return $buttons;
}

function activeHelper_liveHelp_timeByChatEmailEditorHead()
{
	wp_tiny_mce(false);
}

function activeHelper_liveHelp_timeByChatEmailEditor()
{
	global $activeHelper_liveHelp;

	wp_enqueue_script(array('jquery', 'editor', 'thickbox', 'media-upload'));
	wp_enqueue_style('thickbox');
}

function activeHelper_liveHelp_timeByChatEmail()
{
	global $activeHelper_liveHelp;

	if (!empty($activeHelper_liveHelp['errors']))
		$errors = $activeHelper_liveHelp['errors'];

	$tabindex = 1;

	echo '
<div class="wrap">
	<div id="icon-edit" class="icon32 icon32-posts-post"><br /></div>
	<h2>
		LiveHelp » ' . __('Time by chat', 'activehelper_livehelp') . ' » Send by email
	</h2>';

	if (isset($_GET['email']))
		echo '
	<div class="updated below-h2" id="message">
		<p>' . __('The email has been sent. Would you like to send it again to another recipient?', 'activehelper_livehelp') . '</p>
	</div>';

	echo '
	<form id="pmManager_form" action="admin.php?page=' . strtolower('activeHelper_liveHelp_timeByChat') . '&action=email&id=' . $_REQUEST['id'] . '" method="post" accept-charset="utf-8" enctype="multipart/form-data">
		<div id="poststuff" class="metabox-holder has-right-sidebar">
			<div class="inner-sidebar"><div class="meta-box-sortables ui-sortable">
				<div id="submitdiv" class="postbox">
					<div class="handlediv" title="' . __('Click to toggle', 'activehelper_livehelp') . '"><br /></div>
					<h3 style="cursor: default;"><span style="cursor: default;">
						' . __('Send', 'activehelper_livehelp') . '</span></h3>
					<div class="inside"><div class="submitbox">
						<div id="major-publishing-actions" style="padding: 1ex;">
							<div id="delete-action">
								<a class="submitdelete deletion" href="admin.php?page=' . strtolower('activeHelper_liveHelp_timeByChat') . '">' . __('Cancel', 'activehelper_livehelp') . '</a>
							</div>
							<div id="publishing-action">
								<input name="submit" value="' . __('Send', 'activehelper_livehelp') . '" type="submit" accesskey="p" tabindex="999" class="button-primary">
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
					<h3 style="cursor: default;">
						' . __('Subject', 'activehelper_livehelp') . '</h3>
					<div class="inside">
						<input maxlength="255" type="text" id="email_subject" value="' . $_POST['email_subject'] . '" tabindex="' . $tabindex++ . '" size="30" name="email_subject" style="width: 98%">' . (isset($errors['email_subject']) ? '
						<p style="color: #f00;">' . __('Error', 'activehelper_livehelp') . ': <code style="background-color: #FAF0F0;">' . $errors['email_subject'] . '</code></p>' : '') . '
					</div>
				</div>
				<div class="stuffbox postbox">
					<div class="handlediv" title="' . __('Click to toggle', 'activehelper_livehelp') . '"><br /></div>
					<h3 style="cursor: default;">
						' . __('Emisor name', 'activehelper_livehelp') . '</h3>
					<div class="inside">
						<input maxlength="255" type="text" id="email_from_name" value="' . $_POST['email_from_name'] . '" tabindex="' . $tabindex++ . '" size="30" name="email_from_name" style="width: 98%">' . (isset($errors['email_from_name']) ? '
						<p style="color: #f00;">' . __('Error', 'activehelper_livehelp') . ': <code style="background-color: #FAF0F0;">' . $errors['email_from_name'] . '</code></p>' : '') . '
					</div>
				</div>
				<div class="stuffbox postbox">
					<div class="handlediv" title="' . __('Click to toggle', 'activehelper_livehelp') . '"><br /></div>
					<h3 style="cursor: default;">
						' . __('Emisor email', 'activehelper_livehelp') . '</h3>
					<div class="inside">
						<input maxlength="255" type="text" id="email_from" value="' . $_POST['email_from'] . '" tabindex="' . $tabindex++ . '" size="30" name="email_from" style="width: 98%">' . (isset($errors['email_from']) ? '
						<p style="color: #f00;">' . __('Error', 'activehelper_livehelp') . ': <code style="background-color: #FAF0F0;">' . $errors['email_from'] . '</code></p>' : '') . '
					</div>
				</div>
				<div class="stuffbox postbox">
					<div class="handlediv" title="' . __('Click to toggle', 'activehelper_livehelp') . '"><br /></div>
					<h3 style="cursor: default;">
						' . __('Recipient email', 'activehelper_livehelp') . '</h3>
					<div class="inside">
						<input maxlength="255" type="text" id="email_to" value="' . $_POST['email_to'] . '" tabindex="' . $tabindex++ . '" size="30" name="email_to" style="width: 98%">' . (isset($errors['email_to']) ? '
						<p style="color: #f00;">' . __('Error', 'activehelper_livehelp') . ': <code style="background-color: #FAF0F0;">' . $errors['email_to'] . '</code></p>' : '') . '
					</div>
				</div>
			</div>
				<div id="poststuff" style="padding-bottom: 10px; margin-bottom: 0;"><div id="postdivrich" class="postarea">';

	the_editor($_POST['content'], 'content', false, false, $tabindex++);

	echo '
					<table id="post-status-info" cellspacing="0"><tbody><tr>
						<td id="wp-word-count" style="height: 18px; line-height: 18px;"></td>
						<td class="autosave-info"><span id="autosave">&nbsp;</span></td>
					</tr></tbody></table>
				</div></div>
			</div></div>
			<br />
		</div>
	</form>
	<script type="text/javascript">
		jQuery(document).ready(function($)
		{
			if ($("#edButtonHTML").length && $("#edButtonHTML").hasClass("active"))
				$("#edButtonHTML").click();
		});
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

function activeHelper_liveHelp_timeByChatDeletePost()
{
	global $wpdb, $activeHelper_liveHelp;

	$_REQUEST['id'] = !empty($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;

	if (empty($_REQUEST['id']))
	{
		wp_redirect('admin.php?page=' . strtolower('activeHelper_liveHelp_timeByChat') . '&miss');
		exit;
	}

	$wpdb->query("
		DELETE FROM {$wpdb->prefix}livehelp_sessions, {$wpdb->prefix}livehelp_messages
		USING {$wpdb->prefix}livehelp_sessions
		INNER JOIN {$wpdb->prefix}livehelp_messages
		WHERE {$wpdb->prefix}livehelp_sessions.id = '{$_REQUEST['id']}'
			AND {$wpdb->prefix}livehelp_messages.session = {$wpdb->prefix}livehelp_sessions.id
	");

	wp_redirect('admin.php?page=' . strtolower('activeHelper_liveHelp_timeByChat') . '&delete');
	exit;
}

function activeHelper_liveHelp_timeByChatExportPost()
{
	global $wpdb, $activeHelper_liveHelp;

	$timeEnd = !empty($_REQUEST['export_end_date']) ? strtotime((string) $_REQUEST['export_end_date']) : time();
	$timeStart = !empty($_REQUEST['export_start_date']) ? strtotime((string) $_REQUEST['export_start_date']) : mktime(0, 0, 0, date("n", $timeEnd) - 1, date("j", $timeEnd), date("Y", $timeEnd));

	$timeByChatList = $wpdb->get_results("
		SELECT
			jls.id AS session, CONCAT(jlu.firstname, ' ', jlu.lastname) AS name, jld.name AS domain,
			jls.username AS visitor, jls.email AS email, if(jls.rating = -1, '" . __('Not rate', 'activehelper_livehelp') . "', jls.rating) AS rating,
			(TIMEDIFF(jls.refresh, jls.datetime)) AS time, DATE_FORMAT(jls.datetime, '%m/%d/%Y') AS date
		FROM
			{$wpdb->prefix}livehelp_sessions AS jls,
			{$wpdb->prefix}livehelp_users AS jlu,
			{$wpdb->prefix}livehelp_domains AS jld
		WHERE
			DATE_FORMAT(jls.datetime, '%Y%m%d') >= DATE_FORMAT('" . date("Y-m-d", $timeStart) . "', '%Y%m%d')
			AND DATE_FORMAT(jls.datetime,'%Y%m%d') <=DATE_FORMAT('" . date("Y-m-d", $timeEnd) . "', '%Y%m%d')
			AND jls.id_user = jlu.id
			AND jls.id_domain = jld.id_domain
		GROUP BY jls.id, jls.username
		ORDER BY 1 DESC, 2, 3, 4
	", ARRAY_A);

	$export = '"ID","Agent","Domain name","Visitor name","Visitor email","Raiting","Duration","Date"';

	if (!empty($timeByChatList))
		foreach ($timeByChatList as $timeByChat)
		{
			$export .= "\n" . '"' .
				$timeByChat['session'] . '","' .
				$timeByChat['name'] . '","' .
				$timeByChat['domain'] . '","' .
				$timeByChat['visitor'] . '","' .
				$timeByChat['email'] . '","' .
				$timeByChat['rating'] . '","' .
				$timeByChat['time'] . '","' .
				$timeByChat['date'] .
			'"';
		}

	header("Cache-Control: public");
	header("Content-Description: File Transfer");
	header("Content-Disposition: attachment; filename=LiveHelp_TimeByChat_" . date("Y-m-d", $timeStart) . "~" . date("Y-m-d", $timeEnd) . ".csv");
	header("Content-Type: text/csv; text/comma-separated-values");
	header("Content-Transfer-Encoding: binary");

	echo $export;
	exit();
}

function activeHelper_liveHelp_timeByChatViewExportPost()
{
	global $wpdb, $activeHelper_liveHelp;

	$_REQUEST['id'] = !empty($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;

	if (empty($_REQUEST['id']))
	{
		wp_redirect('admin.php?page=' . strtolower('activeHelper_liveHelp_timeByChat') . '&miss');
		exit;
	}

	$chats = $wpdb->get_results("
		SELECT username, message, TIME_FORMAT(jlm.datetime, '%H:%i:%s') AS time
		FROM {$wpdb->prefix}livehelp_messages AS jlm
		WHERE session = '{$_REQUEST['id']}'
		ORDER BY id
	", ARRAY_A);

	$statistic = $wpdb->get_row("
		SELECT
			CONCAT(jlu.firstname, ' ', jlu.lastname) AS agent, jls.department, jls.server,
			jls.username, jls.email, DATE_FORMAT(jls.datetime, '%Y-%m-%d') AS date,
			if(jls.rating = -1, '" . __('Not rate', 'activehelper_livehelp') . "', jls.rating) AS rating,
			jlr.country, jlr.city
		FROM
			{$wpdb->prefix}livehelp_sessions AS jls,
			{$wpdb->prefix}livehelp_users AS jlu,
			{$wpdb->prefix}livehelp_requests AS jlr
		WHERE jls.id = '{$_REQUEST['id']}'
			AND jls.id_user = jlu.id
			AND jls.request = jlr.id
	", ARRAY_A);

	$export = '"Domain","Department","Agent","Visitor","Country","City","Email","Date","Rating"';

	$export .= "\n" . '"' .
			$statistic['server'] . '","' .
			$statistic['department'] . '","' .
			$statistic['agent'] . '","' .
			$statistic['username'] . '","' .
			$statistic['country'] . '","' .
			$statistic['city'] . '","' .
			$statistic['email'] . '","' .
			$statistic['date'] . '","' .
			$statistic['rating'] .
		'"';

	$export .= "\n\n" . '"Username","Message","Time"';

	if (!empty($chats))
		foreach ($chats as $chat)
		{
			$export .= "\n" . '"' .
				$chat['username'] . '","' .
				$chat['message'] . '","' .
				$chat['time'] .
			'"';
		}

	header("Cache-Control: public");
	header("Content-Description: File Transfer");
	header("Content-Disposition: attachment; filename=LiveHelp_" . $statistic['server'] . "~" . $statistic['date'] . "_" . urlencode($statistic['agent'] . '~' . $statistic['username']) . ".csv");
	header("Content-Type: text/csv; text/comma-separated-values; charset=utf-8");
	header("Content-Transfer-Encoding: binary");

	echo $export;
	exit();
}

function activeHelper_liveHelp_timeByChatListPost()
{
	global $wpdb, $activeHelper_liveHelp;

	wp_enqueue_script('jquery');
	wp_enqueue_script('jquery-ui-core');
	wp_enqueue_script('jquery-ui-datepicker', $activeHelper_liveHelp['baseUrl'] . '/scripts/jquery.ui.datepicker.min.js', array('jquery', 'jquery-ui-core') );
	wp_enqueue_style('jquery.ui.theme', $activeHelper_liveHelp['baseUrl'] . '/scripts/smoothness/jquery-ui-1.8.16.custom.css');
}

function activeHelper_liveHelp_timeByChatList()
{
	global $wpdb, $activeHelper_liveHelp;

	$timeEnd = !empty($_REQUEST['export_end_date']) ? strtotime((string) $_REQUEST['export_end_date']) : time();
	$timeStart = !empty($_REQUEST['export_start_date']) ? strtotime((string) $_REQUEST['export_start_date']) : mktime(0, 0, 0, date("n", $timeEnd) - 1, date("j", $timeEnd), date("Y", $timeEnd));

	$timeByChatList = $wpdb->get_results("
		SELECT
			jls.id AS session, CONCAT(jlu.firstname, ' ', jlu.lastname) AS name, jld.name AS domain,
			jls.username AS visitor, jls.email AS email, if(jls.rating = -1, '" . __('Not rate', 'activehelper_livehelp') . "', jls.rating) AS rating,
			(TIMEDIFF(jls.refresh, jls.datetime)) AS time, DATE_FORMAT(jls.datetime, '%m/%d/%Y') AS date
		FROM
			{$wpdb->prefix}livehelp_sessions AS jls,
			{$wpdb->prefix}livehelp_users AS jlu,
			{$wpdb->prefix}livehelp_domains AS jld
		WHERE
			DATE_FORMAT(jls.datetime, '%Y%m%d') >= DATE_FORMAT('" . date("Y-m-d", $timeStart) . "', '%Y%m%d')
			AND DATE_FORMAT(jls.datetime,'%Y%m%d') <=DATE_FORMAT('" . date("Y-m-d", $timeEnd) . "', '%Y%m%d')
			AND jls.id_user = jlu.id
			AND jls.id_domain = jld.id_domain
		GROUP BY jls.id, jls.username
		ORDER BY 1 DESC, 2, 3, 4
	", ARRAY_A);

	echo '
<div class="wrap"><form action="admin.php?page=' . strtolower('activeHelper_liveHelp_timeByChat') . '" method="post" accept-charset="utf-8" id="activeHelper_liveHelp_form">
	<h2 style="padding-right: 0;">
		LiveHelp » ' . __('Time by chat', 'activehelper_livehelp') . '
		<a class="button add-new-h2" href="admin.php?page=' . strtolower('activeHelper_liveHelp_timeByChat') . '&amp;action=export&amp;type=csv" id="activeHelper_liveHelp_export">' . __('export to CSV', 'activehelper_livehelp') . '</a>
	</h2>';

	if (isset($_GET['delete']))
		echo '
	<div class="error below-h2" id="message">
		<p>' . sprintf(__('The %s was deleted permanently.', 'activehelper_livehelp'), __('chat', 'activehelper_livehelp')) . '</p>
	</div>';

	if (isset($_GET['miss']))
		echo '
	<div class="error below-h2" id="message">
		<p>' . sprintf(__('The %s was not found.', 'activehelper_livehelp'), __('chat', 'activehelper_livehelp')) . '</p>
	</div>';

	echo '
	<div class="metabox-holder" style="padding-bottom: 10px;">
		<div id="normal-sortables" class="meta-box-sortables ui-sortable">
			<div class="stuffbox postbox">
				<div class="inside" style="padding-top: 1ex; padding-bottom: 1ex;">
					<div style="float: left; height: 26px; line-height: 26px;">
						<label for="export_start_date">' . __('Start date', 'activehelper_livehelp') . '</labe>
					</div>
					<div style="padding-left: 1ex; float: left; height: 26px; line-height: 26px;">
						<input readonly="readonly" style="background: #fff; cursor: pointer;" tabindex="6" maxlength="255" type="text" style="width: 140px;" value="' . date("Y-m-d", $timeStart) . '" id="export_start_date" name="export_start_date" />
					</div>
					<div style="padding-left: 1ex; float: left; height: 26px; line-height: 26px;">
						<label for="export_end_date">' . __('End date', 'activehelper_livehelp') . '</labe>
					</div>
					<div style="padding-left: 1ex; float: left; height: 26px; line-height: 26px;">
						<input readonly="readonly" style="background: #fff; cursor: pointer;" tabindex="6" maxlength="255" type="text" style="width: 140px;" value="' . date("Y-m-d", $timeEnd) . '" id="export_end_date" name="export_end_date" />
					</div>
					<div style="padding-left: 1ex; float: left; height: 26px; line-height: 26px;">
						<input name="submit" value="' . __('Apply', 'activehelper_livehelp') . '" type="submit" accesskey="p" tabindex="4" class="button-primary">
					</div>
					<div style="clear: both;"></div>
				</div>
			</div>
		</div>
		<table cellspacing="0" class="wp-list-table widefat fixed">
			<thead>
				<tr>
					<th style="width: 50px" class="manage-column" scope="col">
						' . __('ID', 'activehelper_livehelp') . '</th>
					<th style="width: 25%" class="manage-column" scope="col">
						' . __('Agent', 'activehelper_livehelp') . '</th>
					<th style="width: 25%" class="manage-column" scope="col">
						' . __('Domain name', 'activehelper_livehelp') . '</th>
					<th style="width: 25%" class="manage-column" scope="col">
						' . __('Visitor name', 'activehelper_livehelp') . '</th>
					<th style="width: 25%" class="manage-column" scope="col">
						' . __('Visitor email', 'activehelper_livehelp') . '</th>
					<th style="width: 80px" class="manage-column" scope="col">
						' . __('Raiting', 'activehelper_livehelp') . '</th>
					<th style="width: 80px" class="manage-column" scope="col">
						' . __('Duration', 'activehelper_livehelp') . '</th>
					<th style="width: 85px" class="manage-column" scope="col">
						' . __('Date', 'activehelper_livehelp') . '</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th class="manage-column" scope="col">
						' . __('ID', 'activehelper_livehelp') . '</th>
					<th class="manage-column" scope="col">
						' . __('Agent', 'activehelper_livehelp') . '</th>
					<th class="manage-column" scope="col">
						' . __('Domain name', 'activehelper_livehelp') . '</th>
					<th class="manage-column" scope="col">
						' . __('Visitor name', 'activehelper_livehelp') . '</th>
					<th class="manage-column" scope="col">
						' . __('Visitor email', 'activehelper_livehelp') . '</th>
					<th class="manage-column" scope="col">
						' . __('Raiting', 'activehelper_livehelp') . '</th>
					<th class="manage-column" scope="col">
						' . __('Duration', 'activehelper_livehelp') . '</th>
					<th class="manage-column" scope="col">
						' . __('Date', 'activehelper_livehelp') . '</th>
				</tr>
			</tfoot>
			<tbody id="the-list">';

			if (empty($timeByChatList))
				echo '
				<tr valign="top" class="format-default">
					<td class="colspanchange" colspan="8"><p style="margin: 0; padding: .8ex; color: #888;">
						' . sprintf(__('No %s found.', 'activehelper_livehelp'), __('chats', 'activehelper_livehelp')) . '
					</p></td>
				</tr>';
			else
			{
				$alternate = false;
				foreach ($timeByChatList as $timeByChat)
				{
					echo '
				<tr valign="top" class="' . ($alternate ? 'alternate' : '') . ' format-default">
					<td style="padding: 1ex;">
						' . $timeByChat['session'] . '
					</td>
					<td style="padding: 1ex;" class="post-title page-title column-title">
						<strong><a href="admin.php?page=' . strtolower('activeHelper_liveHelp_timeByChat') . '&amp;action=view&amp;id=' . $timeByChat['session'] . '" class="row-title">
							' . $timeByChat['name'] . '</a></strong>
						<div class="row-actions">
							<span class="edit"><a href="admin.php?page=' . strtolower('activeHelper_liveHelp_timeByChat') . '&amp;action=view&amp;id=' . $timeByChat['session'] . '">
								' . __('View', 'activehelper_livehelp') . '</a> | </span>
							<span class="trash"><a href="admin.php?page=' . strtolower('activeHelper_liveHelp_timeByChat') . '&amp;action=delete&amp;id=' . $timeByChat['session'] . '" class="submitdelete" onclick="return window.confirm(\'' . __('Are you sure you want to delete this item permanently?', 'activehelper_livehelp') . '\');">
								' . __('Delete', 'activehelper_livehelp') . '</a></span>
						</div>
					</td>
					<td style="padding: 1ex;">
						' . $timeByChat['domain'] . '
					</td>
					<td style="padding: 1ex;">
						' . $timeByChat['visitor'] . '
					</td>
					<td style="padding: 1ex;">
						' . $timeByChat['email'] . '
					</td>
					<td style="padding: 1ex;">
						' . $timeByChat['rating'] . '
					</td>
					<td style="padding: 1ex;">
						' . $timeByChat['time'] . '
					</td>
					<td style="padding: 1ex;">
						' . $timeByChat['date'] . '
					</td>
				</tr>';

					$alternate = !$alternate;
				}
			}

			echo '
			</tbody>
		</table>
	</div>
	<script type="text/javascript">
		var export_start_date = "' . date("Y-m-d", $timeStart) . '";
		var export_end_date = "' . date("Y-m-d", $timeEnd) . '";

		jQuery(document).ready(function($){
			$("#export_end_date").datepicker({
				dateFormat : "yy-mm-dd",
				defaultDate: 0,
				maxDate: "+0d",
				onSelect: function(date){
					export_end_date = date;
					$("#export_start_date").datepicker("option", "maxDate", date);
				}
			});
			$("#export_start_date").datepicker({
				dateFormat : "yy-mm-dd",
				defaultDate: 0,
				maxDate: export_end_date,
				onSelect: function(date){
					export_start_date = date;
				}
			});

			$("#activeHelper_liveHelp_export").click(function(){
				$(this).attr("href", $(this).attr("href") + "&export_start_date="
					+ export_start_date + "&export_end_date=" + export_end_date);
			});
		});
	</script>
</form></div>';
}

function activeHelper_liveHelp_timeByChatViewPost()
{
	global $wpdb, $activeHelper_liveHelp;

	$_REQUEST['id'] = !empty($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;

	if (empty($_REQUEST['id']))
	{
		wp_redirect('admin.php?page=' . strtolower('activeHelper_liveHelp_timeByChat') . '&miss');
		exit;
	}

	wp_enqueue_style('dashboard');
}

function activeHelper_liveHelp_timeByChatView()
{
	global $wpdb, $activeHelper_liveHelp;

	$chats = $wpdb->get_results("
		SELECT username, message, TIME_FORMAT(jlm.datetime, '%H:%i:%s') AS time
		FROM {$wpdb->prefix}livehelp_messages AS jlm
		WHERE session = '{$_REQUEST['id']}'
		ORDER BY id
	", ARRAY_A);

	$statistic = $wpdb->get_row("
		SELECT
			CONCAT(jlu.firstname, ' ', jlu.lastname) AS agent, jls.department, jls.server,
			jls.username, jls.email, DATE_FORMAT(jls.datetime, '%Y-%m-%d') AS date,
			if(jls.rating = -1, '" . __('Not rate', 'activehelper_livehelp') . "', jls.rating) AS rating,
			jlr.country, jlr.city
		FROM
			{$wpdb->prefix}livehelp_sessions AS jls,
			{$wpdb->prefix}livehelp_users AS jlu,
			{$wpdb->prefix}livehelp_requests AS jlr
		WHERE jls.id = '{$_REQUEST['id']}'
			AND jls.id_user = jlu.id
			AND jls.request = jlr.id
	", ARRAY_A);

	echo '
<div class="wrap"><form action="admin.php?page=' . strtolower('activeHelper_liveHelp_timeByChat') . '" method="post" accept-charset="utf-8" id="activeHelper_liveHelp_form">
	<h2 style="padding-right: 0;">
		LiveHelp » ' . __('Time by chat', 'activehelper_livehelp') . ' » View
		<a class="button add-new-h2" href="admin.php?page=' . strtolower('activeHelper_liveHelp_timeByChat') . '&amp;action=viewExport&amp;type=csv" id="activeHelper_liveHelp_export">' . __('export to CSV', 'activehelper_livehelp') . '</a>
		<a class="button add-new-h2" href="admin.php?page=' . strtolower('activeHelper_liveHelp_timeByChat') . '&amp;action=email" id="activeHelper_liveHelp_email">' . __('send by email', 'activehelper_livehelp') . '</a>
	</h2>';

	if (isset($_GET['delete']))
		echo '
	<div class="error below-h2" id="message">
		<p>' . sprintf(__('The %s was deleted permanently.', 'activehelper_livehelp'), __('chat', 'activehelper_livehelp')) . '</p>
	</div>';

	if (isset($_GET['miss']))
		echo '
	<div class="error below-h2" id="message">
		<p>' . sprintf(__('The %s was not found.', 'activehelper_livehelp'), __('chat', 'activehelper_livehelp')) . '</p>
	</div>';

	echo '
	<div id="dashboard-widgets-wrap">
		<div id="dashboard-widgets" class="metabox-holder">

			<div class="postbox-container" style="width:59%; padding-right: 0;"><div id="normal-sortables" class="meta-box-sortables ui-sortable">
				<div id="dashboard_right_now" class="stuffbox postbox">
					<div class="handlediv" title="' . __('Click to toggle', 'activehelper_livehelp') . '"><br /></div>
					<h3 style="cursor: default;">' . __('Full chat', 'activehelper_livehelp') . '</h3>
					<div class="inside">
						<div class="table table_content" style="width: 98%;">
							<p class="sub">
								' . __('Name: message', 'activehelper_livehelp') . '
							</p>
							<p class="sub" style="right: 15px; left: auto;">
								' . __('Time', 'activehelper_livehelp') . '
							</p>';

	if (empty($chats))
		echo '
							<table><tbody><tr class="first"><td class="first t"></td><td class="b">
								' . __('No records found', 'activehelper_livehelp') . '
							</td></tr></tbody></table>';
	else
		foreach ($chats as $chat)
			echo '
							<table><tbody><tr><td class="t">
								<b>' . $chat['username'] . ':</b> ' . $chat['message'] . '
							</td><td class="b" style="font-size: 12px;">
								' . $chat['time'] . '
							</td></tr></tbody></table>';

	echo '
						</div>
						<div style="clear: both;"></div>
					</div>
				</div>
			</div></div>

			<div class="postbox-container" style="width:39%; float: right; padding-right: 0;"><div id="normal-sortables" class="meta-box-sortables ui-sortable">
				<div id="dashboard_right_now" class="stuffbox postbox">
					<div class="handlediv" title="' . __('Click to toggle', 'activehelper_livehelp') . '"><br /></div>
					<h3 style="cursor: default;">' . __('Chat statistic', 'activehelper_livehelp') . '</h3>
					<div class="inside">
						<div class="table table_content" style="width: 98%;">
							<p class="sub">
								' . __('Statistic', 'activehelper_livehelp') . '
							</p>
							<p class="sub" style="right: 15px; left: auto;">
								' . __('Value', 'activehelper_livehelp') . '
							</p>
							<table><tbody><tr class="first"><td class="first t">
								' . __('Domain', 'activehelper_livehelp') . '
							</td><td class="b">
								' . $statistic['server'] . '
							</td></tr></tbody></table>
							<table><tbody><tr class="first"><td class="first t">
								' . __('Department', 'activehelper_livehelp') . '
							</td><td class="b">
								' . $statistic['department'] . '
							</td></tr></tbody></table>
							<table><tbody><tr class="first"><td class="first t">
								' . __('Agent', 'activehelper_livehelp') . '
							</td><td class="b">
								' . $statistic['agent'] . '
							</td></tr></tbody></table>
							<table><tbody><tr class="first"><td class="first t">
								' . __('Visitor', 'activehelper_livehelp') . '
							</td><td class="b">
								' . $statistic['username'] . '
							</td></tr></tbody></table>
							<table><tbody><tr class="first"><td class="first t">
								' . __('Country', 'activehelper_livehelp') . '
							</td><td class="b">
								' . $statistic['country'] . '
							</td></tr></tbody></table>
							<table><tbody><tr class="first"><td class="first t">
								' . __('City', 'activehelper_livehelp') . '
							</td><td class="b">
								' . $statistic['city'] . '
							</td></tr></tbody></table>
							<table><tbody><tr class="first"><td class="first t">
								' . __('Email', 'activehelper_livehelp') . '
							</td><td class="b">
								' . $statistic['email'] . '
							</td></tr></tbody></table>
							<table><tbody><tr class="first"><td class="first t">
								' . __('Date', 'activehelper_livehelp') . '
							</td><td class="b">
								' . $statistic['date'] . '
							</td></tr></tbody></table>
							<table><tbody><tr class="first"><td class="first t">
								' . __('Rating', 'activehelper_livehelp') . '
							</td><td class="b">
								' . $statistic['rating'] . '
							</td></tr></tbody></table>
						</div>
						<div style="clear: both;"></div>
					</div>
				</div>
			</div></div>
		</div>
	</div>
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

			$("#activeHelper_liveHelp_export").click(function(){
				$(this).attr("href", $(this).attr("href") + "&id=' . $_REQUEST['id'] . '");
			});
			$("#activeHelper_liveHelp_email").click(function(){
				$(this).attr("href", $(this).attr("href") + "&id=' . $_REQUEST['id'] . '");
			});
		});
	</script>
</form></div>';
}

