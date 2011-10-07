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

	// modifying prefix in database config file
	$configFile = $activeHelper_liveHelp['baseDir'] . '/server/import/config_database.php';

	$fhandle = fopen($configFile, "r");
	$content = fread($fhandle, filesize($configFile));

	$content = str_replace('$table_prefix = \'wp_livehelp_\';', '$table_prefix = \'' . $wpdb->prefix . 'livehelp_\';', $content);
	
	$content = str_replace('define("DB_HOST", "");', 'define("DB_HOST", "' . DB_HOST . '");', $content);
	$content = str_replace('define("DB_USER", "");', 'define("DB_USER", "' . DB_USER . '");', $content);
	$content = str_replace('define("DB_PASS", "");', 'define("DB_PASS", "' . DB_PASSWORD . '");', $content);
	$content = str_replace('define("DB_NAME", "");', 'define("DB_NAME", "' . DB_NAME . '");', $content);

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
	";

	return $uninstallQuery;
}

