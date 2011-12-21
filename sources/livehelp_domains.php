<?php
/**
 * @package ActiveHelper Live Help
 */

if (!defined('ACTIVEHELPER_LIVEHELP'))
	die('Hi there! I\'m just a plugin, not much I can do when called directly.');

function activeHelper_liveHelp_domains()
{
	global $wpdb, $activeHelper_liveHelp;

	$actions = array(
		'list' => 'activeHelper_liveHelp_domainsList',
		'edit' => 'activeHelper_liveHelp_domainsRegister',
		'register' => 'activeHelper_liveHelp_domainsRegister',
		'settings' => 'activeHelper_liveHelp_domainsSettings',
		'script' => 'activeHelper_liveHelp_domainsGenerateScript'
	);
	if (!empty($_REQUEST['action']) && isset($actions[$_REQUEST['action']]))
		return $actions[$_REQUEST['action']]();

	return $actions['list']();
}

function activeHelper_liveHelp_domainsPost()
{
	$actions = array(
		'delete' => 'activeHelper_liveHelp_domainsDeletePost',
		'edit' => 'activeHelper_liveHelp_domainsEditPost',
		'register' => 'activeHelper_liveHelp_domainsRegisterPost',
		'settings' => 'activeHelper_liveHelp_domainsSettingsPost',
		'widget' => 'activeHelper_liveHelp_domainsWidgetPost'
	);

	if (!empty($_REQUEST['action']) && isset($actions[$_REQUEST['action']]))
		return $actions[$_REQUEST['action']]();
}

function activeHelper_liveHelp_domainsWidgetPost()
{
	global $wpdb, $activeHelper_liveHelp;

	$_REQUEST['id'] = !empty($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;

	if (empty($_REQUEST['id']))
	{
		wp_redirect('admin.php?page=' . strtolower('activeHelper_liveHelp_domains') . '&miss');
		exit;
	}

	$domain = $wpdb->get_row("
		SELECT COUNT(*)
		FROM {$wpdb->prefix}livehelp_domains
		WHERE id_domain = '{$_REQUEST['id']}'
		LIMIT 1
	", ARRAY_A);
	if (empty($domain))
	{
		wp_redirect('admin.php?page=' . strtolower('activeHelper_liveHelp_domains') . '&miss');
		exit;
	}

	// update widget
	$widgetOriginal = $activeHelper_liveHelp['baseDir'] . '/widget/activehelper_livehelp_widget.php';
	$widgetDownload = $activeHelper_liveHelp['baseDir'] . '/widget/activehelper_livehelp_widget/activehelper_livehelp_widget.php';
	activeHelper_liveHelp_filesDelete($widgetDownload);
	activeHelper_liveHelp_filesDuplicate($widgetOriginal, $widgetDownload);

	$fhandle = fopen($widgetDownload, "r");
	$content = fread($fhandle, filesize($widgetDownload));

	$content = str_replace('{liveHelp_externalWidget_serverUrl}', $activeHelper_liveHelp['serverUrl'], $content);
	$content = str_replace('{liveHelp_externalWidget_domain}', $_REQUEST['id'], $content);

	$fhandle = fopen($widgetDownload, "w");
	fwrite($fhandle, $content);
	fclose($fhandle);

	activeHelper_liveHelp_filesDelete($activeHelper_liveHelp['baseDir'] . '/widget/activehelper_livehelp_widget.zip');
	activeHelper_liveHelp_filesZip($activeHelper_liveHelp['baseDir'] . '/widget/activehelper_livehelp_widget', $activeHelper_liveHelp['baseDir'] . '/widget/activehelper_livehelp_widget.zip');

	header("Cache-Control: public");
	header("Content-Description: File Transfer");
	header("Content-Disposition: attachment; filename=WordPress_LiveHelp_externalWidget.zip");
	header("Content-Type: application/octet-stream");
	header("Content-Transfer-Encoding: binary");
	header("Content-Length: ". filesize($activeHelper_liveHelp['baseDir'] . '/widget/activehelper_livehelp_widget.zip'));

	readfile($activeHelper_liveHelp['baseDir'] . '/widget/activehelper_livehelp_widget.zip');
	exit();
}

function activeHelper_liveHelp_domainsGenerateScript()
{
	global $wpdb, $activeHelper_liveHelp;

	$_REQUEST['id'] = !empty($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
	$domain = $wpdb->get_row("
		SELECT name AS domain_name, status AS domain_status,
			configuration AS domain_global_configuration
		FROM {$wpdb->prefix}livehelp_domains
		WHERE id_domain = '{$_REQUEST['id']}'
		LIMIT 1
	", ARRAY_A);
	$_POST = array_merge($_POST, $domain);

	$languages = $wpdb->get_results("
		SELECT l.code, l.name, IF(ISNULL(ld.welcome_message), 0, 1) AS status,
			IFNULL(ld.welcome_message, '') AS welcome_message
		FROM {$wpdb->prefix}livehelp_languages AS l
			LEFT JOIN {$wpdb->prefix}livehelp_languages_domain AS ld
				ON (ld.id_domain = '{$_REQUEST['id']}' AND ld.code = l.code)
	", ARRAY_A);
	$activeHelper_liveHelp['languages'] = $languages;

	$languages = array();
	foreach ($activeHelper_liveHelp['languages'] as $language)
		$languages[$language['code']] = $language['name'];

	$tabindex = 1;
	
	$generatedScript = '<script type="text/javascript" src="' . $activeHelper_liveHelp['serverUrl'] . '/import/javascript.php"></script>
<script type="text/javascript">
	_vlDomain = 1;
	_vlService = 1;
	_vlLanguage = "en";
	_vlTracking = 1;
	_vlStatus_indicator = 1;
	startLivehelp();
</script>';

	echo '
<div class="wrap">
	<h2 style="padding-right: 0;">
		LiveHelp » ' . __('Domains', 'activehelper_livehelp') . ' <span style="font-size: 70%;">(' . $_POST['domain_name'] . ')</span> » ' . __('Generate script', 'activehelper_livehelp') . '
	</h2>
		<div id="poststuff" class="metabox-holder has-right-sidebar">
			<div class="inner-sidebar"><div class="meta-box-sortables ui-sortable">
				<div id="submitdiv" class="postbox">
					<div class="handlediv" title="' . __('Click to toggle', 'activehelper_livehelp') . '"><br /></div>
					<h3 style="cursor: default;"><span style="cursor: default;">
						' . __('Generate script', 'activehelper_livehelp') . '</span></h3>
					<div class="inside"><div class="submitbox">
						<div id="major-publishing-actions" style="padding: 1ex;">
							<div id="delete-action">
								<a class="submitdelete deletion" href="admin.php?page=' . strtolower('activeHelper_liveHelp_domains') . '">' . __('Close', 'activehelper_livehelp') . '</a>
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
						' . __('Options', 'activehelper_livehelp') . '</h3>
					<div class="inside"><div id="postcustomstuff" style="padding: .6ex 0;">
						<table><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							' . __('Language', 'activehelper_livehelp') . '
						</th></thead><tbody><tr><td id="newmetaleft" class="left">
							<select size="1" id="script_language" style="width: 200px;" name="script_language" tabindex="' . $tabindex++ . '">';

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
        'bg' => __('Bulgarian', 'activehelper_livehelp')
	);

	foreach ($activeHelper_liveHelp['languages'] as $language)
		echo '
								<option value="' . $language['code'] . '" ' . ('en' == $language['code'] ? 'selected="selected"' : '') . '>
									' . $__text[$language['code']] . '</option>';

	echo '
							</select>
							<div style="clear: both;"></div>
						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							' . __('Tracking', 'activehelper_livehelp') . '
						</th></thead><tbody><tr><td id="newmetaleft" class="left" style="padding: 1ex;">
							<label style="margin-left: .5ex; display: block; float: left; margin-right: 1ex; line-height: 15px;">
								<input style="float: left;  margin: 0 .5ex 0 0; width: auto;"" tabindex="' . $tabindex++ . '" type="radio" id="script_tracking_enable" name="script_tracking" checked="checked" value="1" /> ' . __('Enable', 'activehelper_livehelp') . '</label>
							<label style="display: block; margin: 0 .5ex 0 0; float: left; line-height: 15px;">
								<input style="float: left;  margin: 0 .5ex 0 0; width: auto;"" tabindex="' . $tabindex++ . '" type="radio" id="script_tracking_disable" name="script_tracking" value="0" /> ' . __('Disable', 'activehelper_livehelp') . '</label>
							<div style="clear: both;"></div>
						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							' . __('Status indicator', 'activehelper_livehelp') . '
						</th></thead><tbody><tr><td id="newmetaleft" class="left" style="padding: 1ex;">
							<label style="margin-left: .5ex; display: block; float: left; margin-right: 1ex; line-height: 15px;">
								<input style="float: left;  margin: 0 .5ex 0 0; width: auto;"" tabindex="' . $tabindex++ . '" type="radio" id="script_status_enable" name="script_status" checked="checked" value="1" /> ' . __('Enable', 'activehelper_livehelp') . '</label>
							<label style="display: block; margin: 0 .5ex 0 0; float: left; line-height: 15px;">
								<input style="float: left;  margin: 0 .5ex 0 0; width: auto;"" tabindex="' . $tabindex++ . '" type="radio" id="script_status_disable" name="script_status" value="0" /> ' . __('Disable', 'activehelper_livehelp') . '</label>
							<div style="clear: both;"></div>
						</td></tr></tbody></table>
					</div></div>
				</div>
				<div class="stuffbox postbox">
					<div class="handlediv" title="' . __('Click to toggle', 'activehelper_livehelp') . '"><br /></div>
					<h3 style="cursor: default;">
						' . __('Generated script', 'activehelper_livehelp') . '</h3>
					<div class="inside"><div id="postcustomstuff" style="padding: .6ex 0;">
						<textarea tabindex="' . $tabindex++ . '" style="width: 98%; max-width: 98%; min-width: 98%; min-height: 200px; height: 200px;" name="script_generated" id="script_generated">' . htmlspecialchars($generatedScript) . '</textarea>
					</div></div>
				</div>
			</div></div></div>
			<br />
		</div>';

	echo '
	<script type="text/javascript">
		var scriptLanguage = "en";
		var scriptTracking = 1;
		var scriptStatus = 1;
		function generateScript()
		{
			var html = "<" + "script type=\"text/javascript\" src=\"' . $activeHelper_liveHelp['serverUrl'] . '/import/javascript.php\">";
			html += "</" + "script>\n";
			html += "<" + "script type=\"text/javascript\">\n";
			html += "	_vlDomain = 1;\n";
			html += "	_vlService = 1;\n";
			html += "	_vlLanguage = \"" + scriptLanguage + "\";\n";
			html += "	_vlTracking = " + scriptTracking + ";\n";
			html += "	_vlStatus_indicator = " + scriptStatus + ";\n";
			html += "	startLivehelp();\n";
			html += "</" + "script>";

			jQuery("#script_generated").val(html);
		}
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

			$("#script_status_enable").click(function(){ scriptStatus = 1; generateScript(); });
			$("#script_status_disable").click(function(){ scriptStatus = 0; generateScript(); });

			$("#script_tracking_enable").click(function(){ scriptTracking = 1; generateScript(); });
			$("#script_tracking_disable").click(function(){ scriptTracking = 0; generateScript(); });

			$("#script_language").change(function(){ scriptLanguage = $(this).val(); generateScript(); });
		});
	</script>
</div>';
}

function activeHelper_liveHelp_domainsList()
{
	global $wpdb, $activeHelper_liveHelp;

	$domainsList = $wpdb->get_results("
		SELECT id_domain AS ID, name AS domain_name, status AS domain_status,
			configuration AS domain_global_configuration
		FROM {$wpdb->prefix}livehelp_domains
		ORDER BY id_domain
	", ARRAY_A);

	echo '
<div class="wrap">
	<h2 style="padding-right: 0;">
		LiveHelp » ' . __('Domains', 'activehelper_livehelp') . '
		<a class="button add-new-h2" href="admin.php?page=' . strtolower('activeHelper_liveHelp_domains') . '&amp;action=register">' . __('add new', 'activehelper_livehelp') . '</a>
	</h2>';

	if (isset($_GET['register']))
		echo '
	<div class="updated below-h2" id="message">
		<p>' . sprintf(__('The %s was successfully registered.', 'activehelper_livehelp'), __('domain', 'activehelper_livehelp')) . '</p>
	</div>';

	if (isset($_GET['update']))
		echo '
	<div class="updated below-h2" id="message">
		<p>' . sprintf(__('The %s was successfully updated.', 'activehelper_livehelp'), __('domain', 'activehelper_livehelp')) . '</p>
	</div>';

	if (isset($_GET['delete']))
		echo '
	<div class="error below-h2" id="message">
		<p>' . sprintf(__('The %s was deleted permanently.', 'activehelper_livehelp'), __('domain', 'activehelper_livehelp')) . '</p>
	</div>';

	if (isset($_GET['miss']))
		echo '
	<div class="error below-h2" id="message">
		<p>' . sprintf(__('The %s was not found.', 'activehelper_livehelp'), __('domain', 'activehelper_livehelp')) . '</p>
	</div>';

	echo '
	<div class="metabox-holder" style="padding-bottom: 10px;">
		<table cellspacing="0" class="wp-list-table widefat fixed">
			<thead>
				<tr>
					<th style="width: 50px" class="manage-column" scope="col">
						' . __('ID', 'activehelper_livehelp') . '</th>
					<th class="manage-column" scope="col">
						' . __('Name', 'activehelper_livehelp') . '</th>
					<th style="text-align: center; width: 150px" class="manage-column" scope="col">
						' . __('Tracking widget', 'activehelper_livehelp') . '</th>
					<th style="text-align: center; width: 150px" class="manage-column" scope="col">
						' . __('Tracking script', 'activehelper_livehelp') . '</th>
					<th style="text-align: center; width: 100px" class="manage-column" scope="col">
						' . __('Status', 'activehelper_livehelp') . '</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th class="manage-column" scope="col">
						' . __('ID', 'activehelper_livehelp') . '</th>
					<th class="manage-column" scope="col">
						' . __('Name', 'activehelper_livehelp') . '</th>
					<th style="text-align: center;" class="manage-column" scope="col">
						' . __('Tracking widget', 'activehelper_livehelp') . '</th>
					<th style="text-align: center;" class="manage-column" scope="col">
						' . __('Tracking script', 'activehelper_livehelp') . '</th>
					<th style="text-align: center;" class="manage-column" scope="col">
						' . __('Status', 'activehelper_livehelp') . '</th>
				</tr>
			</tfoot>
			<tbody id="the-list">';

			if (empty($domainsList))
				echo '
				<tr valign="top" class="format-default">
					<td class="colspanchange" colspan="5"><p style="margin: 0; padding: .8ex; color: #888;">
						' . sprintf(__('No %s found.', 'activehelper_livehelp'), __('domains', 'activehelper_livehelp')) . '
					</p></td>
				</tr>';
			else
			{
				$alternate = false;
				foreach ($domainsList as $domain)
				{
					echo '
				<tr valign="top" class="' . ($alternate ? 'alternate' : '') . ' format-default">
					<td>
						' . $domain['ID'] . '
					</td>
					<td class="post-title page-title column-title" style="vertical-align: middle;">
						<strong><a href="admin.php?page=' . strtolower('activeHelper_liveHelp_domains') . '&amp;action=edit&amp;id=' . $domain['ID'] . '" class="row-title">
							' . $domain['domain_name'] . '</a></strong>
						<div class="row-actions">
							<span class="edit"><a href="admin.php?page=' . strtolower('activeHelper_liveHelp_domains') . '&amp;action=edit&amp;id=' . $domain['ID'] . '">
								' . __('Edit', 'activehelper_livehelp') . '</a> | </span>
							<span class="edit"><a href="admin.php?page=' . strtolower('activeHelper_liveHelp_domains') . '&amp;action=settings&amp;id=' . $domain['ID'] . '">
								' . __('Settings', 'activehelper_livehelp') . '</a> | </span>
							<span class="trash"><a href="admin.php?page=' . strtolower('activeHelper_liveHelp_domains') . '&amp;action=delete&amp;id=' . $domain['ID'] . '" class="submitdelete" onclick="return window.confirm(\'' . __('Are you sure you want to delete this item permanently?', 'activehelper_livehelp') . '\');">
								' . __('Delete', 'activehelper_livehelp') . '</a></span>
						</div>
					</td>
					<td style="text-align: center;">' . (strstr(get_bloginfo('url'), '//' . $domain['domain_name']) ? '
						<a href="widgets.php">
							' . __('Configure widget', 'activehelper_livehelp') . '</a>
						<br />' : '') . '<a href="admin.php?page=' . strtolower('activeHelper_liveHelp_domains') . '&amp;action=widget&amp;id=' . $domain['ID'] . '">
							' . __('Download widget', 'activehelper_livehelp') . '</a>
					</td>
					<td style="text-align: center;">
						<a href="admin.php?page=' . strtolower('activeHelper_liveHelp_domains') . '&amp;action=script&amp;id=' . $domain['ID'] . '">
							' . __('Generate script', 'activehelper_livehelp') . '</a>
					</td>
					<td style="text-align: center;">
						' . ($domain['domain_status'] == 1 ? __('Enable', 'activehelper_livehelp') : __('Disable', 'activehelper_livehelp')) . '
					</td>
				</tr>';

					$alternate = !$alternate;
				}
			}

			echo '
			</tbody>
		</table>
	</div>
</div>';
}

function activeHelper_liveHelp_domainsDeletePost()
{
	global $wpdb, $activeHelper_liveHelp;

	$_REQUEST['id'] = !empty($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;

	if (empty($_REQUEST['id']))
	{
		wp_redirect('admin.php?page=' . strtolower('activeHelper_liveHelp_domains') . '&miss');
		exit;
	}

	$domain = $wpdb->get_row("
		SELECT COUNT(*)
		FROM {$wpdb->prefix}livehelp_domains
		WHERE id_domain = '{$_REQUEST['id']}'
		LIMIT 1
	", ARRAY_A);
	if (empty($domain))
	{
		wp_redirect('admin.php?page=' . strtolower('activeHelper_liveHelp_domains') . '&miss');
		exit;
	}

	$wpdb->query("
		DELETE FROM {$wpdb->prefix}livehelp_accounts_domain
		WHERE id_domain = '{$_REQUEST['id']}'
	");
	$wpdb->query("
		DELETE FROM {$wpdb->prefix}livehelp_languages_domain
		WHERE Id_domain = '{$_REQUEST['id']}'
	");
	$wpdb->query("
		DELETE FROM {$wpdb->prefix}livehelp_settings
		WHERE id_domain = '{$_REQUEST['id']}'
	");

	activeHelper_liveHelp_filesDelete($activeHelper_liveHelp['domainsDir'] . '/' . $_REQUEST['id']);

	wp_redirect('admin.php?page=' . strtolower('activeHelper_liveHelp_domains') . '&delete');
	exit;
}

function activeHelper_liveHelp_domainsRegisterPost()
{
	global $wpdb, $activeHelper_liveHelp;

	$_POST['domain_name'] = !empty($_POST['domain_name']) ? (string) $_POST['domain_name'] : '';
	$_POST['domain_status'] = !empty($_POST['domain_status']) ? 1 : 0;
	$_POST['domain_global_configuration'] = !empty($_POST['domain_global_configuration']) ? (string) $_POST['domain_global_configuration'] : '';

	$errors = array();
	$activeHelper_liveHelp['errors'] = &$errors;

	while (isset($_POST['submit']))
	{
		unset($_POST['submit']);

		if (empty($_POST['domain_name']))
			$errors['domain_name'] = sprintf(__('You must insert a %s', 'activehelper_livehelp'), __('name', 'activehelper_livehelp')); // error

		// errors ...
		if (!empty($errors))
			break;

		$wpdb->query("
			INSERT INTO {$wpdb->prefix}livehelp_domains
				(name, status, configuration)
			VALUES
				('{$_POST['domain_name']}', '{$_POST['domain_status']}', '{$_POST['domain_global_configuration']}')
		");

		$insert_id = $wpdb->get_row("
			SELECT id_domain AS ID
			FROM {$wpdb->prefix}livehelp_domains
			ORDER BY id_domain DESC
			LIMIT 1
		", ARRAY_A);
		$insert_id = $insert_id['ID'];

		$settingsQuery = activeHelper_liveHelp_domainsSettingsQuery($insert_id);
		$wpdb->query($settingsQuery);

		activeHelper_liveHelp_filesDuplicate($activeHelper_liveHelp['domainsDir'] . '/0',
			$activeHelper_liveHelp['domainsDir'] . '/' . $insert_id);

		$wpdb->query("
			INSERT INTO {$wpdb->prefix}livehelp_languages_domain
				(Id_domain, code, name, welcome_message)
			VALUES
				('{$insert_id}', 'en', 'English', 'Welcome to our LiveHelp, one moment please.')
		");
		$wpdb->query("
			INSERT INTO {$wpdb->prefix}livehelp_accounts_domain
				(id_account, id_domain, status)
			VALUES
				('1', '{$insert_id}', '1')
		");

		wp_redirect('admin.php?page=' . strtolower('activeHelper_liveHelp_domains') . '&register');
		exit;
	}
}

function activeHelper_liveHelp_domainsEditPost()
{
	global $wpdb, $activeHelper_liveHelp;

	$_REQUEST['id'] = !empty($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
	$_POST['domain_name'] = !empty($_POST['domain_name']) ? (string) $_POST['domain_name'] : '';
	$_POST['domain_status'] = !empty($_POST['domain_status']) ? 1 : 0;
	$_POST['domain_global_configuration'] = !empty($_POST['domain_global_configuration']) ? (string) $_POST['domain_global_configuration'] : '';

	if (empty($_REQUEST['id']))
	{
		wp_redirect('admin.php?page=' . strtolower('activeHelper_liveHelp_domains') . '&miss');
		exit;
	}

	$errors = array();
	$activeHelper_liveHelp['errors'] = &$errors;

	$domain = $wpdb->get_row("
		SELECT name AS domain_name, status AS domain_status,
			configuration AS domain_global_configuration
		FROM {$wpdb->prefix}livehelp_domains
		WHERE id_domain = '{$_REQUEST['id']}'
		LIMIT 1
	", ARRAY_A);
	if (empty($domain))
	{
		wp_redirect('admin.php?page=' . strtolower('activeHelper_liveHelp_domains') . '&miss');
		exit;
	}

	if (!isset($_POST['submit']))
		$_POST = array_merge($_POST, $domain);

	while (isset($_POST['submit']))
	{
		unset($_POST['submit']);

		if (empty($_POST['domain_name']))
			$errors['domain_name'] = sprintf(__('You must insert a %s', 'activehelper_livehelp'), __('name', 'activehelper_livehelp')); // error

		// errors ...
		if (!empty($errors))
			break;

		$wpdb->query("
			UPDATE {$wpdb->prefix}livehelp_domains
			SET name = '{$_POST['domain_name']}',
				status = '{$_POST['domain_status']}',
				configuration = '{$_POST['domain_global_configuration']}'
			WHERE id_domain = '{$_REQUEST['id']}'
			LIMIT 1
		");

		wp_redirect('admin.php?page=' . strtolower('activeHelper_liveHelp_domains') . '&update');
		exit;
	}
}

function activeHelper_liveHelp_domainsRegister()
{
	global $activeHelper_liveHelp;

	if (!empty($activeHelper_liveHelp['errors']))
		$errors = $activeHelper_liveHelp['errors'];

	$tabindex = 1;
	echo '
<div class="wrap">
	<div id="icon-edit" class="icon32 icon32-posts-post"><br /></div>
	<h2>
		LiveHelp » ' . __('Domains', 'activehelper_livehelp') . (!empty($_REQUEST['id']) ? ' <span style="font-size: 70%;">(' . $_POST['domain_name'] . ')</span>' : '') . ' » ' . (!empty($_REQUEST['id']) ? __('Edit', 'activehelper_livehelp') : __('Add new', 'activehelper_livehelp')) . '
	</h2>
	<form action="admin.php?page=' . strtolower('activeHelper_liveHelp_domains') . '&amp;action=' . (!empty($_REQUEST['id']) ? 'edit': 'register') . '" method="post" accept-charset="utf-8" id="activeHelper_liveHelp_form" enctype="multipart/form-data">
		<div id="poststuff" class="metabox-holder has-right-sidebar">
			<div class="inner-sidebar"><div class="meta-box-sortables ui-sortable">
				<div id="submitdiv" class="postbox">
					<div class="handlediv" title="' . __('Click to toggle', 'activehelper_livehelp') . '"><br /></div>
					<h3 style="cursor: default;"><span style="cursor: default;">
						' . (!empty($_REQUEST['id']) ? __('Update', 'activehelper_livehelp') : __('Add new', 'activehelper_livehelp')) . '</span></h3>
					<div class="inside"><div class="submitbox">
						<div id="major-publishing-actions" style="padding: 1ex;">
							<div id="delete-action">
								<a class="submitdelete deletion" href="admin.php?page=' . strtolower('activeHelper_liveHelp_domains') . '">' . __('Cancel', 'activehelper_livehelp') . '</a>
							</div>
							<div id="publishing-action">
								<input name="submit" value="' . (!empty($_REQUEST['id']) ? __('Update', 'activehelper_livehelp') : __('Add new', 'activehelper_livehelp')) . '" type="submit" accesskey="p" tabindex="999" class="button-primary">
							</div>
							<div class="clear"></div>
						</div>
						<div class="clear"></div>
					</div></div>
				</div>
			</div></div>
			<div id="post-body"><div id="post-body-content">
				<div class="stuffbox postbox">
					<h3 style="cursor: default;"><label for="domain_name">
						' . __('Name', 'activehelper_livehelp') . '</label></h3>
					<div class="inside">
						<input maxlength="255" type="text" id="domain_name" value="' . $_POST['domain_name'] . '" tabindex="' . $tabindex++ . '" size="30" name="domain_name" style="width: 98%">
						<p>' . __('Example', 'activehelper_livehelp') . ': <code>www.activehelper.com</code></p>' . (isset($errors['domain_name']) ? '
						<p style="color: #f00;">' . __('Error', 'activehelper_livehelp') . ': <code style="background-color: #FAF0F0;">' . $errors['domain_name'] . '</code></p>' : '') . '
					</div>
				</div>
			<div class="meta-box-sortables ui-sortable">
				<div class="stuffbox postbox">
					<div class="handlediv" title="' . __('Click to toggle', 'activehelper_livehelp') . '"><br /></div>
					<h3 style="cursor: default;">' . __('Status', 'activehelper_livehelp') . '</h3>
					<div class="inside">
						<label style="display: block; float: left; margin-right: 1ex; line-height: 18px;">
							<input style="float: left; margin-right: .5ex;" tabindex="' . $tabindex++ . '" type="radio" name="domain_status" ' . (!empty($_POST['domain_status']) ? 'checked="checked"' : '') . ' value="1" /> ' . __('Enable', 'activehelper_livehelp') . '</label>
						<label style="display: block; float: left; line-height: 18px;">
							<input style="float: left; margin-right: .5ex;" tabindex="' . $tabindex++ . '" type="radio" name="domain_status" ' . (empty($_POST['domain_status']) ? 'checked="checked"' : '') . ' value="0" /> ' . __('Disable', 'activehelper_livehelp') . '</label>
						<div style="clear: both;"></div>
					</div>
				</div>
				<!-- <div class="stuffbox postbox">
					<div class="handlediv" title="' . __('Click to toggle', 'activehelper_livehelp') . '"><br /></div>
					<h3 style="cursor: default;"><label for="domain_global_configuration">
						' . __('Global Configuration', 'activehelper_livehelp') . '</label></h3>
					<div class="inside">
						<textarea tabindex="' . $tabindex++ . '" style="width: 98%; max-width: 98%; min-width: 98%; min-height: 100px; height: 100px;" name="domain_global_configuration" id="domain_global_configuration">' . $_POST['domain_global_configuration'] . '</textarea>' . (isset($errors['domain_global_configuration']) ? '
						<p style="color: #f00;">' . __('Error', 'activehelper_livehelp') . ': <code style="background-color: #FAF0F0;">' . $errors['domain_global_configuration'] . '</code></p>' : '') . '
					</div>
				</div> -->
			</div></div></div>
			<br />
		</div>';

	if (!empty($_REQUEST['id']))
		echo '
		<input type="hidden" name="id" value="' . $_REQUEST['id'] . '" />';

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

function activeHelper_liveHelp_domainsSettingsPost()
{
	global $wpdb, $activeHelper_liveHelp;

	$_REQUEST['id'] = !empty($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;

	if (empty($_REQUEST['id']))
	{
		wp_redirect('admin.php?page=' . strtolower('activeHelper_liveHelp_domains') . '&miss');
		exit;
	}

	$errors = array();
	$activeHelper_liveHelp['errors'] = &$errors;

	$domain = $wpdb->get_row("
		SELECT name AS domain_name, status AS domain_status,
			configuration AS domain_global_configuration
		FROM {$wpdb->prefix}livehelp_domains
		WHERE id_domain = '{$_REQUEST['id']}'
		LIMIT 1
	", ARRAY_A);
	if (empty($domain))
	{
		wp_redirect('admin.php?page=' . strtolower('activeHelper_liveHelp_domains') . '&miss');
		exit;
	}
	$domain['domain_language'] = 'en'; // fix!
	$activeHelper_liveHelp['domain'] = $domain;

	$languages = $wpdb->get_results("
		SELECT l.code, l.name, IF(ISNULL(ld.welcome_message), 0, 1) AS status,
			IFNULL(ld.welcome_message, '') AS welcome_message
		FROM {$wpdb->prefix}livehelp_languages AS l
			LEFT JOIN {$wpdb->prefix}livehelp_languages_domain AS ld
				ON (ld.id_domain = '{$_REQUEST['id']}' AND ld.code = l.code)
	", ARRAY_A);
	$activeHelper_liveHelp['languages'] = $languages;

	$languages = array();
	foreach ($activeHelper_liveHelp['languages'] as $language)
		$languages[$language['code']] = $language['name'];

	$settings = $wpdb->get_results("
		SELECT name, value FROM {$wpdb->prefix}livehelp_settings
		WHERE id_domain = '{$_REQUEST['id']}'
	", ARRAY_A);

	$settingsValues = array();
	foreach ($settings as $setting)
		$settingsValues[$setting['name']] = $setting['value'];
	if (!isset($_POST['submit']))
	{
		$_POST = $settingsValues;
	}
	else
	{
		$_POST['campaign_image'] = $settingsValues['campaign_image'];
		$_POST['chat_button_img'] = $settingsValues['chat_button_img'];
		$_POST['chat_button_hover_img'] = $settingsValues['chat_button_hover_img'];
		$_POST['chat_invitation_img'] = $settingsValues['chat_invitation_img'];
		$_POST['company_logo'] = $settingsValues['company_logo'];
	}

	while (isset($_POST['submit']))
	{
		unset($_POST['submit']);

		$domainsPicturesDir = $activeHelper_liveHelp['domainsDir'] . '/' . $_REQUEST['id'] . '/i18n/' . $_POST['domain_language_selector']  . '/pictures';
		while (!empty($_FILES['campaign_image']['tmp_name']))
		{
			if (!empty($_POST['campaign_image']))
				activeHelper_liveHelp_imagesDelete($domainsPicturesDir, $_POST['campaign_image']);

			$image = activeHelper_liveHelp_imagesUpload($domainsPicturesDir, 'chat_banner', $_FILES['campaign_image']);
			unset($_FILES['campaign_image']);

			if ($image === false)
				break;

			$_POST['campaign_image'] = $image;
		}
		while (!empty($_FILES['chat_button_img']['tmp_name']))
		{
			if (!empty($_POST['chat_button_img']))
				activeHelper_liveHelp_imagesDelete($domainsPicturesDir, $_POST['chat_button_img']);

			$image = activeHelper_liveHelp_imagesUpload($domainsPicturesDir, 'send', $_FILES['chat_button_img']);
			unset($_FILES['chat_button_img']);

			if ($image === false)
				break;

			$_POST['chat_button_img'] = $image;
		}
		while (!empty($_FILES['chat_button_hover_img']['tmp_name']))
		{
			if (!empty($_POST['chat_button_hover_img']))
				activeHelper_liveHelp_imagesDelete($domainsPicturesDir, $_POST['chat_button_hover_img']);

			$image = activeHelper_liveHelp_imagesUpload($domainsPicturesDir, 'send_hover', $_FILES['chat_button_hover_img']);
			unset($_FILES['chat_button_hover_img']);

			if ($image === false)
				break;

			$_POST['chat_button_hover_img'] = $image;
		}
		while (!empty($_FILES['chat_invitation_img']['tmp_name']))
		{
			if (!empty($_POST['chat_invitation_img']))
				activeHelper_liveHelp_imagesDelete($domainsPicturesDir, $_POST['chat_invitation_img']);

			$image = activeHelper_liveHelp_imagesUpload($domainsPicturesDir, 'initiate_dialog', $_FILES['chat_invitation_img']);
			unset($_FILES['chat_invitation_img']);

			if ($image === false)
				break;

			$_POST['chat_invitation_img'] = $image;
		}
		while (!empty($_FILES['company_logo']['tmp_name']))
		{
			if (!empty($_POST['company_logo']))
				activeHelper_liveHelp_imagesDelete($domainsPicturesDir, $_POST['company_logo']);

			$image = activeHelper_liveHelp_imagesUpload($domainsPicturesDir, 'logo', $_FILES['company_logo']);
			unset($_FILES['company_logo']);

			if ($image === false)
				break;

			$_POST['company_logo'] = $image;
		}

		$settingsPost = array();
		foreach ($_POST as $name => $value)
			if (strpos($name, 'domain_') === false)
				$settingsPost[$name] = $value;

		$languageStatus = array();
		$languageMessages = array();
		if (!empty($_POST['domain_language_status']))
			foreach ($_POST['domain_language_status'] as $name => $value)
				$languageStatus[$name] = !empty($value);

		if (!empty($_POST['domain_language_message']))
			foreach ($_POST['domain_language_message'] as $name => $value)
				$languageMessages[$name] = $value;

		foreach ($settingsPost as $name => $value)
			$wpdb->query("
				UPDATE {$wpdb->prefix}livehelp_settings
				SET value = '{$value}'
				WHERE name = '{$name}' AND id_domain = '{$_REQUEST['id']}'
			");

		foreach ($languageStatus as $language => $status)
			if (!$status)
				$wpdb->query("
					DELETE FROM {$wpdb->prefix}livehelp_languages_domain
					WHERE code = '{$language}' AND Id_domain = '{$_REQUEST['id']}'
				");

		foreach ($languageMessages as $language => $message)
			if (!empty($languageStatus[$language]))
				$wpdb->query("
					REPLACE INTO {$wpdb->prefix}livehelp_languages_domain
						(Id_domain, name, code, welcome_message)
					VALUES
						('{$_REQUEST['id']}', '{$languages[$language]}', '{$language}', '{$message}')
				");

		while (!empty($_FILES['domain_image_online']['tmp_name']))
		{
			activeHelper_liveHelp_imagesUpload($domainsPicturesDir, 'online', $_FILES['domain_image_online'], '.gif');
			unset($_FILES['domain_image_online']);
		}
		while (!empty($_FILES['domain_image_offline']['tmp_name']))
		{
			activeHelper_liveHelp_imagesUpload($domainsPicturesDir, 'offline', $_FILES['domain_image_offline'], '.gif');
			unset($_FILES['domain_image_offline']);
		}
		while (!empty($_FILES['domain_image_away']['tmp_name']))
		{
			activeHelper_liveHelp_imagesUpload($domainsPicturesDir, 'away', $_FILES['domain_image_away'], '.gif');
			unset($_FILES['domain_image_away']);
		}
		while (!empty($_FILES['domain_image_brb']['tmp_name']))
		{
			activeHelper_liveHelp_imagesUpload($domainsPicturesDir, 'brb', $_FILES['domain_image_brb'], '.gif');
			unset($_FILES['domain_image_brb']);
		}

		wp_redirect('admin.php?page=' . strtolower('activeHelper_liveHelp_domains') . '&update');
		exit;
	}
}

function activeHelper_liveHelp_domainsSettings()
{
	global $activeHelper_liveHelp;

	if (!empty($activeHelper_liveHelp['errors']))
		$errors = $activeHelper_liveHelp['errors'];

	$tabindex = 1;

	echo '
<div class="wrap">
	<div id="icon-edit" class="icon32 icon32-posts-post"><br /></div>
	<h2>
		LiveHelp » ' . __('Domains', 'activehelper_livehelp') . (!empty($_REQUEST['id']) ? ' <span style="font-size: 70%;">(' . $activeHelper_liveHelp['domain']['domain_name'] . ')</span>' : '') . ' » ' . __('Settings', 'activehelper_livehelp') . '
	</h2>
	<form action="admin.php?page=' . strtolower('activeHelper_liveHelp_domains') . '&amp;action=settings" method="post" accept-charset="utf-8" id="activeHelper_liveHelp_form" enctype="multipart/form-data">
		<div id="poststuff" class="metabox-holder has-right-sidebar">
			<div class="inner-sidebar"><div class="meta-box-sortables ui-sortable">
				<div id="submitdiv" class="postbox">
					<div class="handlediv" title="' . __('Click to toggle', 'activehelper_livehelp') . '"><br /></div>
					<h3 style="cursor: default;"><span style="cursor: default;">
						' . __('Update', 'activehelper_livehelp') . '</span></h3>
					<div class="inside"><div class="submitbox">
						<div id="major-publishing-actions" style="padding: 1ex;">
							<div id="delete-action">
								<a class="submitdelete deletion" href="admin.php?page=' . strtolower('activeHelper_liveHelp_domains') . '">' . __('Cancel', 'activehelper_livehelp') . '</a>
							</div>
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
					<h3 style="cursor: pointer;">' . __('Settings', 'activehelper_livehelp') . '</h3>
					<div class="inside"><div id="postcustomstuff" style="padding: .6ex 0;">

						<table><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							' . __('Images\' language', 'activehelper_livehelp') . '
						</th></thead><tbody><tr><td id="newmetaleft" class="left">
							<select size="1" id="domain_language_selector" style="width: 200px;" name="domain_language_selector" tabindex="' . $tabindex++ . '">';

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
        'bg' => __('Bulgarian', 'activehelper_livehelp')
	);

	foreach ($activeHelper_liveHelp['languages'] as $language)
		echo '
								<option value="' . $language['code'] . '" ' . ($_POST['domain_language_selector'] == $language['code'] ? 'selected="selected"' : '') . '>
									' . $__text[$language['code']] . '</option>';

	echo '
							</select>
							<div style="clear: both;"></div>
							<p style="padding-left: 10px;">' . __('Select the language you would like for to edit the images below', 'activehelper_livehelp') . '</p>
						</td></tr></tbody></table>

					</div></div>
				</div>
				<div class="stuffbox postbox">
					<div class="handlediv" title="' . __('Click to toggle', 'activehelper_livehelp') . '"><br /></div>
					<h3 style="cursor: pointer;">' . __('General', 'activehelper_livehelp') . '</h3>
					<div class="inside" style="display: none;"><div id="postcustomstuff" style="padding: .6ex 0;">
						<table><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							<label for="livehelp_name">' . __('LiveHelp Name', 'activehelper_livehelp') . '</label>
						</th></thead><tbody><tr><td id="newmetaleft" class="left">
							<input tabindex="' . $tabindex++ . '" maxlength="255" type="text" style="width: 96%;" value="' . $_POST['livehelp_name'] . '" id="livehelp_name" name="livehelp_name" />
						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							<label for="site_name">' . __('Site Name', 'activehelper_livehelp') . '</label>
						</th></thead><tbody><tr><td id="newmetaleft" class="left">
							<input tabindex="' . $tabindex++ . '" maxlength="255" type="text" style="width: 96%;" value="' . $_POST['site_name'] . '" id="site_name" name="site_name" />
						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							<label for="site_address">' . __('Site Address', 'activehelper_livehelp') . '</label>
						</th></thead><tbody><tr><td id="newmetaleft" class="left">
							<input tabindex="' . $tabindex++ . '" maxlength="255" type="text" style="width: 96%;" value="' . $_POST['site_address'] . '" id="site_address" name="site_address" />
						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							' . __('Departments', 'activehelper_livehelp') . '
						</th></thead><tbody><tr><td id="newmetaleft" class="left" style="padding: 1ex;">
							<label style="margin-left: .5ex; display: block; float: left; margin-right: 1ex; line-height: 15px;">
								<input tabindex="' . $tabindex++ . '" style="float: left; margin: 0 .5ex 0 0; width: auto;" type="radio" name="departments" ' . (!empty($_POST['departments']) ? 'checked="checked"' : '') . ' value="1" /> ' . __('Enable', 'activehelper_livehelp') . '</label>
							<label style="display: block; margin: 0 .5ex 0 0; float: left; line-height: 15px;">
								<input tabindex="' . $tabindex++ . '" style="float: left;  margin: 0 .5ex 0 0; width: auto;" type="radio" name="departments" ' . (empty($_POST['departments']) ? 'checked="checked"' : '') . ' value="0" /> ' . __('Disable', 'activehelper_livehelp') . '</label>
							<div style="clear: both;"></div>
						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							' . __('Disable Geolocation', 'activehelper_livehelp') . '
						</th></thead><tbody><tr><td id="newmetaleft" class="left" style="padding: 1ex;">
							<label style="margin-left: .5ex; display: block; float: left; margin-right: 1ex; line-height: 15px;">
								<input tabindex="' . $tabindex++ . '" style="float: left; margin: 0 .5ex 0 0; width: auto;" type="radio" name="disable_geolocation" ' . (!empty($_POST['disable_geolocation']) ? 'checked="checked"' : '') . ' value="1" /> ' . __('Enable', 'activehelper_livehelp') . '</label>
							<label style="display: block; margin: 0 .5ex 0 0; float: left; line-height: 15px;">
								<input tabindex="' . $tabindex++ . '" style="float: left;  margin: 0 .5ex 0 0; width: auto;" type="radio" name="disable_geolocation" ' . (empty($_POST['disable_geolocation']) ? 'checked="checked"' : '') . ' value="0" /> ' . __('Disable', 'activehelper_livehelp') . '</label>
							<div style="clear: both;"></div>
						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							' . __('Disable status indicator in offline mode', 'activehelper_livehelp') . '
						</th></thead><tbody><tr><td id="newmetaleft" class="left" style="padding: 1ex;">
							<label style="margin-left: .5ex; display: block; float: left; margin-right: 1ex; line-height: 15px;">
								<input tabindex="' . $tabindex++ . '" style="float: left; margin: 0 .5ex 0 0; width: auto;" type="radio" name="disable_tracking_offline" ' . (!empty($_POST['disable_tracking_offline']) ? 'checked="checked"' : '') . ' value="1" /> ' . __('Enable', 'activehelper_livehelp') . '</label>
							<label style="display: block; margin: 0 .5ex 0 0; float: left; line-height: 15px;">
								<input tabindex="' . $tabindex++ . '" style="float: left;  margin: 0 .5ex 0 0; width: auto;" type="radio" name="disable_tracking_offline" ' . (empty($_POST['disable_tracking_offline']) ? 'checked="checked"' : '') . ' value="0" /> ' . __('Disable', 'activehelper_livehelp') . '</label>
							<div style="clear: both;"></div>
						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							' . __('Captcha', 'activehelper_livehelp') . '
						</th></thead><tbody><tr><td id="newmetaleft" class="left" style="padding: 1ex;">
							<label style="margin-left: .5ex; display: block; float: left; margin-right: 1ex; line-height: 15px;">
								<input tabindex="' . $tabindex++ . '" style="float: left; margin: 0 .5ex 0 0; width: auto;" type="radio" name="captcha" ' . (!empty($_POST['captcha']) ? 'checked="checked"' : '') . ' value="1" /> ' . __('Enable', 'activehelper_livehelp') . '</label>
							<label style="display: block; margin: 0 .5ex 0 0; float: left; line-height: 15px;">
								<input tabindex="' . $tabindex++ . '" style="float: left;  margin: 0 .5ex 0 0; width: auto;" type="radio" name="captcha" ' . (empty($_POST['captcha']) ? 'checked="checked"' : '') . ' value="0" /> ' . __('Disable', 'activehelper_livehelp') . '</label>
							<div style="clear: both;"></div>
						</td></tr></tbody></table>
					</div></div>
				</div>

				<div class="stuffbox postbox">
					<div class="handlediv" title="' . __('Click to toggle', 'activehelper_livehelp') . '"><br /></div>
					<h3 style="cursor: pointer;">' . __('Display', 'activehelper_livehelp') . '</h3>
					<div class="inside" style="display: none;"><div id="postcustomstuff" style="padding: .6ex 0;">
						<table><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							<label for="background_color">' . __('Background color', 'activehelper_livehelp') . '</label>
						</th></thead><tbody><tr><td id="newmetaleft" class="left">
							<input tabindex="' . $tabindex++ . '" maxlength="255" type="text" style="width: 96%;" value="' . $_POST['background_color'] . '" id="background_color" name="background_color" />
						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							<label for="chat_font_type">' . __('Chat font type', 'activehelper_livehelp') . '</label>
						</th></thead><tbody><tr><td id="newmetaleft" class="left">
							<input tabindex="' . $tabindex++ . '" maxlength="255" type="text" style="width: 96%;" value="' . $_POST['chat_font_type'] . '" id="chat_font_type" name="chat_font_type" />
						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							<label for="guest_chat_font_size">' . __('Guest chat font size', 'activehelper_livehelp') . '</label>
						</th></thead><tbody><tr><td id="newmetaleft" class="left">
							<input tabindex="' . $tabindex++ . '" maxlength="255" type="text" style="width: 96%;" value="' . $_POST['guest_chat_font_size'] . '" id="v" name="guest_chat_font_size" />
						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							<label for="admin_chat_font_size">' . __('Admin chat font size', 'activehelper_livehelp') . '</label>
						</th></thead><tbody><tr><td id="newmetaleft" class="left">
							<input tabindex="' . $tabindex++ . '" maxlength="255" type="text" style="width: 96%;" value="' . $_POST['admin_chat_font_size'] . '" id="admin_chat_font_size" name="admin_chat_font_size" />
						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							' . __('Disable popup help', 'activehelper_livehelp') . '
						</th></thead><tbody><tr><td id="newmetaleft" class="left" style="padding: 1ex;">
							<label style="margin-left: .5ex; display: block; float: left; margin-right: 1ex; line-height: 15px;">
								<input tabindex="' . $tabindex++ . '" style="float: left; margin: 0 .5ex 0 0; width: auto;" type="radio" name="disable_popup_help" ' . (!empty($_POST['disable_popup_help']) ? 'checked="checked"' : '') . ' value="1" /> ' . __('Enable', 'activehelper_livehelp') . '</label>
							<label style="float: left; display: block; margin: 0 .5ex 0 0; width: auto; line-height: 15px;">
								<input tabindex="' . $tabindex++ . '" style="float: left;  margin: 0 .5ex 0 0; width: auto;" type="radio" name="disable_popup_help" ' . (empty($_POST['disable_popup_help']) ? 'checked="checked"' : '') . ' value="0" /> ' . __('Disable', 'activehelper_livehelp') . '</label>
							<div style="clear: both;"></div>
						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							' . __('Chat Background', 'activehelper_livehelp') . '
						</th></thead><tbody><tr><td id="newmetaleft" class="left">
							<select size="1" id="chat_background_img" style="width: 100px;" name="chat_background_img" tabindex="' . $tabindex++ . '">
								<option value="background_chat_blue.jpg"' . ($_POST['chat_background_img'] == 'background_chat_blue.jpg' ? ' selected="selected"' : '') . '>
									' . __('Blue', 'activehelper_livehelp') . '</option>
								<option value="background_chat_green.jpg"' . ($_POST['chat_background_img'] == 'background_chat_green.jpg' ? ' selected="selected"' : '') . '>
									' . __('Green', 'activehelper_livehelp') . '</option>
								<option value="background_chat_blue_dark.jpg"' . ($_POST['chat_background_img'] == 'background_chat_blue_dark.jpg' ? ' selected="selected"' : '') . '>
									' . __('Dark blue', 'activehelper_livehelp') . '</option>
								<option value="background_chat_grey.jpg"' . ($_POST['chat_background_img'] == 'background_chat_grey.jpg' ? ' selected="selected"' : '') . '>
									' . __('Grey', 'activehelper_livehelp') . '</option>
							</select>
							<div style="clear: both;"></div>
						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							<label for="campaign_link">' . __('Chat Image Link', 'activehelper_livehelp') . '</label>
						</th></thead><tbody><tr><td id="newmetaleft" class="left">
							<input tabindex="' . $tabindex++ . '" maxlength="255" type="text" style="width: 96%;" value="' . $_POST['campaign_link'] . '" id="campaign_link" name="campaign_link" />
						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							' . __('Disable Chat Image', 'activehelper_livehelp') . '
						</th></thead><tbody><tr><td id="newmetaleft" class="left" style="padding: 1ex;">
							<label style="margin-left: .5ex; display: block; float: left; margin-right: 1ex; line-height: 15px;">
								<input tabindex="' . $tabindex++ . '" style="float: left; margin: 0 .5ex 0 0; width: auto;" type="radio" name="disable_agent_bannner" ' . (!empty($_POST['disable_agent_bannner']) ? 'checked="checked"' : '') . ' value="1" /> ' . __('Enable', 'activehelper_livehelp') . '</label>
							<label style="float: left; display: block; margin: 0 .5ex 0 0; width: auto; line-height: 15px;">
								<input tabindex="' . $tabindex++ . '" style="float: left;  margin: 0 .5ex 0 0; width: auto;" type="radio" name="disable_agent_bannner" ' . (empty($_POST['disable_agent_bannner']) ? 'checked="checked"' : '') . ' value="0" /> ' . __('Disable', 'activehelper_livehelp') . '</label>
							<div style="clear: both;"></div>
						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							' . __('Chat Image', 'activehelper_livehelp') . '
						</th></thead><tbody><tr><td id="newmetaleft" class="left">' . (!empty($_POST['campaign_image']) ? '
							<div style="float: right; padding: .5ex 1ex .5ex 1ex;">
								<img style="margin: 4px 2px; border: 1px solid #ccc; background: #fff; padding: 2px;" class="domain_campaign_image" src="' . $activeHelper_liveHelp['domainsUrl'] . '/' . $_REQUEST['id'] . '/i18n/' . $activeHelper_liveHelp['domain']['domain_language'] . '/pictures/' . $_POST['campaign_image'] . '" alt="" />
							</div>' : '') . '
							<input type="file" tabindex="' . $tabindex++ . '" style="width: auto;" size="35" name="campaign_image">
						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							' . __('Chat Send Button', 'activehelper_livehelp') . '
						</th></thead><tbody><tr><td id="newmetaleft" class="left">' . (!empty($_POST['chat_button_img']) ? '
							<div style="float: right; padding: .5ex 1ex .5ex 1ex;">
								<img style="margin: 4px 2px; border: 1px solid #ccc; background: #fff; padding: 2px;" class="domain_chat_button_img" src="' . $activeHelper_liveHelp['domainsUrl'] . '/' . $_REQUEST['id'] . '/i18n/' . $activeHelper_liveHelp['domain']['domain_language'] . '/pictures/' . $_POST['chat_button_img'] . '" alt="" />
							</div>' : '') . '
							<input type="file" tabindex="' . $tabindex++ . '" style="width: auto;" size="35" name="chat_button_img">
						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							' . __('Chat Send Hand Over', 'activehelper_livehelp') . '
						</th></thead><tbody><tr><td id="newmetaleft" class="left">' . (!empty($_POST['chat_button_hover_img']) ? '
							<div style="float: right; padding: .5ex 1ex .5ex 1ex;">
								<img style="margin: 4px 2px; border: 1px solid #ccc; background: #fff; padding: 2px;" class="domain_chat_button_hover_img" src="' . $activeHelper_liveHelp['domainsUrl'] . '/' . $_REQUEST['id'] . '/i18n/' . $activeHelper_liveHelp['domain']['domain_language'] . '/pictures/' . $_POST['chat_button_hover_img'] . '" alt="" />
							</div>' : '') . '
							<input type="file" tabindex="' . $tabindex++ . '" style="width: auto;" size="35" name="chat_button_hover_img">
						</td></tr></tbody></table>
					</div></div>
				</div>

				<div class="stuffbox postbox">
					<div class="handlediv" title="' . __('Click to toggle', 'activehelper_livehelp') . '"><br /></div>
					<h3 style="cursor: pointer;">' . __('Proactive', 'activehelper_livehelp') . '</h3>
					<div class="inside" style="display: none;"><div id="postcustomstuff" style="padding: .6ex 0;">
						<table><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							' . __('Chat Invitation Image', 'activehelper_livehelp') . '
						</th></thead><tbody><tr><td id="newmetaleft" class="left">' . (!empty($_POST['chat_invitation_img']) ? '
							<div style="float: right; padding: .5ex 1ex .5ex 1ex;">
								<img style="margin: 4px 2px; border: 1px solid #ccc; background: #fff; padding: 2px;" class="domain_chat_invitation_img" src="' . $activeHelper_liveHelp['domainsUrl'] . '/' . $_REQUEST['id'] . '/i18n/' . $activeHelper_liveHelp['domain']['domain_language'] . '/pictures/' . $_POST['chat_invitation_img'] . '" alt="" />
							</div>' : '') . '
							<input type="file" tabindex="' . $tabindex++ . '" style="width: auto;" size="35" name="chat_invitation_img">
						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							<label for="invitation_refresh">' . __('Auto Start Invitation Refresh (Sec)', 'activehelper_livehelp') . '</label>
						</th></thead><tbody><tr><td id="newmetaleft" class="left">
							<input tabindex="' . $tabindex++ . '" maxlength="255" type="text" style="width: 96%;" value="' . $_POST['invitation_refresh'] . '" id="invitation_refresh" name="invitation_refresh" />
						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							' . __('Disable Invitation', 'activehelper_livehelp') . '
						</th></thead><tbody><tr><td id="newmetaleft" class="left" style="padding: 1ex;">
							<label style="margin-left: .5ex; display: block; float: left; margin-right: 1ex; line-height: 15px;">
								<input tabindex="' . $tabindex++ . '" style="float: left; margin: 0 .5ex 0 0; width: auto;" type="radio" name="disable_invitation" ' . (!empty($_POST['disable_invitation']) ? 'checked="checked"' : '') . ' value="1" /> ' . __('Enable', 'activehelper_livehelp') . '</label>
							<label style="float: left; display: block; margin: 0 .5ex 0 0; width: auto; line-height: 15px;">
								<input tabindex="' . $tabindex++ . '" style="float: left;  margin: 0 .5ex 0 0; width: auto;" type="radio" name="disable_invitation" ' . (empty($_POST['disable_invitation']) ? 'checked="checked"' : '') . ' value="0" /> ' . __('Disable', 'activehelper_livehelp') . '</label>
							<div style="clear: both;"></div>
						</td></tr></tbody></table>
					</div></div>
				</div>

				<div class="stuffbox postbox">
					<div class="handlediv" title="' . __('Click to toggle', 'activehelper_livehelp') . '"><br /></div>
					<h3 style="cursor: pointer;">' . __('Fonts', 'activehelper_livehelp') . '</h3>
					<div class="inside" style="display: none;"><div id="postcustomstuff" style="padding: .6ex 0;">
						<table><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							<label for="font_type">' . __('Font type', 'activehelper_livehelp') . '</label>
						</th></thead><tbody><tr><td id="newmetaleft" class="left">
							<input tabindex="' . $tabindex++ . '" maxlength="255" type="text" style="width: 96%;" value="' . $_POST['font_type'] . '" id="font_type" name="font_type" />
						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							<label for="font_size">' . __('Font size', 'activehelper_livehelp') . '</label>
						</th></thead><tbody><tr><td id="newmetaleft" class="left">
							<input tabindex="' . $tabindex++ . '" maxlength="255" type="text" style="width: 96%;" value="' . $_POST['font_size'] . '" id="font_size" name="font_size" />
						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							<label for="font_color">' . __('Font color', 'activehelper_livehelp') . '</label>
						</th></thead><tbody><tr><td id="newmetaleft" class="left">
							<input tabindex="' . $tabindex++ . '" maxlength="255" type="text" style="width: 96%;" value="' . $_POST['font_color'] . '" id="font_color" name="font_color" />
						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							<label for="font_link_color">' . __('Font link color', 'activehelper_livehelp') . '</label>
						</th></thead><tbody><tr><td id="newmetaleft" class="left">
							<input tabindex="' . $tabindex++ . '" maxlength="255" type="text" style="width: 96%;" value="' . $_POST['font_link_color'] . '" id="font_link_color" name="font_link_color" />
						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							<label for="sent_font_color">' . __('Sent font color', 'activehelper_livehelp') . '</label>
						</th></thead><tbody><tr><td id="newmetaleft" class="left">
							<input tabindex="' . $tabindex++ . '" maxlength="255" type="text" style="width: 96%;" value="' . $_POST['sent_font_color'] . '" id="sent_font_color" name="sent_font_color" />
						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							<label for="received_font_color">' . __('Received font color', 'activehelper_livehelp') . '</label>
						</th></thead><tbody><tr><td id="newmetaleft" class="left">
							<input tabindex="' . $tabindex++ . '" maxlength="255" type="text" style="width: 96%;" value="' . $_POST['received_font_color'] . '" id="received_font_color" name="received_font_color" />
						</td></tr></tbody></table>
					</div></div>
				</div>

				<div class="stuffbox postbox">
					<div class="handlediv" title="' . __('Click to toggle', 'activehelper_livehelp') . '"><br /></div>
					<h3 style="cursor: pointer;">' . __('Chat', 'activehelper_livehelp') . '</h3>
					<div class="inside" style="display: none;"><div id="postcustomstuff" style="padding: .6ex 0;">
						<table><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							' . __('Disable login', 'activehelper_livehelp') . '
						</th></thead><tbody><tr><td id="newmetaleft" class="left" style="padding: 1ex;">
							<label style="margin-left: .5ex; display: block; float: left; margin-right: 1ex; line-height: 15px;">
								<input tabindex="' . $tabindex++ . '" style="float: left; margin: 0 .5ex 0 0; width: auto;" type="radio" name="disable_login_details" ' . (!empty($_POST['disable_login_details']) ? 'checked="checked"' : '') . ' value="1" /> ' . __('Enable', 'activehelper_livehelp') . '</label>
							<label style="float: left; display: block; margin: 0 .5ex 0 0; width: auto; line-height: 15px;">
								<input tabindex="' . $tabindex++ . '" style="float: left;  margin: 0 .5ex 0 0; width: auto;" type="radio" name="disable_login_details" ' . (empty($_POST['disable_login_details']) ? 'checked="checked"' : '') . ' value="0" /> ' . __('Disable', 'activehelper_livehelp') . '</label>
							<div style="clear: both;"></div>
						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							' . __('Disable chat username', 'activehelper_livehelp') . '
						</th></thead><tbody><tr><td id="newmetaleft" class="left" style="padding: 1ex;">
							<label style="margin-left: .5ex; display: block; float: left; margin-right: 1ex; line-height: 15px;">
								<input tabindex="' . $tabindex++ . '" style="float: left; margin: 0 .5ex 0 0; width: auto;" type="radio" name="disable_chat_username" ' . (!empty($_POST['disable_chat_username']) ? 'checked="checked"' : '') . ' value="1" /> ' . __('Enable', 'activehelper_livehelp') . '</label>
							<label style="float: left; display: block; margin: 0 .5ex 0 0; width: auto; line-height: 15px;">
								<input tabindex="' . $tabindex++ . '" style="float: left;  margin: 0 .5ex 0 0; width: auto;" type="radio" name="disable_chat_username" ' . (empty($_POST['disable_chat_username']) ? 'checked="checked"' : '') . ' value="0" /> ' . __('Disable', 'activehelper_livehelp') . '</label>
							<div style="clear: both;"></div>
						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							' . __('Require guest details', 'activehelper_livehelp') . '
						</th></thead><tbody><tr><td id="newmetaleft" class="left" style="padding: 1ex;">
							<label style="margin-left: .5ex; display: block; float: left; margin-right: 1ex; line-height: 15px;">
								<input tabindex="' . $tabindex++ . '" style="float: left; margin: 0 .5ex 0 0; width: auto;" type="radio" name="require_guest_details" ' . (!empty($_POST['require_guest_details']) ? 'checked="checked"' : '') . ' value="1" /> ' . __('Enable', 'activehelper_livehelp') . '</label>
							<label style="float: left; display: block; margin: 0 .5ex 0 0; width: auto; line-height: 15px;">
								<input tabindex="' . $tabindex++ . '" style="float: left;  margin: 0 .5ex 0 0; width: auto;" type="radio" name="require_guest_details" ' . (empty($_POST['require_guest_details']) ? 'checked="checked"' : '') . ' value="0" /> ' . __('Disable', 'activehelper_livehelp') . '</label>
							<div style="clear: both;"></div>
						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							' . __('Disable language selection', 'activehelper_livehelp') . '
						</th></thead><tbody><tr><td id="newmetaleft" class="left" style="padding: 1ex;">
							<label style="margin-left: .5ex; display: block; float: left; margin-right: 1ex; line-height: 15px;">
								<input tabindex="' . $tabindex++ . '" style="float: left; margin: 0 .5ex 0 0; width: auto;" type="radio" name="disable_language" ' . (!empty($_POST['disable_language']) ? 'checked="checked"' : '') . ' value="1" /> ' . __('Enable', 'activehelper_livehelp') . '</label>
							<label style="float: left; display: block; margin: 0 .5ex 0 0; width: auto; line-height: 15px;">
								<input tabindex="' . $tabindex++ . '" style="float: left;  margin: 0 .5ex 0 0; width: auto;" type="radio" name="disable_language" ' . (empty($_POST['disable_language']) ? 'checked="checked"' : '') . ' value="0" /> ' . __('Disable', 'activehelper_livehelp') . '</label>
							<div style="clear: both;"></div>
						</td></tr></tbody></table>
					</div></div>
				</div>

				<div class="stuffbox postbox">
					<div class="handlediv" title="' . __('Click to toggle', 'activehelper_livehelp') . '"><br /></div>
					<h3 style="cursor: pointer;">' . __('Email', 'activehelper_livehelp') . '</h3>
					<div class="inside" style="display: none;"><div id="postcustomstuff" style="padding: .6ex 0;">
						<table><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							<label for="offline_email">' . __('Offline email', 'activehelper_livehelp') . '</label>
						</th></thead><tbody><tr><td id="newmetaleft" class="left">
							<input tabindex="' . $tabindex++ . '" maxlength="255" type="text" style="width: 96%;" value="' . $_POST['offline_email'] . '" id="offline_email" name="offline_email" />
						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							<label for="from_email">' . __('From email', 'activehelper_livehelp') . '</label>
						</th></thead><tbody><tr><td id="newmetaleft" class="left">
							<input tabindex="' . $tabindex++ . '" maxlength="255" type="text" style="width: 96%;" value="' . $_POST['from_email'] . '" id="from_email" name="from_email" />
						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							' . __('Disable offline email', 'activehelper_livehelp') . '
						</th></thead><tbody><tr><td id="newmetaleft" class="left" style="padding: 1ex;">
							<label style="margin-left: .5ex; display: block; float: left; margin-right: 1ex; line-height: 15px;">
								<input tabindex="' . $tabindex++ . '" style="float: left; margin: 0 .5ex 0 0; width: auto;" type="radio" name="disable_offline_email" ' . (!empty($_POST['disable_offline_email']) ? 'checked="checked"' : '') . ' value="1" /> ' . __('Enable', 'activehelper_livehelp') . '</label>
							<label style="float: left; display: block; margin: 0 .5ex 0 0; width: auto; line-height: 15px;">
								<input tabindex="' . $tabindex++ . '" style="float: left;  margin: 0 .5ex 0 0; width: auto;" type="radio" name="disable_offline_email" ' . (empty($_POST['disable_offline_email']) ? 'checked="checked"' : '') . ' value="0" /> ' . __('Disable', 'activehelper_livehelp') . '</label>
							<div style="clear: both;"></div>
						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							<label for="custom_offline_form_link">' . __('Custom offline form', 'activehelper_livehelp') . '</label>
						</th></thead><tbody><tr><td id="newmetaleft" class="left">
							<input tabindex="' . $tabindex++ . '" maxlength="255" type="text" style="width: 96%;" value="' . $_POST['custom_offline_form_link'] . '" id="custom_offline_form_link" name="custom_offline_form_link" />
						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							' . __('Log offline message', 'activehelper_livehelp') . '
						</th></thead><tbody><tr><td id="newmetaleft" class="left" style="padding: 1ex;">
							<label style="margin-left: .5ex; display: block; float: left; margin-right: 1ex; line-height: 15px;">
								<input tabindex="' . $tabindex++ . '" style="float: left; margin: 0 .5ex 0 0; width: auto;" type="radio" name="log_offline_email" ' . (!empty($_POST['log_offline_email']) ? 'checked="checked"' : '') . ' value="1" /> ' . __('Enable', 'activehelper_livehelp') . '</label>
							<label style="float: left; display: block; margin: 0 .5ex 0 0; width: auto; line-height: 15px;">
								<input tabindex="' . $tabindex++ . '" style="float: left;  margin: 0 .5ex 0 0; width: auto;" type="radio" name="log_offline_email" ' . (empty($_POST['log_offline_email']) ? 'checked="checked"' : '') . ' value="0" /> ' . __('Disable', 'activehelper_livehelp') . '</label>
							<div style="clear: both;"></div>
						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							' . __('SMTP', 'activehelper_livehelp') . '
						</th></thead><tbody><tr><td id="newmetaleft" class="left" style="padding: 1ex;">
							<label style="margin-left: .5ex; display: block; float: left; margin-right: 1ex; line-height: 15px;">
								<input tabindex="' . $tabindex++ . '" style="float: left; margin: 0 .5ex 0 0; width: auto;" type="radio" name="configure_smtp" ' . (!empty($_POST['configure_smtp']) ? 'checked="checked"' : '') . ' value="1" /> ' . __('Enable', 'activehelper_livehelp') . '</label>
							<label style="float: left; display: block; margin: 0 .5ex 0 0; width: auto; line-height: 15px;">
								<input tabindex="' . $tabindex++ . '" style="float: left;  margin: 0 .5ex 0 0; width: auto;" type="radio" name="configure_smtp" ' . (empty($_POST['configure_smtp']) ? 'checked="checked"' : '') . ' value="0" /> ' . __('Disable', 'activehelper_livehelp') . '</label>
							<div style="clear: both;"></div>
						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							<label for="smtp_server">' . __('SMTP Server', 'activehelper_livehelp') . '</label>
						</th></thead><tbody><tr><td id="newmetaleft" class="left">
							<input tabindex="' . $tabindex++ . '" maxlength="255" type="text" style="width: 96%;" value="' . $_POST['smtp_server'] . '" id="smtp_server" name="smtp_server" />
						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							<label for="smtp_port">' . __('SMTP Port', 'activehelper_livehelp') . '</label>
						</th></thead><tbody><tr><td id="newmetaleft" class="left">
							<input tabindex="' . $tabindex++ . '" maxlength="255" type="text" style="width: 96%;" value="' . $_POST['smtp_port'] . '" id="smtp_port" name="smtp_port" />
						</td></tr></tbody></table>
					</div></div>
				</div>

				<div class="stuffbox postbox">
					<div class="handlediv" title="' . __('Click to toggle', 'activehelper_livehelp') . '"><br /></div>
					<h3 style="cursor: pointer;">' . __('Images', 'activehelper_livehelp') . '</h3>
					<div class="inside" style="display: none;"><div id="postcustomstuff" style="padding: .6ex 0;">

						<table><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							' . __('Online image (gif)', 'activehelper_livehelp') . '
						</th></thead><tbody><tr><td id="newmetaleft" class="left">
							<div style="float: right; padding: .5ex 1ex .5ex 1ex;">
								<img style="margin: 4px 2px; border: 1px solid #ccc; background: #fff; padding: 2px;" src="' . $activeHelper_liveHelp['domainsUrl'] . '/' . $_REQUEST['id'] . '/i18n/' . $activeHelper_liveHelp['domain']['domain_language'] . '/pictures/online.gif" class="domain_image_online" alt="" />
							</div>
							<input type="file" tabindex="' . $tabindex++ . '" style="width: auto;" size="35" name="domain_image_online">
						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							' . __('Offline image (gif)', 'activehelper_livehelp') . '
						</th></thead><tbody><tr><td id="newmetaleft" class="left">
							<div style="float: right; padding: .5ex 1ex .5ex 1ex;">
								<img style="margin: 4px 2px; border: 1px solid #ccc; background: #fff; padding: 2px;" src="' . $activeHelper_liveHelp['domainsUrl'] . '/' . $_REQUEST['id'] . '/i18n/' . $activeHelper_liveHelp['domain']['domain_language'] . '/pictures/offline.gif" class="domain_image_offline" alt="" />
							</div>
							<input type="file" tabindex="' . $tabindex++ . '" style="width: auto;" size="35" name="domain_image_offline">
						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							' . __('Away image (gif)', 'activehelper_livehelp') . '
						</th></thead><tbody><tr><td id="newmetaleft" class="left">
							<div style="float: right; padding: .5ex 1ex .5ex 1ex;">
								<img style="margin: 4px 2px; border: 1px solid #ccc; background: #fff; padding: 2px;" src="' . $activeHelper_liveHelp['domainsUrl'] . '/' . $_REQUEST['id'] . '/i18n/' . $activeHelper_liveHelp['domain']['domain_language'] . '/pictures/away.gif" class="domain_image_away" alt="" />
							</div>
							<input type="file" tabindex="' . $tabindex++ . '" style="width: auto;" size="35" name="domain_image_away">
						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							' . __('BRB image (gif)', 'activehelper_livehelp') . '
						</th></thead><tbody><tr><td id="newmetaleft" class="left">
							<div style="float: right; padding: .5ex 1ex .5ex 1ex;">
								<img style="margin: 4px 2px; border: 1px solid #ccc; background: #fff; padding: 2px;" src="' . $activeHelper_liveHelp['domainsUrl'] . '/' . $_REQUEST['id'] . '/i18n/' . $activeHelper_liveHelp['domain']['domain_language'] . '/pictures/brb.gif" class="domain_image_brb" alt="" />
							</div>
							<input type="file" tabindex="' . $tabindex++ . '" style="width: auto;" size="35" name="domain_image_brb">
						</td></tr></tbody></table>
					</div></div>
				</div>

				<div class="stuffbox postbox">
					<div class="handlediv" title="' . __('Click to toggle', 'activehelper_livehelp') . '"><br /></div>
					<h3 style="cursor: pointer;">' . __('Languages and welcome message', 'activehelper_livehelp') . '</h3>
					<div class="inside" style="display: none;"><div id="postcustomstuff" style="padding: .6ex 0;">';

	$first = true;
	foreach ($activeHelper_liveHelp['languages'] as $language)
	{
		echo '
						<table ' . (!$first ? 'style="margin-top: 1.5ex;"' : '') . '><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							' . $__text[$language['code']] . '
						</th></thead><tbody><tr><td id="newmetaleft" class="left">
							<table cellpadding="0" cellspacing="0" border="0" style="border: 0; width: 98%;"><tr><td style="border: 0;">
								<select size="1" style="width: 100px; float: left;" name="domain_language_status[' . $language['code'] . ']" tabindex="' . $tabindex++ . '">
									<option value="1"' . (!empty($language['status']) ? ' selected="selected"' : '') . '>
										' . __('Enable', 'activehelper_livehelp') . '</option>
									<option value="0"' . (empty($language['status']) ? ' selected="selected"' : '') . '>
										' . __('Disable', 'activehelper_livehelp') . '</option>
								</select>
							</td><td style="width: 98%; border: 0;">
								<input tabindex="' . $tabindex++ . '" maxlength="255" type="text" style="float: left; width: 98%;" value="' . $language['welcome_message'] . '" id="domain_language_message" name="domain_language_message[' . $language['code'] . ']" />
							</td></tr></table>
							<div style="clear: both;"></div>
						</td></tr></tbody></table>';

		$first = false;
	}

	echo '
					</div></div>
				</div>

				<div class="stuffbox postbox">
					<div class="handlediv" title="' . __('Click to toggle', 'activehelper_livehelp') . '"><br /></div>
					<h3 style="cursor: pointer;">' . __('Rebranding', 'activehelper_livehelp') . '</h3>
					<div class="inside" style="display: none;"><div id="postcustomstuff" style="padding: .6ex 0;">
						<table><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							' . __('Copyright', 'activehelper_livehelp') . '
						</th></thead><tbody><tr><td id="newmetaleft" class="left" style="padding: 1ex;">
							<label style="margin-left: .5ex; display: block; float: left; margin-right: 1ex; line-height: 15px;">
								<input tabindex="' . $tabindex++ . '" style="float: left; margin: 0 .5ex 0 0; width: auto;" type="radio" name="disable_copyright" ' . (!empty($_POST['disable_copyright']) ? 'checked="checked"' : '') . ' value="1" /> ' . __('Enable', 'activehelper_livehelp') . '</label>
							<label style="float: left; display: block; margin: 0 .5ex 0 0; width: auto; line-height: 15px;">
								<input tabindex="' . $tabindex++ . '" style="float: left;  margin: 0 .5ex 0 0; width: auto;" type="radio" name="disable_copyright" ' . (empty($_POST['disable_copyright']) ? 'checked="checked"' : '') . ' value="0" /> ' . __('Disable', 'activehelper_livehelp') . '</label>
							<div style="clear: both;"></div>
						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							' . __('Image banner', 'activehelper_livehelp') . '
						</th></thead><tbody><tr><td id="newmetaleft" class="left" style="padding: 1ex;">
							<label style="margin-left: .5ex; display: block; float: left; margin-right: 1ex; line-height: 15px;">
								<input tabindex="' . $tabindex++ . '" style="float: left; margin: 0 .5ex 0 0; width: auto;" type="radio" name="copyright_image" ' . (!empty($_POST['copyright_image']) ? 'checked="checked"' : '') . ' value="1" /> ' . __('Enable', 'activehelper_livehelp') . '</label>
							<label style="float: left; display: block; margin: 0 .5ex 0 0; width: auto; line-height: 15px;">
								<input tabindex="' . $tabindex++ . '" style="float: left;  margin: 0 .5ex 0 0; width: auto;" type="radio" name="copyright_image" ' . (empty($_POST['copyright_image']) ? 'checked="checked"' : '') . ' value="0" /> ' . __('Disable', 'activehelper_livehelp') . '</label>
							<div style="clear: both;"></div>
						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							' . __('Company image', 'activehelper_livehelp') . '
						</th></thead><tbody><tr><td id="newmetaleft" class="left">' . (!empty($_POST['company_logo']) ? '
							<div style="float: right; padding: .5ex 1ex .5ex 1ex;">
								<img style="margin: 4px 2px; border: 1px solid #ccc; background: #fff; padding: 2px;" class="domain_company_logo" src="' . $activeHelper_liveHelp['domainsUrl'] . '/' . $_REQUEST['id'] . '/i18n/' . $activeHelper_liveHelp['domain']['domain_language'] . '/pictures/' . $_POST['company_logo'] . '" alt="" />
							</div>' : '') . '
							<input type="file" tabindex="' . $tabindex++ . '" style="width: auto;" size="35" name="company_logo">
						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							<label for="company_link">' . __('Company image link', 'activehelper_livehelp') . '</label>
						</th></thead><tbody><tr><td id="newmetaleft" class="left">
							<input tabindex="' . $tabindex++ . '" maxlength="255" type="text" style="width: 96%;" value="' . $_POST['company_link'] . '" id="company_link" name="company_link" />
						</td></tr></tbody></table>

						<table style="margin-top: 1.5ex;"><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							<label for="company_slogan">' . __('Company slogan', 'activehelper_livehelp') . '</label>
						</th></thead><tbody><tr><td id="newmetaleft" class="left">
							<input tabindex="' . $tabindex++ . '" maxlength="255" type="text" style="width: 96%;" value="' . $_POST['company_slogan'] . '" id="company_slogan" name="company_slogan" />
						</td></tr></tbody></table>
					</div></div>
				</div>

				<div class="stuffbox postbox">
					<div class="handlediv" style="display: block;" title="' . __('Click to toggle', 'activehelper_livehelp') . '"><br /></div>
					<h3 style="cursor: pointer;">' . __('Google Analytics integration', 'activehelper_livehelp') . '</h3>
					<div class="inside" style="display: none;"><div id="postcustomstuff" style="padding: .6ex 0;">
						<table><thead><tr><th style="font-size: 12px; font-weight: normal; text-align: left;">
							<label for="analytics_account">' . __('Analytics account', 'activehelper_livehelp') . '</label>
						</th></thead><tbody><tr><td id="newmetaleft" class="left">
							<input tabindex="' . $tabindex++ . '" maxlength="255" type="text" style="width: 96%;" value="' . $_POST['analytics_account'] . '" id="analytics_account" name="analytics_account" />
						</td></tr></tbody></table>
					</div></div>
				</div>
			</div></div></div>
			<br />
		</div>';

	if (!empty($_REQUEST['id']))
		echo '
		<input type="hidden" name="id" value="' . $_REQUEST['id'] . '" />';

	$random = rand(100, 999);
	echo '
	</form>
	<script type="text/javascript">
		jQuery(document).ready(function($){
			$(".meta-box-sortables .postbox").each(function(){
				var postbox = $(this);
				$("> h3", postbox).click(function(){
					$("div.inside", postbox).toggle();
				});
				$("> div.handlediv", postbox).click(function(){
					$("div.inside", postbox).toggle();
				});
			});

			$("#domain_language_selector").change(function(){
				var lang = $(this).val();

				var src = "' . $activeHelper_liveHelp['domainsUrl'] . '/' . $_REQUEST['id'] . '/i18n/" + lang + "/pictures";

				$("img.domain_image_online").attr("src", src + "/online.gif?cache=' . $random . '");
				$("img.domain_image_offline").attr("src", src + "/offline.gif?cache=' . $random . '");
				$("img.domain_image_away").attr("src", src + "/away.gif?cache=' . $random . '");
				$("img.domain_image_brb").attr("src", src + "/brb.gif?cache=' . $random . '");
				$("img.domain_campaign_image").attr("src", src + "/' . $_POST['campaign_image'] . '?cache=' . $random . '");
				$("img.domain_chat_button_img").attr("src", src + "/' . $_POST['chat_button_img'] . '?cache=' . $random . '");
				$("img.domain_chat_button_hover_img").attr("src", src + "/' . $_POST['chat_button_hover_img'] . '?cache=' . $random . '");
				$("img.domain_chat_invitation_img").attr("src", src + "/' . $_POST['chat_invitation_img'] . '?cache=' . $random . '");
				$("img.domain_company_logo").attr("src", src + "/' . $_POST['company_logo'] . '?cache=' . $random . '");
			});
		});
	</script>
</div>';
}

function activeHelper_liveHelp_domainsSettingsQuery($domain)
{
	global $wpdb;

	$settingsQuery = "
		INSERT INTO {$wpdb->prefix}livehelp_settings
			(name, value, id_domain)
		VALUES
			('admin_homepage', '/eserver1/panel/visitors_index.php', {$domain}),
			('timezone', '+1000', {$domain}),
			('default_department', 'General', {$domain}),
			('departments', '1', {$domain}),
			('disable_offline_email', '0', {$domain}),
			('disable_login_details', '0', {$domain}),
			('admin_chat_font_size', '12px', {$domain}),
			('guest_chat_font_size', '12px', {$domain}),
			('background_color', '#F9F9F9', {$domain}),
			('font_link_color', '#333399', {$domain}),
			('received_font_color', '#000000', {$domain}),
			('sent_font_color', '#666666', {$domain}),
			('chat_font_type', 'Arial, Arial Unicode, Lucida, Verdana', {$domain}),
			('font_color', '#000000', {$domain}),
			('font_size', '13px', {$domain}),
			('font_type', 'Arial, Helvetica, sans-serif,Verdana', {$domain}),
			('admin_smilies', '0', {$domain}),
			('guest_smilies', '1', {$domain}),
			('livehelp_logo', 'eserver/i18n/sp/pictures/help_logo.gif', {$domain}),
			('livehelp_name', 'www.activehelper.com Live Help', {$domain}),
			('offline_email', 'support@activehelper.com', {$domain}),
			('site_address', 'http://www.activehelper.com', {$domain}),
			('site_name', 'www.activehelper.com', {$domain}),
			('initiate_chat_valign', 'top', {$domain}),
			('initiate_chat_halign', 'right', {$domain}),
			('disable_chat_username', '0', {$domain}),
			('campaign_image', 'chat_banner.gif', {$domain}),
			('campaign_link', 'http://www.activehelper.com/', {$domain}),
			('disable_popup_help', '1', {$domain}),
			('p3p', 'ALL DSP COR CUR OUR IND ONL UNI COM NAV', {$domain}),
			('require_guest_details', '0', {$domain}),
			('configure_smtp', '0', {$domain}),
			('smtp_server', '', {$domain}),
			('smtp_port', '25', {$domain}),
			('from_email', 'support@activehelper.com', {$domain}),
			('login_timeout', '20', {$domain}),
			('chat_background_img', 'background_chat_grey.jpg', {$domain}),
			('chat_invitation_img', 'initiate_dialog.gif', {$domain}),
			('chat_button_img', 'send.gif', {$domain}),
			('chat_button_hover_img', 'send_hover.gif', {$domain}),
			('custom_offline_form_link', '', {$domain}),
			('log_offline_email', 0, {$domain}),
			('disable_language', 0, {$domain}),
			('company_logo', 'logo.jpg', {$domain}),
			('company_link', 'http://www.activehelper.com', {$domain}),
			('disable_copyright', 1, {$domain}),
			('company_slogan', 'ACTIVEHELPER Platform All Rights Reserved', {$domain}),
			('copyright_image', 1, {$domain}),
			('analytics_account','', {$domain}),
			('invitation_refresh', 0, {$domain}),
			('disable_invitation', 0, {$domain}),
			('disable_geolocation', 0, {$domain}),
			('disable_tracking_offline', 0, {$domain}),
			('captcha', 1, {$domain}),
			('disable_agent_bannner', 0, {$domain})
	";

	return $settingsQuery;
}

