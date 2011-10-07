<?php
/**
 * @package ActiveHelper Live Help
 */

if (!defined('ACTIVEHELPER_LIVEHELP'))
	die('Hi there! I\'m just a plugin, not much I can do when called directly.');

function activeHelper_liveHelp_offlineMessages()
{
	global $wpdb, $activeHelper_liveHelp;

	$actions = array(
		'list' => 'activeHelper_liveHelp_offlineMessagesList'
	);
	if (!empty($_REQUEST['action']) && isset($actions[$_REQUEST['action']]))
		return $actions[$_REQUEST['action']]();

	return $actions['list']();
}

function activeHelper_liveHelp_offlineMessagesPost()
{
	$actions = array(
		'export' => 'activeHelper_liveHelp_offlineMessagesExportPost',
		'list' => 'activeHelper_liveHelp_offlineMessagesListPost',
		'status' => 'activeHelper_liveHelp_offlineMessagesStatusPost',
		'delete' => 'activeHelper_liveHelp_offlineMessagesDeletePost'
	);

	if (!empty($_REQUEST['action']) && isset($actions[$_REQUEST['action']]))
		return $actions[$_REQUEST['action']]();

	if (empty($_REQUEST['action']))
		return $actions['list']();
}

function activeHelper_liveHelp_offlineMessagesExportPost()
{
	global $wpdb, $activeHelper_liveHelp;

	$timeEnd = !empty($_REQUEST['export_end_date']) ? strtotime((string) $_REQUEST['export_end_date']) : time();
	$timeStart = !empty($_REQUEST['export_start_date']) ? strtotime((string) $_REQUEST['export_start_date']) : mktime(0, 0, 0, date("n", $timeEnd) - 1, date("j", $timeEnd), date("Y", $timeEnd));

	$offlineMessagesList = $wpdb->get_results("
		SELECT
			jlom.id, jlom.name, jlom.email, jld.name AS domain, jlom.datetime, jlom.message, jlom.answered
		FROM
			{$wpdb->prefix}livehelp_offline_messages AS jlom,
			{$wpdb->prefix}livehelp_domains AS jld
		WHERE
			DATE_FORMAT(jlom.datetime,'%Y%m%d') >= DATE_FORMAT('" . date("Y-m-d", $timeStart) . "', '%Y%m%d')
			AND DATE_FORMAT(jlom.datetime, '%Y%m%d') <= DATE_FORMAT('" . date("Y-m-d", $timeEnd) . "', '%Y%m%d')
			AND jlom.id_domain = jld.id_domain
		ORDER BY jlom.id desc, jld.name
	", ARRAY_A);

	$export = '"Answered","Name","Email","Domain name","Date","Message"';

	if (!empty($offlineMessagesList))
		foreach ($offlineMessagesList as $offlineMessages)
		{
			$export .= "\n" . '"' .
				($offlineMessages['answered'] == 1 ? 'Yes' : 'No') . '","' .
				$offlineMessages['name'] . '","' .
				$offlineMessages['email'] . '","' .
				$offlineMessages['domain'] . '","' .
				$offlineMessages['datetime'] . '","' .
				$offlineMessages['message'] .
			'"';
		}

	header("Cache-Control: public");
	header("Content-Description: File Transfer");
	header("Content-Disposition: attachment; filename=LiveHelp_OfflineMessages_" . date("Y-m-d", $timeStart) . "~" . date("Y-m-d", $timeEnd) . ".csv");
	header("Content-Type: text/csv; text/comma-separated-values");
	header("Content-Transfer-Encoding: binary");

	echo $export;
	exit();
}

function activeHelper_liveHelp_offlineMessagesListPost()
{
	global $wpdb, $activeHelper_liveHelp;

	wp_enqueue_script('jquery');
	wp_enqueue_script('jquery-ui-core');
	wp_enqueue_script('jquery-ui-datepicker', $activeHelper_liveHelp['baseUrl'] . '/scripts/jquery.ui.datepicker.min.js', array('jquery', 'jquery-ui-core') );
	wp_enqueue_style('jquery.ui.theme', $activeHelper_liveHelp['baseUrl'] . '/scripts/smoothness/jquery-ui-1.8.16.custom.css');
}

function activeHelper_liveHelp_offlineMessagesStatusPost()
{
	global $wpdb, $activeHelper_liveHelp;

	$timeEnd = !empty($_REQUEST['export_end_date']) ? strtotime((string) $_REQUEST['export_end_date']) : time();
	$timeStart = !empty($_REQUEST['export_start_date']) ? strtotime((string) $_REQUEST['export_start_date']) : mktime(0, 0, 0, date("n", $timeEnd) - 1, date("j", $timeEnd), date("Y", $timeEnd));

	$_REQUEST['id'] = !empty($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;

	if (empty($_REQUEST['id']))
	{
		wp_redirect('admin.php?page=' . strtolower('activeHelper_liveHelp_offlineMessages') . '&miss&export_start_date=' . date("Y-m-d", $timeStart) . '&export_end_date=' . date("Y-m-d", $timeEnd));
		exit;
	}

	$wpdb->query("
		UPDATE {$wpdb->prefix}livehelp_offline_messages
		SET answered = IF(answered <> 1, 1, 0)
		WHERE id = '{$_REQUEST['id']}'
	");

	wp_redirect('admin.php?page=' . strtolower('activeHelper_liveHelp_offlineMessages') . '&status&export_start_date=' . date("Y-m-d", $timeStart) . '&export_end_date=' . date("Y-m-d", $timeEnd));
	exit;
}

function activeHelper_liveHelp_offlineMessagesDeletePost()
{
	global $wpdb, $activeHelper_liveHelp;

	$timeEnd = !empty($_REQUEST['export_end_date']) ? strtotime((string) $_REQUEST['export_end_date']) : time();
	$timeStart = !empty($_REQUEST['export_start_date']) ? strtotime((string) $_REQUEST['export_start_date']) : mktime(0, 0, 0, date("n", $timeEnd) - 1, date("j", $timeEnd), date("Y", $timeEnd));

	$_REQUEST['id'] = !empty($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;

	if (empty($_REQUEST['id']))
	{
		wp_redirect('admin.php?page=' . strtolower('activeHelper_liveHelp_offlineMessages') . '&miss&export_start_date=' . date("Y-m-d", $timeStart) . '&export_end_date=' . date("Y-m-d", $timeEnd));
		exit;
	}

	$wpdb->query("
		DELETE FROM {$wpdb->prefix}livehelp_offline_messages
		WHERE id = '{$_REQUEST['id']}'
	");

	wp_redirect('admin.php?page=' . strtolower('activeHelper_liveHelp_offlineMessages') . '&delete&export_start_date=' . date("Y-m-d", $timeStart) . '&export_end_date=' . date("Y-m-d", $timeEnd));
	exit;
}

function activeHelper_liveHelp_offlineMessagesList()
{
	global $wpdb, $activeHelper_liveHelp;

	$timeEnd = !empty($_REQUEST['export_end_date']) ? strtotime((string) $_REQUEST['export_end_date']) : time();
	$timeStart = !empty($_REQUEST['export_start_date']) ? strtotime((string) $_REQUEST['export_start_date']) : mktime(0, 0, 0, date("n", $timeEnd) - 1, date("j", $timeEnd), date("Y", $timeEnd));

	$offlineMessagesList = $wpdb->get_results("
		SELECT
			jlom.id, jlom.name, jlom.email, jld.name AS domain, jlom.datetime, jlom.message, jlom.answered
		FROM
			{$wpdb->prefix}livehelp_offline_messages AS jlom,
			{$wpdb->prefix}livehelp_domains AS jld
		WHERE
			DATE_FORMAT(jlom.datetime,'%Y%m%d') >= DATE_FORMAT('" . date("Y-m-d", $timeStart) . "', '%Y%m%d')
			AND DATE_FORMAT(jlom.datetime, '%Y%m%d') <= DATE_FORMAT('" . date("Y-m-d", $timeEnd) . "', '%Y%m%d')
			AND jlom.id_domain = jld.id_domain
		ORDER BY jlom.id desc, jld.name
	", ARRAY_A);

	echo '
<div class="wrap"><form action="admin.php?page=' . strtolower('activeHelper_liveHelp_offlineMessages') . '" method="post" accept-charset="utf-8" id="activeHelper_liveHelp_form">
	<h2 style="padding-right: 0;">
		LiveHelp » ' . __('Offline messages', 'activehelper_livehelp') . '
		<a class="button add-new-h2" href="admin.php?page=' . strtolower('activeHelper_liveHelp_offlineMessages') . '&amp;action=export&amp;type=csv" id="activeHelper_liveHelp_export">' . __('export to CSV', 'activehelper_livehelp') . '</a>
	</h2>';

	if (isset($_GET['delete']))
		echo '
	<div class="error below-h2" id="message">
		<p>' . sprintf(__('The %s was deleted permanently.', 'activehelper_livehelp'), __('chat', 'activehelper_livehelp')) . '</p>
	</div>';

	if (isset($_GET['status']))
		echo '
	<div class="updated below-h2" id="message">
		<p>' . sprintf(__('The %s was successfully updated.', 'activehelper_livehelp'), __('chat', 'activehelper_livehelp')) . '</p>
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
					<th style="width: 100px" class="manage-column" scope="col">
						' . __('Answered', 'activehelper_livehelp') . '</th>
					<th style="width: 25%" class="manage-column" scope="col">
						' . __('Name', 'activehelper_livehelp') . '</th>
					<th style="width: 25%" class="manage-column" scope="col">
						' . __('Email', 'activehelper_livehelp') . '</th>
					<th style="width: 25%" class="manage-column" scope="col">
						' . __('Domain name', 'activehelper_livehelp') . '</th>
					<th style="width: 160px" class="manage-column" scope="col">
						' . __('Date', 'activehelper_livehelp') . '</th>
					<th style="width: 25%" class="manage-column" scope="col">
						' . __('Message', 'activehelper_livehelp') . '</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th class="manage-column" scope="col">
						' . __('Answered', 'activehelper_livehelp') . '</th>
					<th class="manage-column" scope="col">
						' . __('Name', 'activehelper_livehelp') . '</th>
					<th class="manage-column" scope="col">
						' . __('Email', 'activehelper_livehelp') . '</th>
					<th class="manage-column" scope="col">
						' . __('Domain name', 'activehelper_livehelp') . '</th>
					<th class="manage-column" scope="col">
						' . __('Date', 'activehelper_livehelp') . '</th>
					<th class="manage-column" scope="col">
						' . __('Message', 'activehelper_livehelp') . '</th>
				</tr>
			</tfoot>
			<tbody id="the-list">';

			if (empty($offlineMessagesList))
				echo '
				<tr valign="top" class="format-default">
					<td class="colspanchange" colspan="5"><p style="margin: 0; padding: .8ex; color: #888;">
						' . sprintf(__('No %s found.', 'activehelper_livehelp'), __('chats', 'activehelper_livehelp')) . '
					</p></td>
				</tr>';
			else
			{
				$alternate = false;
				foreach ($offlineMessagesList as $offlineMessages)
				{
					echo '
				<tr valign="top" class="' . ($alternate ? 'alternate' : '') . ' format-default">
					<td style="padding: 1ex;">
						<a href="admin.php?page=' . strtolower('activeHelper_liveHelp_offlineMessages') . '&amp;action=status&amp;id=' . $offlineMessages['id'] . '&amp;export_start_date=' . date("Y-m-d", $timeStart) . '&amp;export_end_date=' . date("Y-m-d", $timeEnd) . '"><img src="' . $activeHelper_liveHelp['imagesUrl'] . '/' . $offlineMessages['answered'] . '.gif" alt="" /></a>
					</td>
					<td style="padding: 1ex;" class="post-title page-title column-title">
						<strong>' . $offlineMessages['name'] . '</strong>
						<div class="row-actions">
							<span class="edit"><a href="admin.php?page=' . strtolower('activeHelper_liveHelp_offlineMessages') . '&amp;action=status&amp;id=' . $offlineMessages['id'] . '&amp;export_start_date=' . date("Y-m-d", $timeStart) . '&amp;export_end_date=' . date("Y-m-d", $timeEnd) . '">
								' . __('Change status', 'activehelper_livehelp') . '</a> | </span>
							<span class="trash"><a href="admin.php?page=' . strtolower('activeHelper_liveHelp_offlineMessages') . '&amp;action=delete&amp;id=' . $offlineMessages['id'] . '&amp;export_start_date=' . date("Y-m-d", $timeStart) . '&amp;export_end_date=' . date("Y-m-d", $timeEnd) . '" class="submitdelete" onclick="return window.confirm(\'' . __('Are you sure you want to delete this item permanently?', 'activehelper_livehelp') . '\');">
								' . __('Delete', 'activehelper_livehelp') . '</a></span>
						</div>
					</td>
					<td style="padding: 1ex;">
						' . $offlineMessages['email'] . '
					</td>
					<td style="padding: 1ex;">
						' . $offlineMessages['domain'] . '
					</td>
					<td style="padding: 1ex;">
						' . $offlineMessages['datetime'] . '
					</td>
					<td style="padding: 1ex;">
						' . $offlineMessages['message'] . '
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

