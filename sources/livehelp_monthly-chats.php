<?php
/**
 * @package ActiveHelper Live Help
 */

if (!defined('ACTIVEHELPER_LIVEHELP'))
	die('Hi there! I\'m just a plugin, not much I can do when called directly.');

function activeHelper_liveHelp_monthlyChats()
{
	global $wpdb, $activeHelper_liveHelp;

	$actions = array(
		'list' => 'activeHelper_liveHelp_monthlyChatsList'
	);
	if (!empty($_REQUEST['action']) && isset($actions[$_REQUEST['action']]))
		return $actions[$_REQUEST['action']]();

	return $actions['list']();
}

function activeHelper_liveHelp_monthlyChatsPost()
{
	$actions = array(
		'export' => 'activeHelper_liveHelp_monthlyChatsExportPost',
		'list' => 'activeHelper_liveHelp_monthlyChatsListPost'
	);

	if (!empty($_REQUEST['action']) && isset($actions[$_REQUEST['action']]))
		return $actions[$_REQUEST['action']]();

	if (empty($_REQUEST['action']))
		return $actions['list']();
}

function activeHelper_liveHelp_monthlyChatsExportPost()
{
	global $wpdb, $activeHelper_liveHelp;

	$timeEnd = !empty($_REQUEST['export_end_date']) ? strtotime((string) $_REQUEST['export_end_date']) : time();
	$timeStart = !empty($_REQUEST['export_start_date']) ? strtotime((string) $_REQUEST['export_start_date']) : mktime(0, 0, 0, date("n", $timeEnd) - 1, date("j", $timeEnd), date("Y", $timeEnd));

	$monthlyChatsList = $wpdb->get_results("
		SELECT
			CONCAT(jlu.firstname, ' ', jlu.lastname) AS name,
			jld.name AS domain,
			count(jls.id) AS chats
		FROM
			{$wpdb->prefix}livehelp_sessions AS jls,
			{$wpdb->prefix}livehelp_users AS jlu,
			{$wpdb->prefix}livehelp_domains AS jld
		WHERE
			DATE_FORMAT(jls.datetime, '%Y%m%d') >= DATE_FORMAT('" . date("Y-m-d", $timeStart) . "', '%Y%m%d')
			AND DATE_FORMAT(jls.datetime, '%Y%m%d') <= DATE_FORMAT('" . date("Y-m-d", $timeEnd) . "', '%Y%m%d')
			AND jls.id_user = jlu.id
			AND jls.id_domain =jld.id_domain
		GROUP BY
			jls.id_domain, jlu.username, CONCAT(jlu.firstname, ' ', jlu.lastname)
		ORDER BY
			CONCAT(jlu.firstname, ' ', jlu.lastname)
	", ARRAY_A);

	$export = '"Agent","Domain name","Chats"';

	if (!empty($monthlyChatsList))
		foreach ($monthlyChatsList as $monthlyChats)
		{
			$export .= "\n" . '"' .
				$monthlyChats['name'] . '","' .
				$monthlyChats['domain'] . '","' .
				$monthlyChats['chats'] .
			'"';
		}

	header("Cache-Control: public");
	header("Content-Description: File Transfer");
	header("Content-Disposition: attachment; filename=LiveHelp_MonthlyChats_" . date("Y-m-d", $timeStart) . "~" . date("Y-m-d", $timeEnd) . ".csv");
	header("Content-Type: text/csv; text/comma-separated-values");
	header("Content-Transfer-Encoding: binary");

	echo $export;
	exit();
}

function activeHelper_liveHelp_monthlyChatsListPost()
{
	global $wpdb, $activeHelper_liveHelp;

	wp_enqueue_script('jquery');
	wp_enqueue_script('jquery-ui-core');
	wp_enqueue_script('jquery-ui-datepicker', $activeHelper_liveHelp['baseUrl'] . '/scripts/jquery.ui.datepicker.min.js', array('jquery', 'jquery-ui-core') );
	wp_enqueue_style('jquery.ui.theme', $activeHelper_liveHelp['baseUrl'] . '/scripts/smoothness/jquery-ui-1.8.16.custom.css');
}

function activeHelper_liveHelp_monthlyChatsList()
{
	global $wpdb, $activeHelper_liveHelp;

	$timeEnd = !empty($_REQUEST['export_end_date']) ? strtotime((string) $_REQUEST['export_end_date']) : time();
	$timeStart = !empty($_REQUEST['export_start_date']) ? strtotime((string) $_REQUEST['export_start_date']) : mktime(0, 0, 0, date("n", $timeEnd) - 1, date("j", $timeEnd), date("Y", $timeEnd));

	$monthlyChatsList = $wpdb->get_results("
		SELECT
			CONCAT(jlu.firstname, ' ', jlu.lastname) AS name,
			jld.name AS domain,
			count(jls.id) AS chats
		FROM
			{$wpdb->prefix}livehelp_sessions AS jls,
			{$wpdb->prefix}livehelp_users AS jlu,
			{$wpdb->prefix}livehelp_domains AS jld
		WHERE
			DATE_FORMAT(jls.datetime, '%Y%m%d') >= DATE_FORMAT('" . date("Y-m-d", $timeStart) . "', '%Y%m%d')
			AND DATE_FORMAT(jls.datetime, '%Y%m%d') <= DATE_FORMAT('" . date("Y-m-d", $timeEnd) . "', '%Y%m%d')
			AND jls.id_user = jlu.id
			AND jls.id_domain = jld.id_domain
		GROUP BY
			jls.id_domain, jlu.username, CONCAT(jlu.firstname, ' ', jlu.lastname)
		ORDER BY
			CONCAT(jlu.firstname, ' ', jlu.lastname)
	", ARRAY_A);

	echo '
<div class="wrap"><form action="admin.php?page=' . strtolower('activeHelper_liveHelp_monthlyChats') . '" method="post" accept-charset="utf-8" id="activeHelper_liveHelp_form">
	<h2 style="padding-right: 0;">
		LiveHelp » ' . __('Monthly chats', 'activehelper_livehelp') . '
		<a class="button add-new-h2" href="admin.php?page=' . strtolower('activeHelper_liveHelp_monthlyChats') . '&amp;action=export&amp;type=csv" id="activeHelper_liveHelp_export">' . __('export to CSV', 'activehelper_livehelp') . '</a>
	</h2>';

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
						<input name="search" value="' . __('Apply', 'activehelper_livehelp') . '" type="submit" accesskey="p" tabindex="4" class="button-primary">
					</div>
					<div style="clear: both;"></div>
				</div>
			</div>
		</div>
		<table cellspacing="0" class="wp-list-table widefat fixed">
			<thead>
				<tr>
					<th style="width: 50%" class="manage-column" scope="col">
						' . __('Agent', 'activehelper_livehelp') . '</th>
					<th style="width: 50%" class="manage-column" scope="col">
						' . __('Domain name', 'activehelper_livehelp') . '</th>
					<th style="width: 100px" class="manage-column" scope="col">
						' . __('Chats', 'activehelper_livehelp') . '</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th class="manage-column" scope="col">
						' . __('Agent', 'activehelper_livehelp') . '</th>
					<th class="manage-column" scope="col">
						' . __('Domain name', 'activehelper_livehelp') . '</th>
					<th class="manage-column" scope="col">
						' . __('Chats', 'activehelper_livehelp') . '</th>
				</tr>
			</tfoot>
			<tbody id="the-list">';

			if (empty($monthlyChatsList))
				echo '
				<tr valign="top" class="format-default">
					<td class="colspanchange" colspan="3"><p style="margin: 0; padding: .8ex; color: #888;">
						' . sprintf(__('No %s found.', 'activehelper_livehelp'), __('monthly chats', 'activehelper_livehelp')) . '
					</p></td>
				</tr>';
			else
			{
				$alternate = false;
				foreach ($monthlyChatsList as $monthlyChats)
				{
					echo '
				<tr valign="top" class="' . ($alternate ? 'alternate' : '') . ' format-default">
					<td style="padding: 1ex;">
						' . $monthlyChats['name'] . '
					</td>
					<td style="padding: 1ex;">
						' . $monthlyChats['domain'] . '
					</td>
					<td style="padding: 1ex;">
						' . $monthlyChats['chats'] . '
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

