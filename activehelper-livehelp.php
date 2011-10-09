<?
/**
 * @package ActiveHelper Live Help
 */
/*
Plugin Name: ActiveHelper Live Help
Plugin URI: http://www.activehelper.com
Description: Provide superior service by real time chat with your website visitors and interact them through your website. Create a more efficient connection with your website visitors, increase your sales and customer satisfaction.
Version: 2.6.1
Author: ActiveHelper Inc
Author URI: http://www.activehelper.com
*/
/*
Developer: Marco Florian (marco@marcorfg.com)
Developer URI: http://www.marcorfg.com
*/

// make sure we don't expose any info if called directly
if (!function_exists('add_action'))
{
	echo 'Hi there! I\'m just a plugin, not much I can do when called directly.';
	exit();
}

define("ACTIVEHELPER_LIVEHELP", true);

$activeHelper_liveHelp = array();
$activeHelper_liveHelp['baseDir'] = dirname(__FILE__);
$activeHelper_liveHelp['sourcesDir'] = $activeHelper_liveHelp['baseDir'] . '/sources';
$activeHelper_liveHelp['importDir'] = $activeHelper_liveHelp['baseDir'] . '/server/import';
$activeHelper_liveHelp['domainsDir'] = $activeHelper_liveHelp['baseDir'] . '/server/domains';
$activeHelper_liveHelp['agentsDir'] = $activeHelper_liveHelp['baseDir'] . '/server/pictures/agents';
$activeHelper_liveHelp['languagesDir'] = 'activehelper_livehelp/languages';

$activeHelper_liveHelp['baseUrl'] = plugins_url('', __FILE__);
$activeHelper_liveHelp['serverUrl'] = $activeHelper_liveHelp['baseUrl'] . '/server';
$activeHelper_liveHelp['domainsUrl'] = $activeHelper_liveHelp['baseUrl'] . '/server/domains';
$activeHelper_liveHelp['agentsUrl'] = $activeHelper_liveHelp['baseUrl'] . '/server/pictures/agents';
$activeHelper_liveHelp['imagesUrl'] = $activeHelper_liveHelp['baseUrl'] . '/images';

require_once($activeHelper_liveHelp['sourcesDir'] . '/livehelp_widget.php');

if (!is_admin())
	return;

require_once($activeHelper_liveHelp['sourcesDir'] . '/livehelp_lib-install.php');
require_once($activeHelper_liveHelp['sourcesDir'] . '/livehelp_lib-images.php');
require_once($activeHelper_liveHelp['sourcesDir'] . '/livehelp_lib-files.php');
require_once($activeHelper_liveHelp['sourcesDir'] . '/livehelp_domains.php');
require_once($activeHelper_liveHelp['sourcesDir'] . '/livehelp_agents.php');
require_once($activeHelper_liveHelp['sourcesDir'] . '/livehelp_monthly-chats.php');
require_once($activeHelper_liveHelp['sourcesDir'] . '/livehelp_time-by-chat.php');
require_once($activeHelper_liveHelp['sourcesDir'] . '/livehelp_failed-chats.php');
require_once($activeHelper_liveHelp['sourcesDir'] . '/livehelp_chats-by-department.php');
require_once($activeHelper_liveHelp['sourcesDir'] . '/livehelp_chats-by-country.php');
require_once($activeHelper_liveHelp['sourcesDir'] . '/livehelp_chats-by-keyword.php');
require_once($activeHelper_liveHelp['sourcesDir'] . '/livehelp_offline-messages.php');
require_once($activeHelper_liveHelp['sourcesDir'] . '/livehelp_server-settings.php');

add_action('init', 'activeHelper_liveHelp_mainLanguages');
add_action('admin_init', 'activeHelper_liveHelp_mainInstall');
add_action('admin_init', 'activeHelper_liveHelp_mainPost');
add_action('admin_menu', 'activeHelper_liveHelp_mainMenu');

function activeHelper_liveHelp_mainLanguages()
{
	global $activeHelper_liveHelp;

	if (!defined('WP_PLUGIN_DIR'))
		load_plugin_textdomain('activehelper_livehelp', $activeHelper_liveHelp['languagesDir']);
	else
		load_plugin_textdomain('activehelper_livehelp', false, $activeHelper_liveHelp['languagesDir']);
}

function activeHelper_liveHelp_mainInstall()
{
	global $wpdb, $activeHelper_liveHelp;

	$is_installed = $wpdb->get_var("
		SELECT COUNT(*)
		FROM {$wpdb->prefix}livehelp_domains
	");

	$activeHelper_liveHelp['is_installed'] = $is_installed !== null;
	if (!$activeHelper_liveHelp['is_installed'])
		activeHelper_liveHelp_install();
}

function activeHelper_liveHelp_mainPost()
{
	$pages = array(
		strtolower('activeHelper_liveHelp_domains') => 'activeHelper_liveHelp_domainsPost',
		strtolower('activeHelper_liveHelp_agents') => 'activeHelper_liveHelp_agentsPost',
		strtolower('activeHelper_liveHelp_welcome') => 'activeHelper_liveHelp_welcomePost',
		strtolower('activeHelper_liveHelp_monthlyChats') => 'activeHelper_liveHelp_monthlyChatsPost',
		strtolower('activeHelper_liveHelp_timeByChat') => 'activeHelper_liveHelp_timeByChatPost',
		strtolower('activeHelper_liveHelp_failedChats') => 'activeHelper_liveHelp_failedChatsPost',
		strtolower('activeHelper_liveHelp_chatsByDepartment') => 'activeHelper_liveHelp_chatsByDepartmentPost',
		strtolower('activeHelper_liveHelp_chatsByCountry') => 'activeHelper_liveHelp_chatsByCountryPost',
		strtolower('activeHelper_liveHelp_chatsByKeyword') => 'activeHelper_liveHelp_chatsByKeywordPost',
		strtolower('activeHelper_liveHelp_offlineMessages') => 'activeHelper_liveHelp_offlineMessagesPost',
		strtolower('activeHelper_liveHelp_serverSettings') => 'activeHelper_liveHelp_serverSettingsPost',
		strtolower('activeHelper_liveHelp_about') => 'activeHelper_liveHelp_aboutPost',
		strtolower('activehelper_livehelp_uninstall') => 'activehelper_livehelp_uninstallPost'
	);

	if (!empty($_GET['page']) && isset($pages[$_GET['page']]))
	{
		if (empty($_REQUEST['action'])) $_REQUEST['action'] = 'list';

		return $pages[$_GET['page']]();
	}
}

function activeHelper_liveHelp_mainMenu()
{
	if (!function_exists('add_menu_page') || !function_exists('add_submenu_page'))
		return;

	// add block
	add_menu_page(
		'LiveHelp System', // page title
		'LiveHelp System', // menu title
		'none', // type
		strtolower('activeHelper_liveHelp') // id
	);

	add_submenu_page(
		strtolower('activeHelper_liveHelp'), // parent
		__('Dashboard', 'activehelper_livehelp') .  ' ‹ ActiveHelper LiveHelp', // page title
		__('Dashboard', 'activehelper_livehelp'), // menu title
		'manage_options', // type
		strtolower('activeHelper_liveHelp_welcome'), // id
		'activeHelper_liveHelp_welcome' // callback
	);
	add_submenu_page(
		strtolower('activeHelper_liveHelp'), // parent
		__('Manage domains', 'activehelper_livehelp') .  ' ‹ ActiveHelper LiveHelp', // page title
		__('Manage domains', 'activehelper_livehelp'), // menu title
		'manage_options', // type
		strtolower('activeHelper_liveHelp_domains'), // id
		'activeHelper_liveHelp_domains' // callback
	);
	add_submenu_page(
		strtolower('activeHelper_liveHelp'), // parent
		__('Manage agents', 'activehelper_livehelp') . ' ‹ ActiveHelper LiveHelp', // page title
		__('Manage agents', 'activehelper_livehelp'), // menu title
		'manage_options', // type
		strtolower('activeHelper_liveHelp_agents'), // id
		'activeHelper_liveHelp_agents' // callback
	);
	add_submenu_page(
		strtolower('activeHelper_liveHelp'), // parent
		__('Monthly chats', 'activehelper_livehelp') . ' ‹ ActiveHelper LiveHelp', // page title
		__('Monthly chats', 'activehelper_livehelp'), // menu title
		'manage_options', // type
		strtolower('activeHelper_liveHelp_monthlyChats'), // id
		'activeHelper_liveHelp_monthlyChats' // callback
	);
	add_submenu_page(
		strtolower('activeHelper_liveHelp'), // parent
		__('Time by chat', 'activehelper_livehelp') . ' ‹ ActiveHelper LiveHelp', // page title
		__('Time by chat', 'activehelper_livehelp'), // menu title
		'manage_options', // type
		strtolower('activeHelper_liveHelp_timeByChat'), // id
		'activeHelper_liveHelp_timeByChat' // callback
	);

	add_submenu_page(
		strtolower('activeHelper_liveHelp'), // parent
		__('Failed chats', 'activehelper_livehelp') . ' ‹ ActiveHelper LiveHelp', // page title
		__('Failed chats', 'activehelper_livehelp'), // menu title
		'manage_options', // type
		strtolower('activeHelper_liveHelp_failedChats'), // id
		'activeHelper_liveHelp_failedChats' // callback
	);
	add_submenu_page(
		strtolower('activeHelper_liveHelp'), // parent
		__('Chats by department', 'activehelper_livehelp') . ' ‹ ActiveHelper LiveHelp', // page title
		__('Chats by department', 'activehelper_livehelp'), // menu title
		'manage_options', // type
		strtolower('activeHelper_liveHelp_chatsByDepartment'), // id
		'activeHelper_liveHelp_chatsByDepartment' // callback
	);
	add_submenu_page(
		strtolower('activeHelper_liveHelp'), // parent
		__('Chats by country', 'activehelper_livehelp') . ' ‹ ActiveHelper LiveHelp', // page title
		__('Chats by country', 'activehelper_livehelp'), // menu title
		'manage_options', // type
		strtolower('activeHelper_liveHelp_chatsByCountry'), // id
		'activeHelper_liveHelp_chatsByCountry' // callback
	);
	add_submenu_page(
		strtolower('activeHelper_liveHelp'), // parent
		__('Chats by keyword', 'activehelper_livehelp') . ' ‹ ActiveHelper LiveHelp', // page title
		__('Chats by keyword', 'activehelper_livehelp'), // menu title
		'manage_options', // type
		strtolower('activeHelper_liveHelp_chatsByKeyword'), // id
		'activeHelper_liveHelp_chatsByKeyword' // callback
	);
	add_submenu_page(
		strtolower('activeHelper_liveHelp'), // parent
		__('Offline messages', 'activehelper_livehelp') . ' ‹ ActiveHelper LiveHelp', // page title
		__('Offline messages', 'activehelper_livehelp'), // menu title
		'manage_options', // type
		strtolower('activeHelper_liveHelp_offlineMessages'), // id
		'activeHelper_liveHelp_offlineMessages' // callback
	);
	add_submenu_page(
		strtolower('activeHelper_liveHelp'), // parent
		__('Server settings', 'activehelper_livehelp') . ' ‹ ActiveHelper LiveHelp', // page title
		__('Server settings', 'activehelper_livehelp'), // menu title
		'manage_options', // type
		strtolower('activeHelper_liveHelp_serverSettings'), // id
		'activeHelper_liveHelp_serverSettings' // callback
	);

	add_submenu_page(
		strtolower('activeHelper_liveHelp'), // parent
		__('About', 'activehelper_livehelp') . ' ‹ ActiveHelper LiveHelp', // page title
		__('About', 'activehelper_livehelp'), // menu title
		'manage_options', // type
		strtolower('activeHelper_liveHelp_about'), // id
		'activeHelper_liveHelp_about' // callback
	);
	add_submenu_page(
		strtolower('activeHelper_liveHelp'), // parent
		__('Uninstall', 'activehelper_livehelp') . ' ‹ ActiveHelper LiveHelp', // page title
		__('Uninstall', 'activehelper_livehelp'), // menu title
		'manage_options', // type
		strtolower('activehelper_livehelp_uninstall'), // id
		'activehelper_livehelp_uninstall' // callback
	);
}

function activeHelper_liveHelp_welcomePost()
{
	wp_enqueue_style('dashboard');
}

function activeHelper_liveHelp_welcome()
{
	global $wpdb, $activeHelper_liveHelp;

	$domains = $wpdb->get_var("
		SELECT COUNT(*)
		FROM {$wpdb->prefix}livehelp_domains
	");
	$agents = $wpdb->get_var("
		SELECT COUNT(*)
		FROM {$wpdb->prefix}livehelp_users
	");
	$departments = $wpdb->get_var("
		SELECT COUNT(*)
		FROM
		(
			SELECT department
			FROM {$wpdb->prefix}livehelp_users
			GROUP BY department
		) AS t
	");
	$chats = $wpdb->get_var("
		SELECT COUNT(*)
		FROM {$wpdb->prefix}livehelp_sessions
	");

	$chats_today = $wpdb->get_var("
		SELECT COUNT(*)
		FROM {$wpdb->prefix}livehelp_sessions AS ls
		WHERE DATE_FORMAT(ls.datetime, '%m/%d/%Y') = DATE_FORMAT(now(), '%m/%d/%Y')
	");
	$visitors_today = $wpdb->get_var("
		SELECT COUNT(*)
		FROM {$wpdb->prefix}livehelp_requests AS ls
		WHERE DATE_FORMAT(ls.datetime, '%m/%d/%Y') = DATE_FORMAT(now(), '%m/%d/%Y')
	");

	$latest_aggent = $wpdb->get_var("
		SELECT username
		FROM {$wpdb->prefix}livehelp_users
		ORDER BY refresh DESC
		LIMIT 1
	");
	$oldest_aggent = $wpdb->get_var("
		SELECT username
		FROM {$wpdb->prefix}livehelp_users
		ORDER BY refresh ASC
		LIMIT 1
	");

	$fail_chats = $wpdb->get_var("
		SELECT COUNT(*)
		FROM {$wpdb->prefix}livehelp_messages AS lm
			RIGHT JOIN {$wpdb->prefix}livehelp_sessions AS ls
				ON (ls.id = lm.session)
		WHERE lm.username IS NULL
			AND lm.message IS NULL
	");
	$avg_chat_rating = $wpdb->get_var("
		SELECT IFNULL(AVG(rating), 0)
		FROM {$wpdb->prefix}livehelp_sessions
		WHERE rating != -1
	");

	$rowsdomains = $wpdb->get_results("
		SELECT jld.name, COUNT(jls.id) AS value
		FROM {$wpdb->prefix}livehelp_sessions AS jls, {$wpdb->prefix}livehelp_domains AS jld
		WHERE jls.id_domain = jld.id_domain
		GROUP BY jls.id_domain
		LIMIT 5
	", ARRAY_A);
	$rowsagents = $wpdb->get_results("
		SELECT jlu.username AS name, COUNT(jls.id) AS value
		FROM {$wpdb->prefix}livehelp_sessions AS jls, {$wpdb->prefix}livehelp_users AS jlu
		WHERE jls.id_user = jlu.id
		GROUP BY jlu.username
		LIMIT 5
	", ARRAY_A);
	$rowsagents_rating = $wpdb->get_results("
		SELECT jlu.username AS name, IFNULL(AVG(IF(jls.rating = -1, NULL, jls.rating)), 0) AS value
		FROM {$wpdb->prefix}livehelp_sessions AS jls, {$wpdb->prefix}livehelp_users AS jlu
		WHERE jls.id_user = jlu.id
		GROUP BY jlu.username
		LIMIT 5
	", ARRAY_A);
	$rowuser_avg = $wpdb->get_results("
		SELECT CONCAT(jls.username, ' (', jls.email, ')') AS name, COUNT(jls.id) AS value
		FROM {$wpdb->prefix}livehelp_sessions AS jls
		GROUP BY jls.email
		LIMIT 5
	", ARRAY_A);
	$rowsagents_duration = $wpdb->get_results("
		SELECT t1.name, SEC_TO_TIME(SUM(TIME_TO_SEC(Time))) AS value
		FROM
		(
			SELECT b.username AS name, TIMEDIFF(c.refresh, c.datetime) AS Time
			FROM {$wpdb->prefix}livehelp_users AS b, {$wpdb->prefix}livehelp_sessions AS c
			WHERE c.id_user = b.id
				AND DATE_FORMAT(c.datetime,'%Y%m%d') >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
				AND DATE_FORMAT(c.datetime,'%Y%m%d') <= CURDATE()
			GROUP BY c.id
			ORDER BY CONCAT(b.firstname, ' ', b.lastname)
		) AS t1
		GROUP BY t1.name
		LIMIT 5
	", ARRAY_A);

	echo '
<div class="wrap">
	<div class="icon32" id="icon-index"><br /></div>
	<h2 style="padding-right: 0;">
		LiveHelp
	</h2>
	<div id="dashboard-widgets-wrap">
		<div id="dashboard-widgets" class="metabox-holder">
			<div id="normal-sortables" class="meta-box-sortables ui-sortable">
				<div class="stuffbox postbox">
					<div class="inside">
						<h2>' . __('Welcome to ActiveHelper LiveHelp', 'activehelper_livehelp') . '</h2>
						<p>' . __('Thank you for choosing ActiveHelper LiveHelp as your Live Chat solution. This screen will give you a quick overview of your LiveHelp statistics. The links on the left-hand side of this screen allow you to a LiveHelp special reports.', 'activehelper_livehelp') . '</p>
					</div>
				</div>

				<div class="stuffbox postbox">
					<div class="handlediv" title="' . __('Click to toggle', 'activehelper_livehelp') . '"><br /></div>
					<h3 style="cursor: default;">' . __('Navigation', 'activehelper_livehelp') . '</h3>
					<div class="inside" style="margin-top: 0; padding: 0 2ex .5ex 2ex;">
						<div style="margin-top: 15px; float: left; padding-right: 5ex; text-align: center;">
							<a style="display: block;" href="admin.php?page=' . strtolower('activeHelper_liveHelp_domains') . '">
								<img style="margin: 0 auto .5ex auto; display: block;" src="' . $activeHelper_liveHelp['imagesUrl'] . '/world-32.png" />
								<span style="display: inline-block;">' . __('Manage domains', 'activehelper_livehelp') . '</span>
							</a>
						</div>
						<div style="margin-top: 15px; float: left; padding-right: 5ex; text-align: center;">
							<a style="display: block;" href="admin.php?page=' . strtolower('activeHelper_liveHelp_agents') . '">
								<img style="margin: 0 auto .5ex auto; display: block;" src="' . $activeHelper_liveHelp['imagesUrl'] . '/user-chat-32.png" />
								<span style="display: inline-block;">' . __('Manage agents', 'activehelper_livehelp') . '</span>
							</a>
						</div>
						<div style="margin-top: 15px; float: left; padding-right: 5ex; text-align: center;">
							<a style="display: block;" href="admin.php?page=' . strtolower('activeHelper_liveHelp_monthlyChats') . '">
								<img style="margin: 0 auto .5ex auto; display: block;" src="' . $activeHelper_liveHelp['imagesUrl'] . '/up-two-bars-32.png" />
								<span style="display: inline-block;">' . __('Monthly chats', 'activehelper_livehelp') . '</span>
							</a>
						</div>
						<div style="margin-top: 15px; float: left; padding-right: 5ex; text-align: center;">
							<a style="display: block;" href="admin.php?page=' . strtolower('activeHelper_liveHelp_timeByChat') . '">
								<img style="margin: 0 auto .5ex auto; display: block;" src="' . $activeHelper_liveHelp['imagesUrl'] . '/clock-32.png" />
								<span style="display: inline-block;">' . __('Time by chat', 'activehelper_livehelp') . '</span>
							</a>
						</div>
						<div style="margin-top: 15px; float: left; padding-right: 5ex; text-align: center;">
							<a style="display: block;" href="admin.php?page=' . strtolower('activeHelper_liveHelp_failedChats') . '">
								<img style="margin: 0 auto .5ex auto; display: block;" src="' . $activeHelper_liveHelp['imagesUrl'] . '/down-32.png" />
								<span style="display: inline-block;">' . __('Failed chats', 'activehelper_livehelp') . '</span>
							</a>
						</div>
						<div style="margin-top: 15px; float: left; padding-right: 5ex; text-align: center;">
							<a style="display: block;" href="admin.php?page=' . strtolower('activeHelper_liveHelp_chatsByDepartment') . '">
								<img style="margin: 0 auto .5ex auto; display: block;" src="' . $activeHelper_liveHelp['imagesUrl'] . '/chart-32.png" />
								<span style="display: inline-block;">' . __('Chats by department', 'activehelper_livehelp') . '</span>
							</a>
						</div>
						<div style="margin-top: 15px; float: left; padding-right: 5ex; text-align: center;">
							<a style="display: block;" href="admin.php?page=' . strtolower('activeHelper_liveHelp_chatsByCountry') . '">
								<img style="margin: 0 auto .5ex auto; display: block;" src="' . $activeHelper_liveHelp['imagesUrl'] . '/chart-country-32.png" />
								<span style="display: inline-block;">' . __('Chats by country', 'activehelper_livehelp') . '</span>
							</a>
						</div>
						<div style="margin-top: 15px; float: left; padding-right: 5ex; text-align: center;">
							<a style="display: block;" href="admin.php?page=' . strtolower('activeHelper_liveHelp_chatsByKeyword') . '">
								<img style="margin: 0 auto .5ex auto; display: block;" src="' . $activeHelper_liveHelp['imagesUrl'] . '/keyword.png" />
								<span style="display: inline-block;">' . __('Chats by keyword', 'activehelper_livehelp') . '</span>
							</a>
						</div>
						<div style="margin-top: 15px; float: left; padding-right: 5ex; text-align: center;">
							<a style="display: block;" href="admin.php?page=' . strtolower('activeHelper_liveHelp_offlineMessages') . '">
								<img style="margin: 0 auto .5ex auto; display: block;" src="' . $activeHelper_liveHelp['imagesUrl'] . '/messages.png" />
								<span style="display: inline-block;">' . __('Offline messages', 'activehelper_livehelp') . '</span>
							</a>
						</div>
						<div style="margin-top: 15px; float: left; padding-right: 5ex; text-align: center;">
							<a style="display: block;" href="admin.php?page=' . strtolower('activeHelper_liveHelp_serverSettings') . '">
								<img style="margin: 0 auto .5ex auto; display: block;" src="' . $activeHelper_liveHelp['imagesUrl'] . '/settings.png" />
								<span style="display: inline-block;">' . __('Server settings', 'activehelper_livehelp') . '</span>
							</a>
						</div>
						<div style="margin-top: 15px; float: left; padding-right: 5ex; text-align: center;">
							<a style="display: block;" href="admin.php?page=' . strtolower('activeHelper_liveHelp_about') . '">
								<img style="margin: 0 auto .5ex auto; display: block;" src="' . $activeHelper_liveHelp['imagesUrl'] . '/about.png" />
								<span style="display: inline-block;">' . __('About', 'activehelper_livehelp') . '</span>
							</a>
						</div>
						<div style="clear: both;"></div>
					</div>
				</div>

				<div id="dashboard_right_now" class="stuffbox postbox">
					<div class="handlediv" title="' . __('Click to toggle', 'activehelper_livehelp') . '"><br /></div>
					<h3 style="cursor: default;">' . __('General stats', 'activehelper_livehelp') . '</h3>
					<div class="inside" style="padding-top: 0;">
						<div class="table table_content" style="width: 48%; border: 0;">
							<table><tbody><tr class="first"><td class="first t">
								' . __('Domains', 'activehelper_livehelp') . '
							</td><td class="b">
								' . $domains . '
							</td></tr></tbody></table>
							<table><tbody><tr><td class="first t">
								' . __('Departments', 'activehelper_livehelp') . '
							</td><td class="b">
								' . $departments . '
							</td></tr></tbody></table>
							<table><tbody><tr><td class="first t">
								' . __('Chats today', 'activehelper_livehelp') . '
							</td><td class="b">
								' . $chats_today . '
							</td></tr></tbody></table>
							<table><tbody><tr><td class="first t">
								' . __('Latest aggent connected', 'activehelper_livehelp') . '
							</td><td class="b">
								' . (!empty($latest_aggent) ? $latest_aggent : __('No records found', 'activehelper_livehelp')) . '
							</td></tr></tbody></table>
							<table><tbody><tr><td class="first t">
								' . __('Failed chats', 'activehelper_livehelp') . '
							</td><td class="b">
								' . $fail_chats . '
							</td></tr></tbody></table>
						</div>
						<div class="table table_discussion" style="width: 48%; border: 0;">
							<table><tbody><tr class="first"><td class="first t">
								' . __('Agents', 'activehelper_livehelp') . '
							</td><td class="b">
								' . $agents . '
							</td></tr></tbody></table>
							<table><tbody><tr><td class="first t">
								' . __('Chats', 'activehelper_livehelp') . '
							</td><td class="b">
								' . $chats . '
							</td></tr></tbody></table>
							<table><tbody><tr><td class="first t">
								' . __('Visitors today', 'activehelper_livehelp') . '
							</td><td class="b">
								' . $visitors_today . '
							</td></tr></tbody></table>
							<table><tbody><tr><td class="first t">
								' . __('Oldest aggent connected', 'activehelper_livehelp') . '
							</td><td class="b">
								' . (!empty($oldest_aggent) ? $oldest_aggent : __('No records found', 'activehelper_livehelp')) . '
							</td></tr></tbody></table>
							<table><tbody><tr><td class="first t">
								' . __('AVG chat rating', 'activehelper_livehelp') . '
							</td><td class="b">
								' . $avg_chat_rating . '
							</td></tr></tbody></table>
						</div>
						<div style="clear: both;"></div>
					</div>
				</div>
			</div>

			<div class="postbox-container" style="width:49%; padding-right: 0;"><div id="normal-sortables" class="meta-box-sortables ui-sortable">
				<div id="dashboard_right_now" class="stuffbox postbox">
					<div class="handlediv" title="' . __('Click to toggle', 'activehelper_livehelp') . '"><br /></div>
					<h3 style="cursor: default;">' . __('Top 5 most active domains', 'activehelper_livehelp') . '</h3>
					<div class="inside">
						<div class="table table_content" style="width: 98%;">
							<p class="sub">
								' . __('Domain', 'activehelper_livehelp') . '
							</p>
							<p class="sub" style="right: 15px; left: auto;">
								' . __('Chats', 'activehelper_livehelp') . '
							</p>';

	if (empty($rowsdomains))
		echo '
							<table><tbody><tr class="first"><td class="first t"></td><td class="b">
								' . __('No records found', 'activehelper_livehelp') . '
							</td></tr></tbody></table>';
	else
		foreach ($rowsdomains as $row)
			echo '
							<table><tbody><tr class="first"><td class="first t">
								' . $row['name'] . '
							</td><td class="b">
								' . $row['value'] . '
							</td></tr></tbody></table>';

	echo '
						</div>
						<div style="clear: both;"></div>
					</div>
				</div>
				<div id="dashboard_right_now" class="stuffbox postbox">
					<div class="handlediv" title="' . __('Click to toggle', 'activehelper_livehelp') . '"><br /></div>
					<h3 style="cursor: default;">' . __('Top 5 avg agents raiting', 'activehelper_livehelp') . '</h3>
					<div class="inside">
						<div class="table table_content" style="width: 98%;">
							<p class="sub">
								' . __('Username', 'activehelper_livehelp') . '
							</p>
							<p class="sub" style="right: 15px; left: auto;">
								' . __('AVG raiting', 'activehelper_livehelp') . '
							</p>';

	if (empty($rowsagents_rating))
		echo '
							<table><tbody><tr class="first"><td class="first t"></td><td class="b">
								' . __('No records found', 'activehelper_livehelp') . '
							</td></tr></tbody></table>';
	else
		foreach ($rowsagents_rating as $row)
			echo '
							<table><tbody><tr class="first"><td class="first t">
								' . $row['name'] . '
							</td><td class="b">
								' . $row['value'] . '
							</td></tr></tbody></table>';

	echo '
						</div>
						<div style="clear: both;"></div>
					</div>
				</div>
				<div id="dashboard_right_now" class="stuffbox postbox">
					<div class="handlediv" title="' . __('Click to toggle', 'activehelper_livehelp') . '"><br /></div>
					<h3 style="cursor: default;">' . __('Top 5 most active users', 'activehelper_livehelp') . '</h3>
					<div class="inside">
						<div class="table table_content" style="width: 98%;">
							<p class="sub">
								' . __('Username', 'activehelper_livehelp') . '
							</p>
							<p class="sub" style="right: 15px; left: auto;">
								' . __('Chats', 'activehelper_livehelp') . '
							</p>';

	if (empty($rowuser_avg))
		echo '
							<table><tbody><tr class="first"><td class="first t"></td><td class="b">
								' . __('No records found', 'activehelper_livehelp') . '
							</td></tr></tbody></table>';
	else
		foreach ($rowuser_avg as $row)
			echo '
							<table><tbody><tr class="first"><td class="first t">
								' . $row['name'] . '
							</td><td class="b">
								' . $row['value'] . '
							</td></tr></tbody></table>';

	echo '
						</div>
						<div style="clear: both;"></div>
					</div>
				</div>
			</div></div>

			<div class="postbox-container" style="width:49%; float: right; padding-right: 0;"><div id="normal-sortables" class="meta-box-sortables ui-sortable">
				<div id="dashboard_right_now" class="stuffbox postbox">
					<div class="handlediv" title="' . __('Click to toggle', 'activehelper_livehelp') . '"><br /></div>
					<h3 style="cursor: default;">' . __('Top 5 most active agents by duration', 'activehelper_livehelp') . '</h3>
					<div class="inside">
						<div class="table table_content" style="width: 98%;">
							<p class="sub">
								' . __('Username', 'activehelper_livehelp') . '
							</p>
							<p class="sub" style="right: 15px; left: auto;">
								' . __('Duration', 'activehelper_livehelp') . '
							</p>';

	if (empty($rowsagents_duration))
		echo '
							<table><tbody><tr class="first"><td class="first t"></td><td class="b">
								' . __('No records found', 'activehelper_livehelp') . '
							</td></tr></tbody></table>';
	else
		foreach ($rowsagents_duration as $row)
			echo '
							<table><tbody><tr class="first"><td class="first t">
								' . $row['name'] . '
							</td><td class="b">
								' . $row['value'] . '
							</td></tr></tbody></table>';

	echo '
						</div>
						<div style="clear: both;"></div>
					</div>
				</div>

				<div id="dashboard_right_now" class="stuffbox postbox">
					<div class="handlediv" title="' . __('Click to toggle', 'activehelper_livehelp') . '"><br /></div>
					<h3 style="cursor: default;">' . __('Top 5 most active agents', 'activehelper_livehelp') . '</h3>
					<div class="inside">
						<div class="table table_content" style="width: 98%;">
							<p class="sub">
								' . __('Username', 'activehelper_livehelp') . '
							</p>
							<p class="sub" style="right: 15px; left: auto;">
								' . __('Duration', 'activehelper_livehelp') . '
							</p>';

	if (empty($rowsagents))
		echo '
							<table><tbody><tr class="first"><td class="first t"></td><td class="b">
								' . __('No records found', 'activehelper_livehelp') . '
							</td></tr></tbody></table>';
	else
		foreach ($rowsagents as $row)
			echo '
							<table><tbody><tr class="first"><td class="first t">
								' . $row['name'] . '
							</td><td class="b">
								' . $row['value'] . '
							</td></tr></tbody></table>';

	echo '
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
		});
	</script>
</div>';
}

function activeHelper_liveHelp_aboutPost()
{
	wp_enqueue_style('dashboard');
}

function activeHelper_liveHelp_about()
{
	echo '
<div class="wrap">
	<div class="icon32" id="icon-index"><br /></div>
	<h2 style="padding-right: 0;">
		LiveHelp » ' . __('About', 'activehelper_livehelp') . '
	</h2>
	<div id="dashboard-widgets-wrap">
		<div id="dashboard-widgets" class="metabox-holder">
			<div id="normal-sortables" class="meta-box-sortables ui-sortable">
				<div id="dashboard_right_now" class="stuffbox postbox">
					<h3 style="cursor: default;">' . __('About', 'activehelper_livehelp') . '</h3>
					<div class="inside">
						<style type="text/css">
							#dashboard_right_now td.b a { font-size: 14px; }
						</style>
						<div class="table table_content" style="width: 100%;">
							<p class="sub">
								' . __('LiveHelp support system', 'activehelper_livehelp') . '
							</p>
							<p class="sub" style="right: 15px; left: auto;">
								' . __('Info', 'activehelper_livehelp') . '
							</p>
							<table><tbody><tr class="first"><td class="first t">
								' . __('Name', 'activehelper_livehelp') . '
							</td><td class="b">
								' . __('LiveHelp system for WordPress', 'activehelper_livehelp') . '
							</td></tr></tbody></table>
							<table><tbody><tr><td class="first t">
								' . __('Version', 'activehelper_livehelp') . '
							</td><td class="b">
								' . __('2.6.1', 'activehelper_livehelp') . '
							</td></tr></tbody></table>
							<table><tbody><tr><td class="first t">
								' . __('Check for Update', 'activehelper_livehelp') . '
							</td><td class="b">
								' . __('<a href="http://www.activehelper.com/">http://www.activehelper.com/</a>', 'activehelper_livehelp') . '
							</td></tr></tbody></table>
							<table><tbody><tr><td class="first t">
								' . __('Help', 'activehelper_livehelp') . '
							</td><td class="b">
								' . __('<a href="http://www.activehelper.com/livehelp/live-chat/faqs.html">http://www.activehelper.com/livehelp/live-chat/faqs.html</a>', 'activehelper_livehelp') . '
							</td></tr></tbody></table>
							<table><tbody><tr><td class="first t">
								' . __('Support Forum', 'activehelper_livehelp') . '
							</td><td class="b">
								' . __('<a href="http://www.activehelper.com/contact-us/community-forum">http://www.activehelper.com/contact-us/community-forum</a>', 'activehelper_livehelp') . '
							</td></tr></tbody></table>
							<table><tbody><tr><td class="first t">
								' . __('Follow at Twitter', 'activehelper_livehelp') . '
							</td><td class="b">
								' . __('<a href="https://twitter.com/activehelper">https://twitter.com/activehelper</a>', 'activehelper_livehelp') . '
							</td></tr></tbody></table>
							<table><tbody><tr><td class="first t">
								' . __('License', 'activehelper_livehelp') . '
							</td><td class="b">
								' . __('GNU/GPL v2 - <a href="http://www.activehelper.com/license.txt">http://www.activehelper.com/license.txt</a>', 'activehelper_livehelp') . '
							</td></tr></tbody></table>
							<table><tbody><tr><td class="first t">
								' . __('Copyright', 'activehelper_livehelp') . '
							</td><td class="b">
								' . __('Copyright © 2009 - 2010. Activehelper - 2009 - 2011 - All Rights Reserved', 'activehelper_livehelp') . '
							</td></tr></tbody></table>
						</div>
						<div style="clear: both;"></div>
					</div>
				</div>
		
			</div>
		</div>
	</div>
</div>';
}

function activehelper_livehelp_uninstallPost()
{
	// :(
	if (isset($_GET['uninstall']))
		activeHelper_liveHelp_libUninstall();
}

function activehelper_livehelp_uninstall()
{
	echo '
<div class="wrap">
	<div class="icon32" id="icon-index"><br /></div>
	<h2 style="padding-right: 0;">
		LiveHelp » ' . __('Uninstall', 'activehelper_livehelp') . '
	</h2>
	<div id="dashboard-widgets-wrap">
		<div id="dashboard-widgets" class="metabox-holder">
			<div id="normal-sortables" class="meta-box-sortables ui-sortable">
				<div id="dashboard_right_now" class="stuffbox postbox">
					<div class="inside">
						<h2 style="text-align: center; font-weight: bold;">' . __('Are you sure you want to uninstall the ActiveHelper LiveHelp plugin?', 'activehelper_livehelp') . '</h2>
						<h2 style="text-align: center;">' . __('To deactivate this plugin, go to <a href="plugins.php">Plugins</a> | To uninstall this plugin, click on the link below', 'activehelper_livehelp') . '</h2>
						<p>&nbsp;</p>
						<h2 style="text-align: center;"><a href="admin.php?page=' . strtolower('activehelper_livehelp_uninstall') . '&uninstall">' . __('Delete all files and database tables related with this plugin permanently') . '</a></h3>
						<div style="clear: both;"></div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>';
}

