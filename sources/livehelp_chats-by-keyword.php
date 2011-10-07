<?php
/**
 * @package ActiveHelper Live Help
 */

if (!defined('ACTIVEHELPER_LIVEHELP'))
	die('Hi there! I\'m just a plugin, not much I can do when called directly.');

function activeHelper_liveHelp_chatsByKeyword()
{
	global $wpdb, $activeHelper_liveHelp;

	$actions = array(
		'list' => 'activeHelper_liveHelp_chatsByKeywordList',
		// 'view' => 'activeHelper_liveHelp_timeByChatView', // use external function, since it already exists
		// 'email' => 'activeHelper_liveHelp_timeByChatEmail' // use external function, since it already exists
	);
	if (!empty($_REQUEST['action']) && isset($actions[$_REQUEST['action']]))
		return $actions[$_REQUEST['action']]();

	return $actions['list']();
}

function activeHelper_liveHelp_chatsByKeywordPost()
{
	$actions = array(
		'list' => 'activeHelper_liveHelp_chatsByKeywordListPost',
		'export' => 'activeHelper_liveHelp_chatsByKeywordExportPost',
		// 'view' => 'activeHelper_liveHelp_timeByChatViewPost', // use external function, since it already exists
		// 'viewExport' => 'activeHelper_liveHelp_timeByChatViewExportPost', // use external function, since it already exists
		// 'email' => 'activeHelper_liveHelp_timeByChatEmailPost' // use external function, since it already exists
	);

	if (!empty($_REQUEST['action']) && isset($actions[$_REQUEST['action']]))
		return $actions[$_REQUEST['action']]();

	if (empty($_REQUEST['action']))
		return $actions['list']();
}

function activeHelper_liveHelp_chatsByKeywordExportPost()
{
	global $wpdb, $activeHelper_liveHelp;

	$keyword = !empty($_REQUEST['export_keyword']) ? htmlspecialchars((string) $_REQUEST['export_keyword']) : '';

	$chatsByKeywordList = $wpdb->get_results("
		SELECT
			jls.id AS session, CONCAT(jlu.firstname, ' ', jlu.lastname) AS name, jld.name AS domain,
			jls.username AS visitor, jls.email AS email, if(jls.rating = -1,
			'" . __('Not rate', 'activehelper_livehelp') . "', jls.rating) AS rating,
			(TIMEDIFF(jls.refresh, jls.datetime)) AS time, DATE_FORMAT(jls.datetime, '%m/%d/%Y') AS date
		FROM
			{$wpdb->prefix}livehelp_messages AS jlm,
			{$wpdb->prefix}livehelp_sessions AS jls,
			{$wpdb->prefix}livehelp_users AS jlu,
			{$wpdb->prefix}livehelp_domains AS jld
		WHERE
			LOWER(jlm.message) LIKE LOWER('%" . $keyword . "%')
			AND jls.id = jlm.session
		GROUP BY 1 DESC
	", ARRAY_A);

	$export = '"ID","Agent","Domain name","Visitor name","Visitor email","Raiting","Duration","Date"';

	if (!empty($chatsByKeywordList))
		foreach ($chatsByKeywordList as $chatsByKeyword)
		{
			$export .= "\n" . '"' .
				$chatsByKeyword['session'] . '","' .
				$chatsByKeyword['name'] . '","' .
				$chatsByKeyword['domain'] . '","' .
				$chatsByKeyword['visitor'] . '","' .
				$chatsByKeyword['email'] . '","' .
				$chatsByKeyword['rating'] . '","' .
				$chatsByKeyword['time'] . '","' .
				$chatsByKeyword['date'] .
			'"';
		}

	header("Cache-Control: public");
	header("Content-Description: File Transfer");
	header("Content-Disposition: attachment; filename=LiveHelp_ChatsByKeyword_" . urlencode($keyword) . ".csv");
	header("Content-Type: text/csv; text/comma-separated-values");
	header("Content-Transfer-Encoding: binary");

	echo $export;
	exit();
}

function activeHelper_liveHelp_chatsByKeywordListPost()
{
	global $wpdb, $activeHelper_liveHelp;

	wp_enqueue_script('jquery');
	wp_enqueue_script('jquery-ui-core');
	wp_enqueue_script('jquery-ui-datepicker', $activeHelper_liveHelp['baseUrl'] . '/scripts/jquery.ui.datepicker.min.js', array('jquery', 'jquery-ui-core') );
	wp_enqueue_style('jquery.ui.theme', $activeHelper_liveHelp['baseUrl'] . '/scripts/smoothness/jquery-ui-1.8.16.custom.css');
}

function activeHelper_liveHelp_chatsByKeywordList()
{
	global $wpdb, $activeHelper_liveHelp;

	$keyword = !empty($_REQUEST['export_keyword']) ? htmlspecialchars((string) $_REQUEST['export_keyword']) : '';

	$chatsByKeywordList = $wpdb->get_results("
		SELECT
			jls.id AS session, CONCAT(jlu.firstname, ' ', jlu.lastname) AS name, jld.name AS domain,
			jls.username AS visitor, jls.email AS email, if(jls.rating = -1,
			'" . __('Not rate', 'activehelper_livehelp') . "', jls.rating) AS rating,
			(TIMEDIFF(jls.refresh, jls.datetime)) AS time, DATE_FORMAT(jls.datetime, '%m/%d/%Y') AS date
		FROM
			{$wpdb->prefix}livehelp_messages AS jlm,
			{$wpdb->prefix}livehelp_sessions AS jls,
			{$wpdb->prefix}livehelp_users AS jlu,
			{$wpdb->prefix}livehelp_domains AS jld
		WHERE
			LOWER(jlm.message) LIKE LOWER('%" . $keyword . "%')
			AND jls.id = jlm.session
		GROUP BY 1 DESC
	", ARRAY_A);

	echo '
<div class="wrap"><form action="admin.php?page=' . strtolower('activeHelper_liveHelp_chatsByKeyword') . '" method="post" accept-charset="utf-8" id="activeHelper_liveHelp_form">
	<h2 style="padding-right: 0;">
		LiveHelp » ' . __('Time by chat', 'activehelper_livehelp') . '
		<a class="button add-new-h2" href="admin.php?page=' . strtolower('activeHelper_liveHelp_chatsByKeyword') . '&amp;action=export&amp;type=csv" id="activeHelper_liveHelp_export">' . __('export to CSV', 'activehelper_livehelp') . '</a>
	</h2>';

	echo '
	<div class="metabox-holder" style="padding-bottom: 10px;">
		<div id="normal-sortables" class="meta-box-sortables ui-sortable">
			<div class="stuffbox postbox">
				<div class="inside" style="padding-top: 1ex; padding-bottom: 1ex;">
					<div style="float: left; height: 26px; line-height: 26px;">
						<label for="export_keyword">' . __('Keyword', 'activehelper_livehelp') . '</labe>
					</div>
					<div style="padding-left: 1ex; float: left; height: 26px; line-height: 26px;">
						<input style="background: #fff;" tabindex="6" maxlength="255" type="text" style="width: 140px;" value="' . $keyword . '" id="export_keyword" name="export_keyword" />
					</div>
					<div style="padding-left: 1ex; float: left; height: 26px; line-height: 26px;">
						<input name="submit" value="' . __('Search', 'activehelper_livehelp') . '" type="submit" accesskey="p" tabindex="4" class="button-primary">
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

			if (empty($chatsByKeywordList))
				echo '
				<tr valign="top" class="format-default">
					<td class="colspanchange" colspan="8"><p style="margin: 0; padding: .8ex; color: #888;">
						' . sprintf(__('No %s found.', 'activehelper_livehelp'), __('chats', 'activehelper_livehelp')) . '
					</p></td>
				</tr>';
			else
			{
				$alternate = false;
				foreach ($chatsByKeywordList as $chatsByKeyword)
				{
					echo '
				<tr valign="top" class="' . ($alternate ? 'alternate' : '') . ' format-default">
					<td style="padding: 1ex;">
						' . $chatsByKeyword['session'] . '
					</td>
					<td style="padding: 1ex;" class="post-title page-title column-title">
						<strong><a href="admin.php?page=' . strtolower('activeHelper_liveHelp_timeByChat') . '&amp;action=view&amp;id=' . $chatsByKeyword['session'] . '" class="row-title">
							' . $chatsByKeyword['name'] . '</a></strong>
						<div class="row-actions">
							<span class="edit"><a href="admin.php?page=' . strtolower('activeHelper_liveHelp_timeByChat') . '&amp;action=view&amp;id=' . $chatsByKeyword['session'] . '">
								' . __('View', 'activehelper_livehelp') . '</a></span>
						</div>
					</td>
					<td style="padding: 1ex;">
						' . $chatsByKeyword['domain'] . '
					</td>
					<td style="padding: 1ex;">
						' . $chatsByKeyword['visitor'] . '
					</td>
					<td style="padding: 1ex;">
						' . $chatsByKeyword['email'] . '
					</td>
					<td style="padding: 1ex;">
						' . $chatsByKeyword['rating'] . '
					</td>
					<td style="padding: 1ex;">
						' . $chatsByKeyword['time'] . '
					</td>
					<td style="padding: 1ex;">
						' . $chatsByKeyword['date'] . '
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
		var export_keyword = "' . $keyword . '";

		jQuery(function($){
			$("#activeHelper_liveHelp_export").click(function(){
				$(this).attr("href", $(this).attr("href") + "&export_keyword=" + export_keyword);
			});
		});
	</script>
</form></div>';
}

