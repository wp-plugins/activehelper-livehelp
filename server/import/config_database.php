<?php

if (!defined('__CONFIG_DATABASE_INC'))
{
	define('__CONFIG_DATABASE_INC', 1);
	define( 'DS', DIRECTORY_SEPARATOR );

	include_once('constants.php');   
	include_once('jlhconst.php');

	define("DB_HOST", "");
	define("DB_USER", "");
	define("DB_PASS", "");
	define("DB_NAME", "");

	$table_prefix = 'wp_livehelp_';
}

?>