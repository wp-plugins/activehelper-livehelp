<?php
/**
 * @package ActiveHelper Live Help
 */

if (!defined('ACTIVEHELPER_LIVEHELP'))
	die('Hi there! I\'m just a plugin, not much I can do when called directly.');

function activeHelper_liveHelp_chatsByDepartment()
{
	global $wpdb, $activeHelper_liveHelp;

	$actions = array(
		'list' => 'activeHelper_liveHelp_chatsByDepartmentList'
	);
	if (!empty($_REQUEST['action']) && isset($actions[$_REQUEST['action']]))
		return $actions[$_REQUEST['action']]();

	return $actions['list']();
}

function activeHelper_liveHelp_chatsByDepartmentPost()
{
	$actions = array(
		'export' => 'activeHelper_liveHelp_chatsByDepartmentExportPost',
		'list' => 'activeHelper_liveHelp_chatsByDepartmentListPost'
	);

	if (!empty($_REQUEST['action']) && isset($actions[$_REQUEST['action']]))
		return $actions[$_REQUEST['action']]();

	if (empty($_REQUEST['action']))
		return $actions['list']();
}

function activeHelper_liveHelp_chatsByDepartmentExportPost()
{
	global $wpdb, $activeHelper_liveHelp;

	$timeEnd = !empty($_REQUEST['export_end_date']) ? strtotime((string) $_REQUEST['export_end_date']) : time();
	$timeStart = !empty($_REQUEST['export_start_date']) ? strtotime((string) $_REQUEST['export_start_date']) : mktime(0, 0, 0, date("n", $timeEnd) - 1, date("j", $timeEnd), date("Y", $timeEnd));

	$chatsByDepartmentList = $wpdb->get_results("
		SELECT
			jls.department, jld.name AS domain, IFNULL(AVG(IF(jls.rating = -1, NULL, jls.rating)), 0) AS rating,
			COUNT(*) AS chats
		FROM
			{$wpdb->prefix}livehelp_domains AS jld,
			{$wpdb->prefix}livehelp_sessions AS jls
		WHERE
			DATE_FORMAT(jls.datetime, '%Y%m%d') >= DATE_FORMAT('" . date("Y-m-d", $timeStart) . "', '%Y%m%d')
			AND DATE_FORMAT(jls.datetime, '%Y%m%d') <= DATE_FORMAT('" . date("Y-m-d", $timeEnd) . "', '%Y%m%d')
			AND jls.id_domain = jld.id_domain
		GROUP BY jls.department
		ORDER BY jls.department
	", ARRAY_A);

	$export = '"Department","Domain name","Raiting","Chats"';

	if (!empty($chatsByDepartmentList))
		foreach ($chatsByDepartmentList as $chatsByDepartment)
		{
			$export .= "\n" . '"' .
				$chatsByDepartment['department'] . '","' .
				$chatsByDepartment['domain'] . '","' .
				$chatsByDepartment['rating'] . '","' .
				$chatsByDepartment['chats'] .
			'"';
		}

	header("Cache-Control: public");
	header("Content-Description: File Transfer");
	header("Content-Disposition: attachment; filename=LiveHelp_ChatsByDepartment_" . date("Y-m-d", $timeStart) . "~" . date("Y-m-d", $timeEnd) . ".csv");
	header("Content-Type: text/csv; text/comma-separated-values");
	header("Content-Transfer-Encoding: binary");

	echo $export;
	exit();
}

function activeHelper_liveHelp_chatsByDepartmentListPost()
{
	global $wpdb, $activeHelper_liveHelp;

	wp_enqueue_script('jquery');
	wp_enqueue_script('jquery-ui-core');
	wp_enqueue_script('jquery-ui-datepicker', $activeHelper_liveHelp['baseUrl'] . '/scripts/jquery.ui.datepicker.min.js', array('jquery', 'jquery-ui-core') );
	wp_enqueue_style('jquery.ui.theme', $activeHelper_liveHelp['baseUrl'] . '/scripts/smoothness/jquery-ui-1.8.16.custom.css');
}

function activeHelper_liveHelp_chatsByDepartmentList()
{
	global $wpdb, $activeHelper_liveHelp;

	$timeEnd = !empty($_REQUEST['export_end_date']) ? strtotime((string) $_REQUEST['export_end_date']) : time();
	$timeStart = !empty($_REQUEST['export_start_date']) ? strtotime((string) $_REQUEST['export_start_date']) : mktime(0, 0, 0, date("n", $timeEnd) - 1, date("j", $timeEnd), date("Y", $timeEnd));

	$chatsByDepartmentList = $wpdb->get_results("
		SELECT
			jls.department, jld.name AS domain, IFNULL(AVG(IF(jls.rating = -1, NULL, jls.rating)), 0) AS rating, COUNT(*) AS chats
		FROM
			{$wpdb->prefix}livehelp_domains AS jld,
			{$wpdb->prefix}livehelp_sessions AS jls
		WHERE
			DATE_FORMAT(jls.datetime, '%Y%m%d') >= DATE_FORMAT('" . date("Y-m-d", $timeStart) . "', '%Y%m%d')
			AND DATE_FORMAT(jls.datetime, '%Y%m%d') <= DATE_FORMAT('" . date("Y-m-d", $timeEnd) . "', '%Y%m%d')
			AND jls.id_domain = jld.id_domain
		GROUP BY jls.department
		ORDER BY jls.department
	", ARRAY_A);

	echo '
<div class="wrap"><form action="admin.php?page=' . strtolower('activeHelper_liveHelp_chatsByDepartment') . '" method="post" accept-charset="utf-8" id="activeHelper_liveHelp_form">
	<h2 style="padding-right: 0;">
		LiveHelp » ' . __('Chats by department', 'activehelper_livehelp') . '
		<a class="button add-new-h2" href="admin.php?page=' . strtolower('activeHelper_liveHelp_chatsByDepartment') . '&amp;action=export&amp;type=csv" id="activeHelper_liveHelp_export">' . __('export to CSV', 'activehelper_livehelp') . '</a>
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
						<input name="submit" value="' . __('Apply', 'activehelper_livehelp') . '" type="submit" accesskey="p" tabindex="4" class="button-primary">
					</div>
					<div style="clear: both;"></div>
				</div>
			</div>
		</div>
		<table cellspacing="0" class="wp-list-table widefat fixed">
			<thead>
				<tr>
					<th style="width: 50%" class="manage-column" scope="col">
						' . __('Department', 'activehelper_livehelp') . '</th>
					<th style="width: 50%" class="manage-column" scope="col">
						' . __('Domain name', 'activehelper_livehelp') . '</th>
					<th style="width: 120px" class="manage-column" scope="col">
						' . __('Raiting', 'activehelper_livehelp') . '</th>
					<th style="width: 120px" class="manage-column" scope="col">
						' . __('Chats', 'activehelper_livehelp') . '</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th class="manage-column" scope="col">
						' . __('Department', 'activehelper_livehelp') . '</th>
					<th class="manage-column" scope="col">
						' . __('Domain name', 'activehelper_livehelp') . '</th>
					<th class="manage-column" scope="col">
						' . __('Raiting', 'activehelper_livehelp') . '</th>
					<th class="manage-column" scope="col">
						' . __('Chats', 'activehelper_livehelp') . '</th>
				</tr>
			</tfoot>
			<tbody id="the-list">';

			if (empty($chatsByDepartmentList))
				echo '
				<tr valign="top" class="format-default">
					<td class="colspanchange" colspan="4"><p style="margin: 0; padding: .8ex; color: #888;">
						' . sprintf(__('No %s found.', 'activehelper_livehelp'), __('chats', 'activehelper_livehelp')) . '
					</p></td>
				</tr>';
			else
			{
				$alternate = false;
				foreach ($chatsByDepartmentList as $chatsByDepartment)
				{
					echo '
				<tr valign="top" class="' . ($alternate ? 'alternate' : '') . ' format-default">
					<td style="padding: 1ex;" class="post-title page-title column-title">
						<strong>' . $chatsByDepartment['department'] . '</strong>
					</td>
					<td style="padding: 1ex;">
						' . $chatsByDepartment['domain'] . '
					</td>
					<td style="padding: 1ex;">
						' . $chatsByDepartment['rating'] . '
					</td>
					<td style="padding: 1ex;">
						' . $chatsByDepartment['chats'] . '
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

