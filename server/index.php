<?php
include_once('import/constants.php');

$_REQUEST['URL'] = !isset( $_REQUEST['URL'] ) ? '' : (string) $_REQUEST['URL'];
$_REQUEST['TITLE'] = !isset( $_REQUEST['TITLE'] ) ? '' : htmlspecialchars( (string) $_REQUEST['TITLE'], ENT_QUOTES );
$_REQUEST['DEPARTMENT'] = !isset( $_REQUEST['DEPARTMENT'] ) ? '' : htmlspecialchars( (string) $_REQUEST['DEPARTMENT'], ENT_QUOTES );
$_REQUEST['ERROR'] = !isset( $_REQUEST['ERROR'] ) ? '' : htmlspecialchars( (string) $_REQUEST['ERROR'], ENT_QUOTES );
$_REQUEST['COOKIE'] = !isset( $_REQUEST['COOKIE'] ) ? '' : htmlspecialchars( (string) $_REQUEST['COOKIE'], ENT_QUOTES );
$_REQUEST['SERVER'] = !isset( $_REQUEST['SERVER'] ) ? '' : htmlspecialchars( (string) $_REQUEST['SERVER'], ENT_QUOTES );

if (isset($_REQUEST['DOMAINID'])){
  $domain_id = (int) $_REQUEST['DOMAINID'];
}


if (!isset($_REQUEST['URL'])) {
        header('Location: offline.php?'.'DOMAINID='.$domain_id.'&LANGUAGE='.LANGUAGE_TYPE);
        exit();
}

if (isset($_SERVER['PATH_TRANSLATED']) && $_SERVER['PATH_TRANSLATED'] != '') { $env_path = $_SERVER['PATH_TRANSLATED']; } else { $env_path = $_SERVER['SCRIPT_FILENAME']; }
$full_path = str_replace("\\\\", "\\", $env_path);
$livehelp_path = $_SERVER['PHP_SELF'];
if (strpos($full_path, '/') === false) { $livehelp_path = str_replace("/", "\\", $livehelp_path); }
$pos = strpos($full_path, $livehelp_path);
if ($pos === false) {
        $install_path = $full_path;
}
else {
        $install_path = substr($full_path, 0, $pos);
}

$installed = false;

include('./import/constants.php');
$database = include($install_path . $install_directory . '/import/config_database.php');
if ($database) {
        include($install_path . $install_directory . '/import/block_spiders.php');
        include($install_path . $install_directory . '/import/class.mysql.php');
        $installed = include($install_path . $install_directory . '/import/config.php');
} else {
        $installed = false;
}


//$domain_Id = $domain_id;

if ($installed == false) {      
        header('Location: ' . $install_directory . '/offline.php?URL=' . urlencode( $_REQUEST['URL'] ) . '&DOMAINID='.$domain_id.'&LANGUAGE='.LANGUAGE_TYPE);
        exit();
}

$error = $_REQUEST['ERROR'];



if ($installed == true) {

        $department = $_REQUEST['DEPARTMENT'];
        $current_page = $_REQUEST['URL'];
        $title = $_REQUEST['TITLE'];

        // Update the Current URL, URL Title and Referer in the requests table.
        $current_page = $_REQUEST['URL'];
        for ($i = 0; $i < 3; $i++) {
                $substr_pos = strpos($current_page, '/');
                if ($substr_pos === false) {
                        $current_page = '';
                        break;
                }
                if ($i < 2) {
                        $current_page = substr($current_page, $substr_pos + 1);
                }
                elseif ($i >= 2) {
                        $current_page = substr($current_page, $substr_pos);
                }
        }

        // Get the current host from the request data
        $current_host = $_REQUEST['URL'];
        $str_start = 0;
        for ($i = 0; $i < 3; $i++) {
                $substr_pos = strpos($current_host, '/');
                if ($substr_pos === false) {
                        break;
                }
                if ($i < 2) {
                        $current_host = substr($current_host, $substr_pos + 1);
                        $str_start += $substr_pos + 1;
                }
                elseif ($i >= 2) {
                        $current_host = substr($_REQUEST['URL'], 0, $substr_pos + $str_start);
                }
        }

       
         // Deparment disable
         $disable_department = $departments;

        // Joomla Auto Login
        $query = "SELECT visitor_name , visitor_email FROM " . $table_prefix . "requests WHERE id = " . ( (int) $request_id );
        $row = $SQL->selectquery($query);
        
         if (is_array($row)) {
          
          if ($row['visitor_name'] != '') 
          {  
            $username = $row['visitor_name'];
            $email = $row['visitor_email'];
            $autologin =TRUE;
            
            // Count available departments
            $query = "SELECT DISTINCT u.department FROM " . $table_prefix . "users u, " . $table_prefix . "domain_user du WHERE (UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(u.refresh)) < '$connection_timeout' AND u.status = '1' And u.id = du.id_user And du.id_domain = " . $domain_id;
            $rows = $SQL->selectall($query);
             
             if (is_array($rows)) {
               $dep_num = count($rows);
             }
           }
         }
                
 //  User Recognition Auto Start
 if ($disable_login_details == false && $autologin == true && $dep_num ==1 ) {       
      header('Location: ' . $install_directory . '/frames.php?URL=' . urlencode( $_REQUEST['URL'] ) . '&SERVER=' . $_REQUEST['SERVER'].'&DOMAINID='.$domain_id .'&USER='.$username .'&EMAIL='.$email . '&LANGUAGE='.LANGUAGE_TYPE);
    exit();
    }
  
        // Update the current URL statistics within the requests tables
        if ($current_page == '') { $current_page = '/'; }

        $query = "SELECT `path` FROM " . $table_prefix . "requests WHERE `id` = '" . ( (int) $request_id ) . "'";
        $row = $SQL->selectquery($query);
        if (is_array($row)) {
                $current_page = urldecode($current_page);
                $prev_path = explode(';  ', $row['path']);
                $current_path = $row['path'];

                end($prev_path);
                $index = key($prev_path);


                if ($current_page != $prev_path[$index]) {
                        $query = "UPDATE " . $table_prefix . "requests SET `url` = '$current_page', `title` = '$title', `path` = '$current_path;  $current_page', number_pages = number_pages + 1 WHERE `id` = '$request_id'";

                        $SQL->insertquery($query);
                }
        }
        if (!isset($_COOKIE[$cookieName])) {
                //TODO - Revisar este codigo debe mejorarse cuando el usuario va directamente al archivo index.php
                header('Location: ' . $install_directory . '/cookies.php?SERVER=' . $_REQUEST['SERVER'] . '&COOKIE=true'.'&DOMAINID='.$domain_id);
                exit();
        }

        // Checks if any users in user table are online
        if ($error == '') {
                $query = "SELECT u.id FROM " . $table_prefix . "users u, " . $table_prefix . "domain_user du WHERE (UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(u.refresh)) < '$connection_timeout' AND u.status = '1' And du.id_user = u.id And du.id_domain = ".$domain_id;
                if ($department != '' && $departments == true) { $query .= " AND department LIKE '%$department%'"; }
                $row = $SQL->selectquery($query);


                if(!is_array($row))
                   {
              
                      header('Location: ' . $install_directory . '/offline.php?DOMAINID='.$domain_id.'&SERVER='.$_REQUEST['SERVER'].'&URL='. urlencode( $_REQUEST['URL'] ) .'&LANGUAGE='.LANGUAGE_TYPE);                        
                      
                        exit();
                }
        }

        if ($disable_login_details == true) {
                header('Location: ' . $install_directory . '/frames.php?URL=' . urlencode( $_REQUEST['URL'] ) . '&SERVER=' . $_REQUEST['SERVER'].'&DOMAINID='.$domain_id.'&LANGUAGE='.LANGUAGE_TYPE);
                
                exit();
        }

        //invalidating old 'GUEST_LOGIN_ID' to create a new session for the chat
        if (isset($_COOKIE[$cookieName])) {
            $session = array();
            $session = unserialize($_COOKIE[$cookieName]);

            $session['GUEST_LOGIN_ID'] = "0";
            $data = serialize($session);

            setCookie($cookieName, $data, false, '/', $cookie_domain, $ssl);

            header("P3P: CP='$p3p'");

            /*
            foreach ($session as $key => $value) {

            }
            */

            unset($session);

        }


}

header('Content-type: text/html; charset=' . CHARSET);

$language_file = './i18n/' . LANGUAGE_TYPE . '/lang_guest_' . LANGUAGE_TYPE . '.php';
if (file_exists($language_file)) {
        include($language_file);
}
else {
        include('./i18n/en/lang_guest_en.php');
}


include_once('import/settings_default.php');
  
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title><?php echo($livehelp_name); ?></title>
<link href="<?=$install_directory?>/style/styles.php?<?echo('DOMAINID='.$domain_id);?>" rel="stylesheet" type="text/css">
<style type="text/css">
<!--
.background {
  /*      background-image: url(./i18n/<?=LANGUAGE_TYPE?>/pictures/background.gif);
     */   background-repeat: no-repeat;
        background-position: right top;
        margin-left: 0px;
        margin-top: 0px;
}
-->
</style>
<script language="JavaScript" type="text/JavaScript">
<!--
function disableForm() {
        document.login.Submit.disabled = true;
        return true;
}

function chForm() {
<?
if($require_guest_details == 1) {
?>
        if(document.getElementById("USER").value == "") {
                alert("<?php echo($empty_user_details_label); ?>")
                return false
        }
        if(document.getElementById("EMAIL").value == "") {
                alert("<?php echo($empty_email_details_label); ?>")
                return false
        }
        if(document.getElementById("EMAIL").value.indexOf("@") == -1) {
                alert("<?php echo($empty_valid_email_details_label); ?>")
                return false
        }
<?
}
?>
        disableForm()
        document.getElementById("login").submit()
}

//-->
 
</script>

</head>

<body bgcolor="<?php echo($background_color); ?>" text="<?php echo($font_color); ?>" link="<?php echo($font_link_color); ?>" vlink="<?php echo($font_link_color); ?>" alink="<?php echo($font_link_color); ?>" class="background">

<!--<?=CHARSET?>-->

<!--img src="./i18n/<?=LANGUAGE_TYPE?>/pictures/background_online.gif" width="265" height="49" style="position: relative; right: -200px; top: 10px;"-->
<?
if ($error == 'email') {
?>
<strong><?php echo($invalid_email_error_label); ?></strong>
<?
}
if ($error == 'empty') {
?>
<strong><?php echo($empty_user_details_label); ?></strong>
<?
}
?>

<form name="login" id="login" method="POST" action="frames.php?SERVER=<?php echo($_REQUEST['SERVER']); ?>&URL=<?php echo($_REQUEST['URL']); ?><?echo('&DOMAINID='.$domain_id.'&LANGUAGE='.LANGUAGE_TYPE);?>">

  <input type="hidden" name="DOMAINID" value="<?php echo($domain_id); ?>"/>
        <table border="0" align="center" cellspacing="0" bordercolor="#111111" style="border:0px solid #CCCCCC;padding:7 20 7 20;" height="350" width="450px">
                <tr>
                        <td colspan="2"><p><b><?php echo($welcome_to_label); ?> <!--?php echo($site_name); ?--> <?php echo($our_live_help_label); ?><b><br></p></td>
                </tr>
                <tr>
                        <td colspan="2" ><?php echo($enter_guest_details_label); ?></td>
                </tr>
                <tr>
                        <td colspan="2" class="subheader"><?php echo($else_send_message_label); ?> <a href="offline.php?SERVER=<?php echo($_REQUEST['SERVER']); ?>&URL=<?= urlencode( $_REQUEST['URL']) ?><?echo('&DOMAINID='.$domain_id.'&LANGUAGE='.LANGUAGE_TYPE);?>" class="normlink"><?php echo($offline_message_label); ?></a></td>
                </tr>
                <tr>
                        <td width="250"><strong><?php echo($name_label); ?></strong>:</td>
                        
                         <? if ($username !='') { ?>
                         
                          <td><font face="arial" size="2"><input name="USER" id="USER" type="text" value ="<?php echo($username); ?>"  READONLY ="TRUE"  style="width:175px;filter:alpha(opacity=75);moz-opacity:0.75" maxlength="20"></font></td>
                        <? } else { ?>                        
                          <td><font face="arial" size="2"><input name="USER" id="USER" type="text" style="width:175px;filter:alpha(opacity=75);moz-opacity:0.75" maxlength="20"></font></td>
                        <? } ?>
                        
                </tr>
                <tr>
                        <td><strong><?php echo($email_label); ?></strong>:</td>
                        
                         <? if ($email !='') { ?>
                         <td><font face="arial" size="2"><input type="text"  value ="<?php echo($email); ?>" name="EMAIL" id="EMAIL"  READONLY ="TRUE"  style="width:175px;filter:alpha(opacity=75);moz-opacity:0.75"></font></td>
                        <? } else { ?>
                          <td><font face="arial" size="2"><input type="text"  name="EMAIL" id="EMAIL" style="width:175px;filter:alpha(opacity=75);moz-opacity:0.75"></font></td>
                         <? } ?>
                </tr>
                
             <?
             // Languague display option 
             
              $query = "SELECT code, name FROM " . $table_prefix . "languages_domain Where Id_domain = " . $domain_id . " Order By name";
              $lang_count = $SQL->selcount($query);  
              
              $disable_language =0; 
    
              // find the custom form link
              $query = "SELECT `value` FROM " . $table_prefix . "settings WHERE `id_domain`= '$domain_id' and name ='disable_language'";

             $row = $SQL->selectquery($query);
             if (is_array($row)) {
               $disable_language = $row['value'];                                  
               }
            
             ?>        
            
            <? if ($lang_count > 1 && $disable_language ==0) { ?>
                
           <tr>
                        <td><strong><?php echo($select_language_label); ?></strong>:</td>
                        <td>
                                <select name="LANGUAGE" style="width:175px;filter:alpha(opacity=75);moz-opacity:0.75">
<?
        $query = "SELECT code, name FROM " . $table_prefix . "languages_domain Where Id_domain = " . $domain_id . " Order By name";
         
        $rows = $SQL->selectall($query);
        
        foreach ($rows as $key => $row) {
?>
                                        <option value="<?=strtolower($row["code"])?>"<?= ($row["code"] == $language ? " selected" : "")?>><?=$row["name"]?>
<?
        }
?>
                                </select>
                        </td>
                </tr>
<? } ?>
       
             
<?

if ($disable_department == true && $department == '' && $installed == true || $error == 'empty')  {
?>
                <tr>
                        <td><strong><?php echo($department_label); ?></strong>:</td>
                        <td>
                                <select name="DEPARTMENT" style="width:175px;filter:alpha(opacity=75);moz-opacity:0.75">
<?
$query = "SELECT DISTINCT u.department FROM " . $table_prefix . "users u, " . $table_prefix . "domain_user du WHERE (UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(u.refresh)) < '$connection_timeout' AND u.status = '1' And u.id = du.id_user And du.id_domain = " . $domain_id;
$rows = $SQL->selectall($query);

if (is_array($rows)) {
        $distinct_departments = array();
        foreach ($rows as $key => $row) {
                if (is_array($row)) {
                        $department = $row['department'];
                        $departments = split ('[;]',  $row['department']);
                        if (is_array($departments)) {
                                foreach ($departments as $key => $department) {
                                        $department = trim($department);
                                        if (!in_array($department, $distinct_departments)) {
                                                $distinct_departments[] = $department;
?>
                                        <option value="<?php echo($department); ?>"><?php echo($department); ?></option>
<?
                                        }
                                }
                        } else {
                                $department = trim($department);
                                if (!in_array($department, $distinct_departments)) {
                                        $distinct_departments[] = $department;
?>
                                        <option value="<?php echo($department); ?>"><?php echo($department); ?></option>
<?
                                }
                        }
                }
        }
}
       
?>
                                </select>
                        </td>
                </tr>
<?

} else if (($departments == true) || ($department != '')) {
?>
 <input name="DEPARTMENT" type="hidden" value="<?php echo($department); ?>">
<?
}
?>

<?
if ($_REQUEST['COOKIE'] != '') {
        $cookie_domain = $_REQUEST['COOKIE'];
?>
                                <input name="COOKIE" type="hidden" value="<?php echo($cookie_domain); ?>">
<? 
}
    
?>
<tr>
<td><strong></strong></td>
<td>
<p align="center"> <input name="Submit" type="button" id="Submit" value="<?php echo($continue_label); ?>" onClick="return chForm()">  </p>
</td>
</tr>

<?php

 $copyright = 1;
 $query = "SELECT value FROM " . $table_prefix . "settings WHERE name = 'disable_copyright' and id_domain = $domain_id";
 $row = $SQL->selectquery($query);
 if (is_array($row)) {
 $copyright = $row['value'];
 }

  if ($copyright ==1) {
    
 $query = "SELECT value FROM " . $table_prefix . "settings WHERE name = 'company_logo' and id_domain = $domain_id";
 $row = $SQL->selectquery($query);
 if (is_array($row)) {
 $logo = $row['value'];
 }
   
 $query = "SELECT value FROM " . $table_prefix . "settings WHERE name = 'company_link' and id_domain = $domain_id";
 $row = $SQL->selectquery($query);
 if (is_array($row)) {
 $company_link = $row['value'];
 } 

 $query = "SELECT value FROM " . $table_prefix . "settings WHERE name = 'company_slogan' and id_domain = $domain_id";
 $row = $SQL->selectquery($query);
 if (is_array($row)) {
 $company_slogan = $row['value'];
 }
 
 $query = "SELECT value FROM " . $table_prefix . "settings WHERE name = 'copyright_image' and id_domain = $domain_id";
 $row = $SQL->selectquery($query);
 if (is_array($row)) {
 $banner_enable = $row['value'];
 }
 
 $livehelp_logo_path = $install_directory . '/domains/' . $domain_id . '/i18n/en/pictures/';  
    ?>
       <tr>
        <td colspan="2" align="left" valign="top">
        <?php
          if ($banner_enable ==1) { 
          ?>  
         <a href=" <?php echo($company_link); ?> " target="_blank"><img src="<?php echo($livehelp_logo_path . $logo); ?> " border="0" ></a>
         <?php
           } else {
          ?>      
          <span class="small"><a href=" <?php echo($company_link); ?> " target="_blank" class="normlink"><?php echo($company_slogan); ?></span>  
           <?php
           } 
          ?>    
        </td>
      </tr>
<?php
 }
  ?>
</table>
</form>
</body>
</html>


