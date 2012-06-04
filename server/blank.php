<?php

include_once('import/config_database.php');
include_once('import/class.mysql.php');
include_once('import/config.php');
include_once('import/block_spiders.php');

if (isset($_REQUEST['DOMAINID'])){
  $domainId = (int) $_REQUEST['DOMAINID'];
}

// Find total guest visitors that are pending within the selected department
$query = "SELECT `department` FROM " . $table_prefix . "sessions WHERE `id` = '" . ( (int) $guest_login_id ) . "'";
$row = $SQL->selectquery($query);
if (is_array($row)) {
        $department = $row['department'];
        $query = "SELECT count(`id`) FROM " . $table_prefix . "sessions WHERE (UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(`refresh`)) < '$connection_timeout' AND `active` = '0' AND `department` LIKE '%$department%'";
}
else {
        $query = "SELECT count(`id`) FROM " . $table_prefix . "sessions WHERE (UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(`refresh`)) < '$connection_timeout' AND `active` = '0'";
}
$row = $SQL->selectquery($query);
if (is_array($row)) {
        $users_online = $row['count(`id`)'];
}
else {
        $users_online = '1';
}


header("Content-type: text/html; charset=utf-8");

$language = substr(LANGUAGE_TYPE,0,2); 
$language_file = './i18n/' . $language . '/lang_guest_' . $language . '.php';


if (file_exists($language_file)) {
        include($language_file);
}
else {
        include('./i18n/en/lang_guest_en.php');
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title><?php echo($livehelp_name); ?></title>

<link href="./style/styles.php?<?php echo('DOMAINID='.$domainId);?>" rel="stylesheet" type="text/css">

<style type="text/css">
<!--
.background {
        background-image: url(./i18n/<?php echo($language); ?>/pictures/connecting.gif);
        background-repeat: no-repeat;
        background-position: center center;
}
-->
</style>

</head>
<body bgcolor="#FFFFFF" class="background">
<div align="center">
  <table width="100%" border="0" cellspacing="2" cellpadding="2">
    <tr>
      <td align="center"><?php echo($thank_you_patience_label); ?></td>
    </tr>
    <tr>
      <td height="76">&nbsp;</td>
    </tr>
    <tr>
      <td align="center"><div align="right"><span class="small"><?php echo($currently_label . ' ' . $users_online . ' ' . $users_waiting_label); ?>. [<a href="#" class="normlink" onClick="top.displayFrame.displayContentsFrame.location.reload(true);"><?php echo($refresh_label); ?></a>] </span></div></td>
    </tr>
  </table>
</div>
</body>
</html>
