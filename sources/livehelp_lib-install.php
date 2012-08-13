<?php
/**
 * @package ActiveHelper Live Help
 */

if (!defined('ACTIVEHELPER_LIVEHELP'))
	die('Hi there! I\'m just a plugin, not much I can do when called directly.');

function activeHelper_liveHelp_libUninstall()
{
	global $wpdb, $activeHelper_liveHelp;

	$uninstallQuery = activeHelper_liveHelp_uninstallQuery();
	$uninstallQuery = str_replace('wp_livehelp', $wpdb->prefix . 'livehelp', $uninstallQuery);

	$active_plugins = get_option('active_plugins');
	foreach ($active_plugins as $id => $name)
		if ($name == 'activehelper_livehelp/activehelper_livehelp.php') unset($active_plugins[$id]);

	update_option('active_plugins', $active_plugins);

	$uninstallQuery = explode(";", $uninstallQuery);
	foreach ($uninstallQuery as $query)
		$wpdb->query($query);

	activeHelper_liveHelp_filesDelete($activeHelper_liveHelp['baseDir']);

	wp_redirect('plugins.php?deactivate=true');
	exit;
}

function activeHelper_liveHelp_install()
{
	global $wpdb, $activeHelper_liveHelp;

	if (!isset($activeHelper_liveHelp['is_installed']) || $activeHelper_liveHelp['is_installed'])
		return;

	$installQuery = activeHelper_liveHelp_installQuery();
	$installQuery = str_replace('wp_livehelp', $wpdb->prefix . 'livehelp', $installQuery);

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

	$installQuery = explode(";", $installQuery);
	foreach ($installQuery as $query)
		dbDelta($query);

	activeHelper_liveHelp_resetSettings();
}

function activeHelper_liveHelp_resetSettings()
{
	global $activeHelper_liveHelp;
	
	// Create constant file
	$settingsFile = $activeHelper_liveHelp['importDir'] . '/constants.php';

	$content = '<?php 
 
if (!defined(\'__CONSTANTS_INC\')) {  
define(\'__CONSTANTS_INC\', 1);  
 
include_once(\'jlhconst.php\');  
 
$eserverHostname = J_HOST;  
$eserverName = "server";
$domainSettings =J_DOMAIN_SET_PATH;  
$server_directory =J_DIR_PATH;  
$ssl =J_CONF_SSL;  
 
$install_directory = $server_directory."/".$eserverName;
 
// Set advanced settings, ie. timers  
 
$connection_timeout = 60;
$keep_alive_timeout = 30;
$guest_login_timeout= 60;
$chat_refresh_rate = 6;
$user_panel_refresh_rate = 10;
$sound_alert_new_message = 1;
 
} /* __CONSTANTS_INC */
 
?>';

	$fhandle = fopen($settingsFile, "w");
	fwrite($fhandle, $content);
	fclose($fhandle);

	// modifying prefix in database config file
	$configFile = $activeHelper_liveHelp['baseDir'] . '/server/import/config_database.php';

	$content = '<?php

if (!defined(\'__CONFIG_DATABASE_INC\'))
{
	define(\'__CONFIG_DATABASE_INC\', 1);
	define( \'DS\', DIRECTORY_SEPARATOR );

	include_once(\'constants.php\');   
	include_once(\'jlhconst.php\');

	define("DB_HOST", "' . DB_HOST . '");
	define("DB_USER", "' . DB_USER . '");
	define("DB_PASS", "' . DB_PASSWORD . '");
	define("DB_NAME", "' . DB_NAME . '");

	$table_prefix = \'wp_livehelp_\';
}

?>';

	$fhandle = fopen($configFile, "w");
	fwrite($fhandle, $content);
	fclose($fhandle);

	// creating host config file
	$parseUrl = parse_url(get_bloginfo('url'));
	$host = $parseUrl['scheme'] . '://' . $parseUrl['host'];
	$path = str_replace($host, '', $activeHelper_liveHelp['baseUrl']);
	$rootPath = str_replace(array('/wp-content/plugins/activehelper_livehelp', '\wp-content\plugins\activehelper_livehelp'), '', $activeHelper_liveHelp['baseDir']);
	$secureHost = $parseUrl['scheme'] == 'http' ? 0 : 1;

	$hostFile = $activeHelper_liveHelp['baseDir'] . '/server/import/jlhconst.php';
	$hostContent = '<?php

define("J_HOST", "' . $host . '");
define("J_DOMAIN_SET_PATH", "' . $activeHelper_liveHelp['domainsDir'] . '");
define("J_DIR_PATH", "' . $path . '");
define("J_CONF_PATH", "' . $rootPath . '");
define("J_CONF_SSL", ' . $secureHost . ');

?>';

	$fhandle = fopen($hostFile, "w");
	fwrite($fhandle, $hostContent);
	fclose($fhandle);
}

function activeHelper_liveHelp_installQuery()
{
	$installQuery = "
		CREATE TABLE IF NOT EXISTS `wp_livehelp_administration` (
			`id` bigint(20) NOT NULL auto_increment,
			`user` bigint(20) NOT NULL default '0',
			`username` varchar(30) NOT NULL default '',
			`operator_id` bigint(20) NOT NULL default '0',
			`datetime` datetime NOT NULL default '0000-00-00 00:00:00',
			`message` text NOT NULL,
			`id_domain` bigint(20) default NULL,
			`align` int(1) NOT NULL default '0',
			`status` int(1) NOT NULL default '0',
			PRIMARY KEY  (`id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;

		CREATE TABLE IF NOT EXISTS `wp_livehelp_accounts` (
			`id_account` bigint(19) NOT NULL default '0',
			`id_account_type` bigint(19) default '1',
			`login` varchar(30) NOT NULL default '',
			`password` varchar(30) NOT NULL default '',
			`creation_date` date default '0000-00-00',
			`expire_date` date default '0000-00-00',
			`status` char(1) NOT NULL default '0',
			`user_id` bigint(20) NOT NULL default '0',
			PRIMARY KEY  (`id_account`),
			UNIQUE KEY `uk_accounts_login` (`login`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;

		INSERT INTO wp_livehelp_accounts VALUES (1, 1, 'default', 'd16dcdb233ba1ecfb72b3d903e1ea2', '2006-03-22', '2006-03-22', '1', 1);

		CREATE TABLE IF NOT EXISTS `wp_livehelp_accounts_domain` (
			`id_account_domain` bigint(20) NOT NULL auto_increment,
			`id_account` bigint(20) NOT NULL default '0',
			`id_domain` bigint(20) NOT NULL default '0',
			`status` int(1) NOT NULL default '1',
			PRIMARY KEY  (`id_account_domain`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;

		CREATE TABLE IF NOT EXISTS `wp_livehelp_commands` (
			`id` int(5) NOT NULL auto_increment,
			`type` int(1) NOT NULL default '0',
			`description` varchar(255) NOT NULL default '',
			`contents` varchar(255) NOT NULL default '',
			PRIMARY KEY  (`id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;

		CREATE TABLE IF NOT EXISTS `wp_livehelp_domain_user` (
			`id_domain_user` bigint(20) NOT NULL auto_increment,
			`id_domain` bigint(20) NOT NULL default '0',
			`id_user` bigint(20) NOT NULL default '0',
			`status` int(1) NOT NULL default '1',
			PRIMARY KEY  (`id_domain_user`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;

		CREATE TABLE IF NOT EXISTS `wp_livehelp_domains` (
			`id_domain` bigint(20) NOT NULL auto_increment,
			`name` varchar(50) NOT NULL,
			`status` int(1) NOT NULL default '1',
			`configuration` text NOT NULL,
			PRIMARY KEY  (`id_domain`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;

		CREATE TABLE IF NOT EXISTS `wp_livehelp_languages` (
			`code` char(2) NOT NULL default '',
			`name` varchar(100) NOT NULL default '',
			`charset` varchar(100) NOT NULL default '',
			PRIMARY KEY  (`code`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;

		INSERT INTO wp_livehelp_languages VALUES ('en', 'English', 'utf-8');
		INSERT INTO wp_livehelp_languages VALUES ('sp', 'Spanish', 'utf-8');
		INSERT INTO wp_livehelp_languages VALUES ('de', 'Deutsch', 'utf-8');
		INSERT INTO wp_livehelp_languages VALUES ('pt', 'Portuguese','utf-8');
		INSERT INTO wp_livehelp_languages VALUES ('it', 'Italian', 'utf-8');
		INSERT INTO wp_livehelp_languages VALUES ('fr', 'French', 'utf-8');
		INSERT INTO wp_livehelp_languages VALUES ('cz', 'Czech', 'utf-8');
		INSERT INTO wp_livehelp_languages VALUES ('se', 'Swedish', 'utf-8');
		INSERT INTO wp_livehelp_languages VALUES ('no', 'Norwegian', 'utf-8');
		INSERT INTO wp_livehelp_languages VALUES ('tr', 'Turkey', 'utf-8');
		INSERT INTO wp_livehelp_languages VALUES ('gr', 'Greek', 'utf-8');
		INSERT INTO wp_livehelp_languages VALUES ('he', 'Hebrew', 'utf-8');
		INSERT INTO wp_livehelp_languages VALUES ('fa', 'Farsi', 'utf-8');
		INSERT INTO wp_livehelp_languages VALUES ('sr', 'Serbian', 'utf-8');
		INSERT INTO wp_livehelp_languages VALUES ('ru', 'Rusian', 'utf-8');
		INSERT INTO wp_livehelp_languages VALUES ('hu', 'Hungarian', 'utf-8');
		INSERT INTO wp_livehelp_languages VALUES ('zh', 'Traditional Chinese', 'utf-8');
		INSERT INTO wp_livehelp_languages VALUES ('ar', 'Arab', 'utf-8');	
		INSERT INTO wp_livehelp_languages VALUES ('nl', 'Dutch', 'utf-8');
		INSERT INTO wp_livehelp_languages VALUES ('fi', 'Finnish', 'utf-8');
		INSERT INTO wp_livehelp_languages VALUES ('dk', 'Danish', 'utf-8');
		INSERT INTO wp_livehelp_languages VALUES ('pl', 'Polish', 'utf-8');
		INSERT INTO wp_livehelp_languages VALUES ('cn', 'Simplified Chinese', 'utf-8');
        INSERT INTO wp_livehelp_languages VALUES ('bg', 'Bulgarian', 'utf-8');
        INSERT INTO wp_livehelp_languages VALUES ('sk', 'Slovak', 'utf-8');
        INSERT INTO wp_livehelp_languages VALUES ('cr', 'Croatian', 'utf-8');

		CREATE TABLE IF NOT EXISTS `wp_livehelp_languages_domain` (
			`Id_domain` int(11) NOT NULL default '0',
			`code` char(2) NOT NULL default '',
			`name` varchar(100) NOT NULL default '', 
			`welcome_message` text NOT NULL,
			PRIMARY KEY  (`Id_domain`,`code`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;

		CREATE TABLE IF NOT EXISTS `wp_livehelp_messages` (
			`id` bigint(20) NOT NULL auto_increment,
			`session` bigint(20) NOT NULL default '0',
			`username` varchar(30) NOT NULL default '',
			`datetime` datetime NOT NULL default '0000-00-00 00:00:00',
			`message` text NOT NULL,
			`id_domain` bigint(20) default NULL,
			`align` int(1) NOT NULL default '0',
			`status` int(1) NOT NULL default '0',
			`id_user` bigint(20) default '-1',
			PRIMARY KEY  (`id`),
			KEY `idx_session` (`session`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;

		CREATE TABLE IF NOT EXISTS `wp_livehelp_requests` (
			`id` bigint(20) NOT NULL auto_increment,
			`ipaddress` varchar(100) NOT NULL default '',
			`useragent` varchar(200) NOT NULL default '',
			`resolution` varchar(20) NOT NULL default '',
			`datetime` datetime NOT NULL default '0000-00-00 00:00:00',
			`request` datetime NOT NULL default '0000-00-00 00:00:00',
			`refresh` datetime NOT NULL default '0000-00-00 00:00:00',
			`url` text NOT NULL,
			`id_domain` bigint(20) default NULL,
			`title` varchar(150) NOT NULL default '',
			`referrer` text NOT NULL,
			`path` text NOT NULL,
			`initiate` bigint(20) NOT NULL default '0',
			`status` int(1) NOT NULL default '0',
			`services` varchar(255) default NULL,
			`number_pages` int(11) NOT NULL default '0',
			`city` varchar(50) default NULL,
			`region` varchar(50) default NULL,
			`country_code` varchar(6) default NULL,
			`country` varchar(50) default NULL,
			`latitude` varchar(20) default NULL,
			`longitude` varchar(20) default NULL, 
			`visitor_name` varchar(30) default NULL,
			`visitor_email` varchar(50) default NULL,
			`visitor_id` bigint(20) default NULL,
			PRIMARY KEY  (`id`),
			KEY `IDX_R_DOMAIN` (`id_domain`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;

		CREATE TABLE IF NOT EXISTS `wp_livehelp_responses` (
			`id` int(5) NOT NULL auto_increment,
			`contents` varchar(255) NOT NULL default '',
			PRIMARY KEY  (`id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;

		CREATE TABLE IF NOT EXISTS `wp_livehelp_sa_domain_user_role` (
			`id_domain_user_role` bigint(19) NOT NULL auto_increment,
			`id_domain_user` bigint(19) default '0',
			`id_role` bigint(19) default NULL,
			PRIMARY KEY  (`id_domain_user_role`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;

		CREATE TABLE IF NOT EXISTS `wp_livehelp_sa_role` (
			`id_role` bigint(19) NOT NULL default '0',
			`description` varchar(100) NOT NULL default '',
			PRIMARY KEY  (`id_role`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;

		INSERT INTO wp_livehelp_sa_role VALUES (1, 'superuser');
		INSERT INTO wp_livehelp_sa_role VALUES (2, 'LiveChat administrator');
		INSERT INTO wp_livehelp_sa_role VALUES (3, 'LiveCall administrator');
		INSERT INTO wp_livehelp_sa_role VALUES (5, 'LiveTalk administrator');
		INSERT INTO wp_livehelp_sa_role VALUES (6, 'Web flow administrator');
		INSERT INTO wp_livehelp_sa_role VALUES (7, 'LiveMail');

		CREATE TABLE IF NOT EXISTS `wp_livehelp_sa_role_services` (
			`id_role_service` bigint(19) NOT NULL default '0',
			`id_role` bigint(19) default NULL,
			`id_service` bigint(19) default NULL,
			PRIMARY KEY  (`id_role_service`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;

		INSERT INTO wp_livehelp_sa_role_services VALUES (1, 1, 1);
		INSERT INTO wp_livehelp_sa_role_services VALUES (7, 2, 1);

		CREATE TABLE IF NOT EXISTS `wp_livehelp_sessions` (
			`id` bigint(20) NOT NULL auto_increment,
			`request` bigint(20) NOT NULL default '0',
			`username` varchar(30) NOT NULL default '',
			`datetime` datetime NOT NULL default '0000-00-00 00:00:00',
			`refresh` datetime NOT NULL default '0000-00-00 00:00:00',
			`email` varchar(50) NOT NULL default '',
			`server` varchar(100) NOT NULL default '',
			`department` varchar(50) NOT NULL default '',
			`rating` int(1) NOT NULL default '0',
			`typing` int(1) NOT NULL default '0',
			`transfer` int(1) NOT NULL default '0',
			`active` int(1) NOT NULL default '0',
			`language` char(2) NOT NULL default '',
			`id_user` bigint(20) default NULL,
			`id_domain` bigint(20) default NULL,
			PRIMARY KEY  (`id`),
			KEY `IDX_R_SESSOION` (`request`)
		) ENGINE=MyISAM AUTO_INCREMENT=100 DEFAULT CHARSET=utf8;

		CREATE TABLE IF NOT EXISTS `wp_livehelp_ge_global_settings` (
			`id` varchar(50) NOT NULL default '',
			`value` text NOT NULL,
			PRIMARY KEY  (`id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;

		INSERT INTO wp_livehelp_ge_global_settings VALUES ('webcall_timeout', '30');

		CREATE TABLE IF NOT EXISTS `wp_livehelp_settings` (
			`id` bigint(20) NOT NULL auto_increment,
			`name` varchar(50) NOT NULL default '',
			`value` varchar(255) NOT NULL default '',
			`id_domain` bigint(20) default NULL,
			PRIMARY KEY  (`id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;

		INSERT INTO wp_livehelp_settings VALUES (23, 'admin_homepage', '/eserver1/panel/visitors_index.php', 0);
		INSERT INTO wp_livehelp_settings VALUES (22, 'timezone', '+1000', 0);
		INSERT INTO wp_livehelp_settings VALUES (21, 'default_department', 'General', 0);
		INSERT INTO wp_livehelp_settings VALUES (20, 'departments', '1', 0);
		INSERT INTO wp_livehelp_settings VALUES (19, 'disable_offline_email', '0', 0);
		INSERT INTO wp_livehelp_settings VALUES (18, 'disable_login_details', '0', 0);
		INSERT INTO wp_livehelp_settings VALUES (17, 'admin_chat_font_size', '12px', 0);
		INSERT INTO wp_livehelp_settings VALUES (16, 'guest_chat_font_size', '12px', 0);
		INSERT INTO wp_livehelp_settings VALUES (15, 'background_color', '#F9F9F9', 0);
		INSERT INTO wp_livehelp_settings VALUES (14, 'font_link_color', '#333399', 0);
		INSERT INTO wp_livehelp_settings VALUES (13, 'received_font_color', '#000000', 0);
		INSERT INTO wp_livehelp_settings VALUES (12, 'sent_font_color', '#666666', 0);
		INSERT INTO wp_livehelp_settings VALUES (11, 'chat_font_type', 'Arial, Arial Unicode, Lucida, Verdana', 0);
		INSERT INTO wp_livehelp_settings VALUES (10, 'font_color', '#000000', 0);
		INSERT INTO wp_livehelp_settings VALUES (9, 'font_size', '13px', 0);
		INSERT INTO wp_livehelp_settings VALUES (8, 'font_type', 'Arial, Helvetica, sans-serif,Verdana', 0);
		INSERT INTO wp_livehelp_settings VALUES (7, 'admin_smilies', '0', 0);
		INSERT INTO wp_livehelp_settings VALUES (6, 'guest_smilies', '1', 0);
		INSERT INTO wp_livehelp_settings VALUES (5, 'livehelp_logo', 'eserver/i18n/sp/pictures/help_logo.gif', 0);
		INSERT INTO wp_livehelp_settings VALUES (4, 'livehelp_name', 'www.activehelper.com Live Help', 0);
		INSERT INTO wp_livehelp_settings VALUES (3, 'offline_email', 'support@activehelper.com', 0);
		INSERT INTO wp_livehelp_settings VALUES (2, 'site_address', 'http://www.activehelper.com', 0);
		INSERT INTO wp_livehelp_settings VALUES (1, 'site_name', 'www.activehelper.com', 0);
		INSERT INTO wp_livehelp_settings VALUES (24, 'initiate_chat_valign', 'top', 0);
		INSERT INTO wp_livehelp_settings VALUES (25, 'initiate_chat_halign', 'right', 0);
		INSERT INTO wp_livehelp_settings VALUES (26, 'disable_chat_username', '0', 0);
		INSERT INTO wp_livehelp_settings VALUES (27, 'campaign_image', 'chat_banner.gif', 0);
		INSERT INTO wp_livehelp_settings VALUES (28, 'campaign_link', 'http://www.activehelper.com/', 0);
		INSERT INTO wp_livehelp_settings VALUES (29, 'disable_popup_help', '1', 0);
		INSERT INTO wp_livehelp_settings VALUES (30, 'p3p', 'ALL DSP COR CUR OUR IND ONL UNI COM NAV', 0);
		INSERT INTO wp_livehelp_settings VALUES (31, 'require_guest_details', '0', 0);
		INSERT INTO wp_livehelp_settings VALUES (32, 'configure_smtp', '0', 0);
		INSERT INTO wp_livehelp_settings VALUES (33, 'smtp_server', '', 0);
		INSERT INTO wp_livehelp_settings VALUES (34, 'smtp_port', '25', 0);
		INSERT INTO wp_livehelp_settings VALUES (35, 'from_email', 'support@activehelper.com', 0);
		INSERT INTO wp_livehelp_settings VALUES (36, 'login_timeout', '20', 0);
		INSERT INTO wp_livehelp_settings VALUES (37, 'chat_background_img', 'background_chat_grey.jpg', 0);
		INSERT INTO wp_livehelp_settings VALUES (38, 'chat_invitation_img', 'initiate_dialog.gif', 0);
		INSERT INTO wp_livehelp_settings VALUES (39, 'chat_button_img', 'send.gif', 0);
		INSERT INTO wp_livehelp_settings VALUES (40, 'chat_button_hover_img', 'send_hover.gif', 0);
		INSERT INTO wp_livehelp_settings VALUES (41, 'custom_offline_form_link', '', 0);
		INSERT INTO wp_livehelp_settings VALUES (42, 'log_offline_email', 0, 0);
		INSERT INTO wp_livehelp_settings VALUES (43, 'disable_language', 0, 0);
		INSERT INTO wp_livehelp_settings VALUES (44, 'company_logo', 'logo.jpg', 0);
		INSERT INTO wp_livehelp_settings VALUES (45, 'company_link', 'http://www.activehelper.com', 0);
		INSERT INTO wp_livehelp_settings VALUES (46, 'disable_copyright', 1, 0);
		INSERT INTO wp_livehelp_settings VALUES (47, 'company_slogan', 'ACTIVEHELPER Platform All Rights Reserved', 0);
		INSERT INTO wp_livehelp_settings VALUES (48, 'copyright_image', 1, 0);
		INSERT INTO wp_livehelp_settings VALUES (49, 'analytics_account','', 0);
		INSERT INTO wp_livehelp_settings VALUES (50, 'invitation_refresh', 0, 0);
		INSERT INTO wp_livehelp_settings VALUES (51, 'disable_invitation', 0, 0);
		INSERT INTO wp_livehelp_settings VALUES (52, 'disable_geolocation', 0, 0);
		INSERT INTO wp_livehelp_settings VALUES (53, 'disable_tracking_offline', 0, 0);
		INSERT INTO wp_livehelp_settings VALUES (54, 'captcha', 1, 0);
		INSERT INTO wp_livehelp_settings VALUES (55, 'disable_agent_bannner', 0, 0);

		CREATE TABLE IF NOT EXISTS `wp_livehelp_statuses` (
			`id_status` int(11) NOT NULL default '0',
			`id_service` int(11) NOT NULL default '0',
			`service_name` varchar(100) default '',
			`service_description` text,
			PRIMARY KEY  (`id_service`,`id_status`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;

		INSERT INTO wp_livehelp_statuses VALUES (0, 4, 'Waiting', 'Just addes request without answer');
		INSERT INTO wp_livehelp_statuses VALUES (-1, 4, 'Canceled', 'Canceled by user');
		INSERT INTO wp_livehelp_statuses VALUES (-2, 4, 'Timeout', 'Canceled by Timeout');
		INSERT INTO wp_livehelp_statuses VALUES (1, 4, 'Talking', 'Operator is talking now with user');
		INSERT INTO wp_livehelp_statuses VALUES (2, 4, 'Finished', 'Finished');

		CREATE TABLE IF NOT EXISTS `wp_livehelp_users` (
			`id` bigint(20) NOT NULL auto_increment,
			`username` varchar(50) NOT NULL default '',
			`password` varchar(100) NOT NULL default '',
			`firstname` varchar(50) NOT NULL default '',
			`lastname` varchar(50) NOT NULL default '',
			`email` varchar(50) NOT NULL default '',
			`department` varchar(100) NOT NULL default '',
			`datetime` datetime NOT NULL default '0000-00-00 00:00:00',
			`refresh` datetime NOT NULL default '0000-00-00 00:00:00',
			`disabled` int(1) NOT NULL default '0',
			`privilege` int(1) NOT NULL default '0', 
			`photo` varchar(10) DEFAULT NULL,
			`status` bigint(20) NOT NULL default '0',
			PRIMARY KEY  (`id`),
			UNIQUE KEY `uk_users_username` (`username`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;

		CREATE TABLE IF NOT EXISTS `wp_livehelp_offline_messages` (
			`id` bigint(20) NOT NULL auto_increment,
			`name` varchar(30) NOT NULL default '',
			`email` varchar(30) NOT NULL default '',
			`message` text NOT NULL,
			`id_domain` bigint(20) NOT NULL,
			`datetime` datetime NOT NULL default '0000-00-00 00:00:00',	
			`answered` char(1) NOT NULL DEFAULT '0',
			PRIMARY KEY  (`id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;

		CREATE TABLE `wp_livehelp_countries` (
			`code` varchar(2) NOT NULL,
			`name` char(64) NOT NULL,
			UNIQUE KEY `code` (`code`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;

		INSERT INTO wp_livehelp_countries VALUES ('AD', 'Andorra');
		INSERT INTO wp_livehelp_countries VALUES ('AE', 'United Arab Emirates');
		INSERT INTO wp_livehelp_countries VALUES ('AF', 'Afghanistan');
		INSERT INTO wp_livehelp_countries VALUES ('AG', 'Antigua and Barbuda');
		INSERT INTO wp_livehelp_countries VALUES ('AI', 'Anguilla');
		INSERT INTO wp_livehelp_countries VALUES ('AL', 'Albania');
		INSERT INTO wp_livehelp_countries VALUES ('AM', 'Armenia');
		INSERT INTO wp_livehelp_countries VALUES ('AN', 'Netherlands Antilles');
		INSERT INTO wp_livehelp_countries VALUES ('AO', 'Angola');
		INSERT INTO wp_livehelp_countries VALUES ('AP', 'Asia/Pacific Region');
		INSERT INTO wp_livehelp_countries VALUES ('AQ', 'Antartica');
		INSERT INTO wp_livehelp_countries VALUES ('AR', 'Argentina');
		INSERT INTO wp_livehelp_countries VALUES ('AS', 'American Samoa');
		INSERT INTO wp_livehelp_countries VALUES ('AT', 'Austria');
		INSERT INTO wp_livehelp_countries VALUES ('AU', 'Australia');
		INSERT INTO wp_livehelp_countries VALUES ('AW', 'Aruba');
		INSERT INTO wp_livehelp_countries VALUES ('AX', 'Aland Islands');
		INSERT INTO wp_livehelp_countries VALUES ('AZ', 'Azerbaijan');
		INSERT INTO wp_livehelp_countries VALUES ('BA', 'Bosnia and Herzegovina');
		INSERT INTO wp_livehelp_countries VALUES ('BB', 'Barbados');
		INSERT INTO wp_livehelp_countries VALUES ('BD', 'Bangladesh');
		INSERT INTO wp_livehelp_countries VALUES ('BE', 'Belgium');
		INSERT INTO wp_livehelp_countries VALUES ('BF', 'Burkina Faso');
		INSERT INTO wp_livehelp_countries VALUES ('BG', 'Bulgaria');
		INSERT INTO wp_livehelp_countries VALUES ('BH', 'Bahrain');
		INSERT INTO wp_livehelp_countries VALUES ('BI', 'Burundi');
		INSERT INTO wp_livehelp_countries VALUES ('BJ', 'Benin');
		INSERT INTO wp_livehelp_countries VALUES ('BM', 'Bermuda');
		INSERT INTO wp_livehelp_countries VALUES ('BN', 'Brunei Darussalam');
		INSERT INTO wp_livehelp_countries VALUES ('BO', 'Bolivia');
		INSERT INTO wp_livehelp_countries VALUES ('BR', 'Brazil');
		INSERT INTO wp_livehelp_countries VALUES ('BS', 'Bahamas');
		INSERT INTO wp_livehelp_countries VALUES ('BT', 'Bhutan');
		INSERT INTO wp_livehelp_countries VALUES ('BV', 'Bouvet Island');
		INSERT INTO wp_livehelp_countries VALUES ('BW', 'Botswana');
		INSERT INTO wp_livehelp_countries VALUES ('BY', 'Belarus');
		INSERT INTO wp_livehelp_countries VALUES ('BZ', 'Belize');
		INSERT INTO wp_livehelp_countries VALUES ('CA', 'Canada');
		INSERT INTO wp_livehelp_countries VALUES ('CC', 'Cocos (Keeling) Islands');
		INSERT INTO wp_livehelp_countries VALUES ('CD', 'Congo  The Democratic Republic of the');
		INSERT INTO wp_livehelp_countries VALUES ('CF', 'Central African Republic');
		INSERT INTO wp_livehelp_countries VALUES ('CG', 'Congo');
		INSERT INTO wp_livehelp_countries VALUES ('CH', 'Switzerland');
		INSERT INTO wp_livehelp_countries VALUES ('CI', 'Cote d Ivoire');
		INSERT INTO wp_livehelp_countries VALUES ('CK', 'Cook Islands');
		INSERT INTO wp_livehelp_countries VALUES ('CL', 'Chile');
		INSERT INTO wp_livehelp_countries VALUES ('CM', 'Cameroon');
		INSERT INTO wp_livehelp_countries VALUES ('CN', 'China');
		INSERT INTO wp_livehelp_countries VALUES ('CO', 'Colombia');
		INSERT INTO wp_livehelp_countries VALUES ('CR', 'Costa Rica');
		INSERT INTO wp_livehelp_countries VALUES ('CU', 'Cuba');
		INSERT INTO wp_livehelp_countries VALUES ('CV', 'Cape Verde');
		INSERT INTO wp_livehelp_countries VALUES ('CX', 'Christmas Island');
		INSERT INTO wp_livehelp_countries VALUES ('CY', 'Cyprus');
		INSERT INTO wp_livehelp_countries VALUES ('CZ', 'Czech Republic');
		INSERT INTO wp_livehelp_countries VALUES ('DE', 'Germany');
		INSERT INTO wp_livehelp_countries VALUES ('DJ', 'Djibouti');
		INSERT INTO wp_livehelp_countries VALUES ('DK', 'Denmark');
		INSERT INTO wp_livehelp_countries VALUES ('DM', 'Dominica');
		INSERT INTO wp_livehelp_countries VALUES ('DO', 'Dominican Republic');
		INSERT INTO wp_livehelp_countries VALUES ('DZ', 'Algeria');
		INSERT INTO wp_livehelp_countries VALUES ('EC', 'Ecuador');
		INSERT INTO wp_livehelp_countries VALUES ('EE', 'Estonia');
		INSERT INTO wp_livehelp_countries VALUES ('EG', 'Egypt');
		INSERT INTO wp_livehelp_countries VALUES ('EH', 'Western Sahara');
		INSERT INTO wp_livehelp_countries VALUES ('ER', 'Eritrea');
		INSERT INTO wp_livehelp_countries VALUES ('ES', 'Spain');
		INSERT INTO wp_livehelp_countries VALUES ('ET', 'Ethiopia');
		INSERT INTO wp_livehelp_countries VALUES ('EU', 'Europe');
		INSERT INTO wp_livehelp_countries VALUES ('FI', 'Finland');
		INSERT INTO wp_livehelp_countries VALUES ('FJ', 'Fiji');
		INSERT INTO wp_livehelp_countries VALUES ('FK', 'Falkland Islands (Malvinas)');
		INSERT INTO wp_livehelp_countries VALUES ('FM', 'Micronesia  Federated States of');
		INSERT INTO wp_livehelp_countries VALUES ('FO', 'Faroe Islands');
		INSERT INTO wp_livehelp_countries VALUES ('FR', 'France');
		INSERT INTO wp_livehelp_countries VALUES ('GA', 'Gabon');
		INSERT INTO wp_livehelp_countries VALUES ('GB', 'United Kingdom');
		INSERT INTO wp_livehelp_countries VALUES ('GD', 'Grenada');
		INSERT INTO wp_livehelp_countries VALUES ('GE', 'Georgia');
		INSERT INTO wp_livehelp_countries VALUES ('GF', 'French Guiana');
		INSERT INTO wp_livehelp_countries VALUES ('GG', 'Guernsey');
		INSERT INTO wp_livehelp_countries VALUES ('GH', 'Ghana');
		INSERT INTO wp_livehelp_countries VALUES ('GI', 'Gibraltar');
		INSERT INTO wp_livehelp_countries VALUES ('GL', 'Greenland');
		INSERT INTO wp_livehelp_countries VALUES ('GM', 'Gambia');
		INSERT INTO wp_livehelp_countries VALUES ('GN', 'Guinea');
		INSERT INTO wp_livehelp_countries VALUES ('GP', 'Guadeloupe');
		INSERT INTO wp_livehelp_countries VALUES ('GQ', 'Equatorial Guinea');
		INSERT INTO wp_livehelp_countries VALUES ('GR', 'Greece');
		INSERT INTO wp_livehelp_countries VALUES ('GS', 'South Georgia and the South Sandwich Islands');
		INSERT INTO wp_livehelp_countries VALUES ('GT', 'Guatemala');
		INSERT INTO wp_livehelp_countries VALUES ('GU', 'Guam');
		INSERT INTO wp_livehelp_countries VALUES ('GW', 'Guinea-Bissau');
		INSERT INTO wp_livehelp_countries VALUES ('GY', 'Guyana');
		INSERT INTO wp_livehelp_countries VALUES ('HK', 'Hong Kong');
		INSERT INTO wp_livehelp_countries VALUES ('HM', 'Heard Island and McDonald Islands');
		INSERT INTO wp_livehelp_countries VALUES ('HN', 'Honduras');
		INSERT INTO wp_livehelp_countries VALUES ('HR', 'Croatia');
		INSERT INTO wp_livehelp_countries VALUES ('HT', 'Haiti');
		INSERT INTO wp_livehelp_countries VALUES ('HU', 'Hungary');
		INSERT INTO wp_livehelp_countries VALUES ('ID', 'Indonesia');
		INSERT INTO wp_livehelp_countries VALUES ('IE', 'Ireland');
		INSERT INTO wp_livehelp_countries VALUES ('IL', 'Israel');
		INSERT INTO wp_livehelp_countries VALUES ('IM', 'Isle of Man');
		INSERT INTO wp_livehelp_countries VALUES ('IN', 'India');
		INSERT INTO wp_livehelp_countries VALUES ('IO', 'British Indian Ocean Territory');
		INSERT INTO wp_livehelp_countries VALUES ('IQ', 'Iraq');
		INSERT INTO wp_livehelp_countries VALUES ('IR', 'Iran  Islamic Republic of');
		INSERT INTO wp_livehelp_countries VALUES ('IS', 'Iceland');
		INSERT INTO wp_livehelp_countries VALUES ('IT', 'Italy');
		INSERT INTO wp_livehelp_countries VALUES ('JE', 'Jersey');
		INSERT INTO wp_livehelp_countries VALUES ('JM', 'Jamaica');
		INSERT INTO wp_livehelp_countries VALUES ('JO', 'Jordan');
		INSERT INTO wp_livehelp_countries VALUES ('JP', 'Japan');
		INSERT INTO wp_livehelp_countries VALUES ('KE', 'Kenya');
		INSERT INTO wp_livehelp_countries VALUES ('KG', 'Kyrgyzstan');
		INSERT INTO wp_livehelp_countries VALUES ('KH', 'Cambodia');
		INSERT INTO wp_livehelp_countries VALUES ('KI', 'Kiribati');
		INSERT INTO wp_livehelp_countries VALUES ('KM', 'Comoros');
		INSERT INTO wp_livehelp_countries VALUES ('KN', 'Saint Kitts and Nevis');
		INSERT INTO wp_livehelp_countries VALUES ('KP', 'Korea  Democratic People Republic of');
		INSERT INTO wp_livehelp_countries VALUES ('KR', 'Korea  Republic of');
		INSERT INTO wp_livehelp_countries VALUES ('KW', 'Kuwait');
		INSERT INTO wp_livehelp_countries VALUES ('KY', 'Cayman Islands');
		INSERT INTO wp_livehelp_countries VALUES ('KZ', 'Kazakhstan');
		INSERT INTO wp_livehelp_countries VALUES ('LA', 'Lao People  Democratic Republic');
		INSERT INTO wp_livehelp_countries VALUES ('LB', 'Lebanon');
		INSERT INTO wp_livehelp_countries VALUES ('LC', 'Saint Lucia');
		INSERT INTO wp_livehelp_countries VALUES ('LI', 'Liechtenstein');
		INSERT INTO wp_livehelp_countries VALUES ('LK', 'Sri Lanka');
		INSERT INTO wp_livehelp_countries VALUES ('LR', 'Liberia');
		INSERT INTO wp_livehelp_countries VALUES ('LS', 'Lesotho');
		INSERT INTO wp_livehelp_countries VALUES ('LT', 'Lithuania');
		INSERT INTO wp_livehelp_countries VALUES ('LU', 'Luxembourg');
		INSERT INTO wp_livehelp_countries VALUES ('LV', 'Latvia');
		INSERT INTO wp_livehelp_countries VALUES ('LY', 'Libyan Arab Jamahiriya');
		INSERT INTO wp_livehelp_countries VALUES ('MA', 'Morocco');
		INSERT INTO wp_livehelp_countries VALUES ('MC', 'Monaco');
		INSERT INTO wp_livehelp_countries VALUES ('MD', 'Moldova  Republic of');
		INSERT INTO wp_livehelp_countries VALUES ('ME', 'Montenegro');
		INSERT INTO wp_livehelp_countries VALUES ('MG', 'Madagascar');
		INSERT INTO wp_livehelp_countries VALUES ('MH', 'Marshall Islands');
		INSERT INTO wp_livehelp_countries VALUES ('MK', 'Macedonia');
		INSERT INTO wp_livehelp_countries VALUES ('ML', 'Mali');
		INSERT INTO wp_livehelp_countries VALUES ('MM', 'Myanmar');
		INSERT INTO wp_livehelp_countries VALUES ('MN', 'Mongolia');
		INSERT INTO wp_livehelp_countries VALUES ('MO', 'Macao');
		INSERT INTO wp_livehelp_countries VALUES ('MP', 'Northern Mariana Islands');
		INSERT INTO wp_livehelp_countries VALUES ('MQ', 'Martinique');
		INSERT INTO wp_livehelp_countries VALUES ('MR', 'Mauritania');
		INSERT INTO wp_livehelp_countries VALUES ('MS', 'Montserrat');
		INSERT INTO wp_livehelp_countries VALUES ('MT', 'Malta');
		INSERT INTO wp_livehelp_countries VALUES ('MU', 'Mauritius');
		INSERT INTO wp_livehelp_countries VALUES ('MV', 'Maldives');
		INSERT INTO wp_livehelp_countries VALUES ('MW', 'Malawi');
		INSERT INTO wp_livehelp_countries VALUES ('MX', 'Mexico');
		INSERT INTO wp_livehelp_countries VALUES ('MY', 'Malaysia');
		INSERT INTO wp_livehelp_countries VALUES ('MZ', 'Mozambique');
		INSERT INTO wp_livehelp_countries VALUES ('NA', 'Namibia');
		INSERT INTO wp_livehelp_countries VALUES ('NC', 'New Caledonia');
		INSERT INTO wp_livehelp_countries VALUES ('NE', 'Niger');
		INSERT INTO wp_livehelp_countries VALUES ('NF', 'Norfolk Island');
		INSERT INTO wp_livehelp_countries VALUES ('NG', 'Nigeria');
		INSERT INTO wp_livehelp_countries VALUES ('NI', 'Nicaragua');
		INSERT INTO wp_livehelp_countries VALUES ('NL', 'Netherlands');
		INSERT INTO wp_livehelp_countries VALUES ('NO', 'Norway');
		INSERT INTO wp_livehelp_countries VALUES ('NP', 'Nepal');
		INSERT INTO wp_livehelp_countries VALUES ('NR', 'Nauru');
		INSERT INTO wp_livehelp_countries VALUES ('NU', 'Niue');
		INSERT INTO wp_livehelp_countries VALUES ('NZ', 'New Zealand');
		INSERT INTO wp_livehelp_countries VALUES ('OM', 'Oman');
		INSERT INTO wp_livehelp_countries VALUES ('PA', 'Panama');
		INSERT INTO wp_livehelp_countries VALUES ('PE', 'Peru');
		INSERT INTO wp_livehelp_countries VALUES ('PF', 'French Polynesia');
		INSERT INTO wp_livehelp_countries VALUES ('PG', 'Papua New Guinea');
		INSERT INTO wp_livehelp_countries VALUES ('PH', 'Philippines');
		INSERT INTO wp_livehelp_countries VALUES ('PK', 'Pakistan');
		INSERT INTO wp_livehelp_countries VALUES ('PL', 'Poland');
		INSERT INTO wp_livehelp_countries VALUES ('PM', 'Saint Pierre and Miquelon');
		INSERT INTO wp_livehelp_countries VALUES ('PN', 'Pitcairn');
		INSERT INTO wp_livehelp_countries VALUES ('PR', 'Puerto Rico');
		INSERT INTO wp_livehelp_countries VALUES ('PS', 'Palestinian Territory');
		INSERT INTO wp_livehelp_countries VALUES ('PT', 'Portugal');
		INSERT INTO wp_livehelp_countries VALUES ('PW', 'Palau');
		INSERT INTO wp_livehelp_countries VALUES ('PY', 'Paraguay');
		INSERT INTO wp_livehelp_countries VALUES ('QA', 'Qatar');
		INSERT INTO wp_livehelp_countries VALUES ('RE', 'Reunion');
		INSERT INTO wp_livehelp_countries VALUES ('RO', 'Romania');
		INSERT INTO wp_livehelp_countries VALUES ('RS', 'Serbia');
		INSERT INTO wp_livehelp_countries VALUES ('RU', 'Russian Federation');
		INSERT INTO wp_livehelp_countries VALUES ('RW', 'Rwanda');
		INSERT INTO wp_livehelp_countries VALUES ('SA', 'Saudi Arabia');
		INSERT INTO wp_livehelp_countries VALUES ('SB', 'Solomon Islands');
		INSERT INTO wp_livehelp_countries VALUES ('SC', 'Seychelles');
		INSERT INTO wp_livehelp_countries VALUES ('SD', 'Sudan');
		INSERT INTO wp_livehelp_countries VALUES ('SE', 'Sweden');
		INSERT INTO wp_livehelp_countries VALUES ('SG', 'Singapore');
		INSERT INTO wp_livehelp_countries VALUES ('SH', 'Saint Helena');
		INSERT INTO wp_livehelp_countries VALUES ('SI', 'Slovenia');
		INSERT INTO wp_livehelp_countries VALUES ('SJ', 'Svalbard and Jan Mayen');
		INSERT INTO wp_livehelp_countries VALUES ('SK', 'Slovakia');
		INSERT INTO wp_livehelp_countries VALUES ('SL', 'Sierra Leone');
		INSERT INTO wp_livehelp_countries VALUES ('SM', 'San Marino');
		INSERT INTO wp_livehelp_countries VALUES ('SN', 'Senegal');
		INSERT INTO wp_livehelp_countries VALUES ('SO', 'Somalia');
		INSERT INTO wp_livehelp_countries VALUES ('SR', 'Suriname');
		INSERT INTO wp_livehelp_countries VALUES ('ST', 'Sao Tome and Principe');
		INSERT INTO wp_livehelp_countries VALUES ('SV', 'El Salvador');
		INSERT INTO wp_livehelp_countries VALUES ('SY', 'Syrian Arab Republic');
		INSERT INTO wp_livehelp_countries VALUES ('SZ', 'Swaziland');
		INSERT INTO wp_livehelp_countries VALUES ('TC', 'Turks and Caicos Islands');
		INSERT INTO wp_livehelp_countries VALUES ('TD', 'Chad');
		INSERT INTO wp_livehelp_countries VALUES ('TF', 'French Southern Territories');
		INSERT INTO wp_livehelp_countries VALUES ('TG', 'Togo');
		INSERT INTO wp_livehelp_countries VALUES ('TH', 'Thailand');
		INSERT INTO wp_livehelp_countries VALUES ('TJ', 'Tajikistan');
		INSERT INTO wp_livehelp_countries VALUES ('TK', 'Tokelau');
		INSERT INTO wp_livehelp_countries VALUES ('TL', 'Timor-Leste');
		INSERT INTO wp_livehelp_countries VALUES ('TM', 'Turkmenistan');
		INSERT INTO wp_livehelp_countries VALUES ('TN', 'Tunisia');
		INSERT INTO wp_livehelp_countries VALUES ('TO', 'Tonga');
		INSERT INTO wp_livehelp_countries VALUES ('TR', 'Turkey');
		INSERT INTO wp_livehelp_countries VALUES ('TT', 'Trinidad and Tobago');
		INSERT INTO wp_livehelp_countries VALUES ('TV', 'Tuvalu');
		INSERT INTO wp_livehelp_countries VALUES ('TW', 'Taiwan');
		INSERT INTO wp_livehelp_countries VALUES ('TZ', 'Tanzania  United Republic of');
		INSERT INTO wp_livehelp_countries VALUES ('UA', 'Ukraine');
		INSERT INTO wp_livehelp_countries VALUES ('UG', 'Uganda');
		INSERT INTO wp_livehelp_countries VALUES ('UM', 'United States Minor Outlying Islands');
		INSERT INTO wp_livehelp_countries VALUES ('US', 'United States');
		INSERT INTO wp_livehelp_countries VALUES ('UY', 'Uruguay');
		INSERT INTO wp_livehelp_countries VALUES ('UZ', 'Uzbekistan');
		INSERT INTO wp_livehelp_countries VALUES ('VA', 'Holy See (Vatican City State)');
		INSERT INTO wp_livehelp_countries VALUES ('VC', 'Saint Vincent and the Grenadines');
		INSERT INTO wp_livehelp_countries VALUES ('VE', 'Venezuela');
		INSERT INTO wp_livehelp_countries VALUES ('VG', 'Virgin Islands  British');
		INSERT INTO wp_livehelp_countries VALUES ('VI', 'Virgin Islands  U.S.');
		INSERT INTO wp_livehelp_countries VALUES ('VN', 'Vietnam');
		INSERT INTO wp_livehelp_countries VALUES ('VU', 'Vanuatu');
		INSERT INTO wp_livehelp_countries VALUES ('WF', 'Wallis and Futuna');
		INSERT INTO wp_livehelp_countries VALUES ('WS', 'Samoa');
		INSERT INTO wp_livehelp_countries VALUES ('YE', 'Yemen');
		INSERT INTO wp_livehelp_countries VALUES ('YT', 'Mayotte');
		INSERT INTO wp_livehelp_countries VALUES ('ZA', 'South Africa');
		INSERT INTO wp_livehelp_countries VALUES ('ZM', 'Zambia');
		INSERT INTO wp_livehelp_countries VALUES ('ZW', 'Zimbabwe');

		CREATE TABLE `wp_livehelp_not_allowed_countries` (
			`id` bigint(20) NOT NULL AUTO_INCREMENT,
			`id_domain` int(11) NOT NULL DEFAULT '0',
			`code` varchar(2) NOT NULL,
			PRIMARY KEY (`id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;
	";

	return $installQuery;
}

function activeHelper_liveHelp_uninstallQuery()
{
	$uninstallQuery = "
		DROP TABLE IF EXISTS wp_livehelp_account_activation;
		DROP TABLE IF EXISTS wp_livehelp_account_type;
		DROP TABLE IF EXISTS wp_livehelp_accounts;
		DROP TABLE IF EXISTS wp_livehelp_accounts_domain;
		DROP TABLE IF EXISTS wp_livehelp_administration;
		DROP TABLE IF EXISTS wp_livehelp_commands;
		DROP TABLE IF EXISTS wp_livehelp_domain_alias;
		DROP TABLE IF EXISTS wp_livehelp_domain_user;
		DROP TABLE IF EXISTS wp_livehelp_domains;
		DROP TABLE IF EXISTS wp_livehelp_ge_global_settings;
		DROP TABLE IF EXISTS wp_livehelp_ip2country;
		DROP TABLE IF EXISTS wp_livehelp_webcall;
		DROP TABLE IF EXISTS wp_livehelp_users;
		DROP TABLE IF EXISTS wp_livehelp_top_level_domains;
		DROP TABLE IF EXISTS wp_livehelp_statuses;
		DROP TABLE IF EXISTS wp_livehelp_settings;
		DROP TABLE IF EXISTS wp_livehelp_sessions;
		DROP TABLE IF EXISTS wp_livehelp_search_engines;
		DROP TABLE IF EXISTS wp_livehelp_languages;
		DROP TABLE IF EXISTS wp_livehelp_languages_domain;
		DROP TABLE IF EXISTS wp_livehelp_messages;
		DROP TABLE IF EXISTS wp_livehelp_public_emails;
		DROP TABLE IF EXISTS wp_livehelp_requests;
		DROP TABLE IF EXISTS wp_livehelp_responses;
		DROP TABLE IF EXISTS wp_livehelp_sa_domain_user_role;
		DROP TABLE IF EXISTS wp_livehelp_sa_role;
		DROP TABLE IF EXISTS wp_livehelp_sa_role_services;
		DROP TABLE IF EXISTS wp_livehelp_search_engine_domain;
		DROP TABLE IF EXISTS wp_livehelp_se_services;  
		DROP TABLE IF EXISTS wp_livehelp_offline_messages;
		DROP TABLE IF EXISTS wp_livehelp_not_allowed_countries;
		DROP TABLE IF EXISTS wp_livehelp_countries;
	";

	return $uninstallQuery;
}

