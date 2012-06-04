<?php
include('import/constants.php');

if (isset($_REQUEST['DOMAINID'])){ $domain_id = (int) $_REQUEST['DOMAINID']; }

header('Content-type: text/html; charset=utf-8');

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
$database = include('import/config_database.php');
if ($database) {
        include('import/block_spiders.php');
        include('import/class.mysql.php');
        include('import/config.php');
} else {
        $installed = false;
}

if ($installed == false) {
        include('import/settings_default.php');
}

$_REQUEST['SERVER'] = !isset( $_REQUEST['SERVER'] ) ? '' : htmlspecialchars( (string) $_REQUEST['SERVER'],ENT_QUOTES );
$_REQUEST['URL'] = !isset( $_REQUEST['URL'] ) ? '' : (string) $_REQUEST['URL'];
$_REQUEST['TITLE'] = !isset( $_REQUEST['TITLE'] ) ? '' : htmlspecialchars( (string) $_REQUEST['TITLE'], ENT_QUOTES );
$_REQUEST['COMPLETE'] = !isset( $_REQUEST['COMPLETE'] ) ? '' : (string) $_REQUEST['COMPLETE'];
$_REQUEST['SECURITY'] = !isset( $_REQUEST['SECURITY'] ) ? '' : (string) $_REQUEST['SECURITY'];
$_REQUEST['BCC'] = !isset( $_REQUEST['BCC'] ) ? '' : (string) $_REQUEST['BCC'];


$error = '';
$invalid_email = '';
$invalid_security = '';
$captcha =1;
$email = '';
$name = '';
$message = '';
$code = '';
$status = '';

        // captcha 
        $query = "SELECT value FROM " . $table_prefix . "settings WHERE name = 'captcha' And id_domain = $domain_id";
                                $row = $SQL->selectquery($query);
                                if (is_array($row)) {
                                        $captcha = $row['value'];
                                }


if($_REQUEST['COMPLETE'] == true) {
    

        $name = stripslashes($_REQUEST['NAME']);
        $email = stripslashes($_REQUEST['EMAIL']);
        $message = stripslashes($_REQUEST['MESSAGE']);
        $code = stripslashes($_REQUEST['SECURITY']);
        $bcc = stripslashes($_REQUEST['BCC']);

        

        if ($email == '' || $name == '' || $message == '') {
                $error = true;
        }
        else {

                $url = stripslashes($_REQUEST['URL']);
                $title = stripslashes($_REQUEST['TITLE']);

                if (!ereg('^[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+'.
                                          '@'.
                                          '[-!#$%&\'*+\\/0-9=?A-Z^_`a-z{|}~]+\.'.
                                          '[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+$', $email)) {
                                          $invalid_email = true;
                }
                else {

                        $md5code = md5(strtoupper($code));
                        if ($security != $md5code && ((function_exists('imagepng') || function_exists('imagejpeg')) && function_exists('imagettftext')) && ($captcha ==1) ) {
                                $invalid_security = true;

                                // Generate a NEW random string
                                $chars = array('a','A','b','B','c','C','d','D','e','E','f','F','g','G','h','H','i','I','j','J','k','K','l','L','m','M','n','N','o','O','p','P','q','Q','r','R','s','S','t','T','u','U','v','V','w','W','x','X','y','Y','z','Z','1','2','3','4','5','6','7','8','9');
                                $security = '';
                                for ($i = 0; $i < 5; $i++) {
                                   $security .= $chars[rand(0, count($chars)-1)];
                                }

                                $session = array();
                                $session['REQUEST'] = $request_id;
                                $session['DOMAINID'] = $domain_id;
                                $session['SECURITY'] = md5(strtoupper($security));
                                $session['LANGUAGE'] = LANGUAGE_TYPE;
                                $session['CHARSET'] = CHARSET;
                                $data = serialize($session);

                                setCookie($cookieName, $data, false, '/', $cookie_domain, $ssl);

                        }
                        else {
                                $current_page = 'Unavailable';
                                $title = 'Unavailable';
                                $referrer = 'Unavailable';

                                $query = "SELECT `url`, `title`, `referrer`, `id_domain` FROM " . $table_prefix . "requests WHERE `id` = '$request_id'";
                                $row = $SQL->selectquery($query);
                                if (is_array($row)) {
                                        $current_page = $row['url'];
                                        $title = $row['title'];
                                        $referrer = $row['referrer'];
                                        if ($current_page == '') { $current_page = 'Unavailable'; }
                                        if ($title == '') { $title = 'Unavailable'; }
                                        if ($referrer == '') { $referrer = 'Unavailable'; } elseif ($referrer == 'false') { $referrer = 'Direct Link / Bookmark'; }
                                }


                                if ($configure_smtp == true) {
                                        ini_set('SMTP', $smtp_server);
                                        ini_set('smtp_port', $smtp_port);
                                        ini_set('sendmail_from', $from_email);
                                }

                             
  
                                $from_name = "$name";
                                $from_email = "$email";
                                $query = "SELECT value FROM " . $table_prefix . "settings WHERE name = 'offline_email' And id_domain = $domain_id";
                                $row = $SQL->selectquery($query);
                                if (is_array($row)) {
                                        $offline_email = $row['value'];
                                }
                                $to_email = $offline_email;
                                $subject = "Livehelp Offline Message";
                                $headers = "From: " . $from_name . " <" . $from_email . ">\n";
                                $headers .= "Reply-To: " . $from_name . " <" . $from_email . ">\n";
                                $headers .= "Return-Path: " . $from_name . " <" . $from_email . ">\n";
                                $msg      = $message;
                                $message .= "\n\n--------------------------\n";
                                $message .= "IP Logged:  " . $_SERVER['REMOTE_ADDR'] . "\n";
                                if ($ip2country_installed == true) { $message .= "Country:  $country\n"; }
                                $message .= "URL:  $current_page\n";
                                $message .= "URL Title:  $title\n";
                                $message .= "Referrer:  $referrer\n";

                                $sendmail_path = ini_get('sendmail_path');
                                if ($sendmail_path == '') {
                                        $headers = str_replace("\n", "\r\n", $headers);
                                        $message = str_replace("\n", "\r\n", $message);
                                }
                                mail($to_email, $subject, $message, $headers);
                                
                                // save the offline email in the database
                                
                                $query = "SELECT value FROM " . $table_prefix . "settings WHERE name = 'log_offline_email' And id_domain = $domain_id";
                                $row = $SQL->selectquery($query);
                                if (is_array($row)) {
                                        $log_offline_email = $row['value'];
                                }
                                
                                if ($log_offline_email == 1) {
                                $query = "INSERT INTO " . $table_prefix . "offline_messages (`name`, `email`, `message`, `id_domain` , `datetime`) VALUES ('$name', '$email', '$msg', $domain_id, NOW())";
                                $SQL->insertquery($query);
                                 }
                                
                                // send email copy
                                
                                if ($bcc == true) {
                                        $to_email = "$email";
                                        $headers = "From: " . $from_name . " <" . $from_email . ">\n";
                                        $headers .= "Reply-To: " . $from_name . " <" . $from_email . ">\n";
                                        $headers .= "Return-Path: " . $from_name . " <" . $from_email . ">\n";
                                        $message = stripslashes($_REQUEST['MESSAGE']);

                                        if ($sendmail_path == '') { $headers = str_replace("\n", "\r\n", $headers); $message = str_replace("\n", "\r\n", $message); }
                                        mail($to_email, $subject, $message, $headers);
                                }
                        }
                }
        }

        $message = stripslashes($_REQUEST['MESSAGE']);

}

header('Content-type: text/html; charset=' . CHARSET);

$language_file = './i18n/' . LANGUAGE_TYPE . '/lang_guest_' . LANGUAGE_TYPE . '.php';

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
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo($livehelp_name); ?></title>
<link href="<?= $install_directory ?>/style/styles.php?<?echo('DOMAINID='.$domain_id);?>" rel="stylesheet" type="text/css">
<script>
window.resizeTo(470, 469);
</script>
<style type="text/css">
<!--
.background {
 /*       background-image: url(./i18n/<?=LANGUAGE_TYPE?>/pictures/background.gif);
   */     background-repeat: no-repeat;
        background-position: right top;
        margin-left: 0px;
        margin-top: 0px;
}
-->
</style>
</head>
<body bgcolor="<?php echo($background_color); ?>" text="<?php echo($font_color); ?>" link="<?php echo($font_link_color); ?>" vlink="<?php echo($font_link_color); ?>" alink="<?php echo($font_link_color); ?>" class="background">
 <!--img src="./i18n/<?=LANGUAGE_TYPE?>/pictures/background_offline.gif" alt="<?php echo($offline_message_label); ?>" width="309" height="49" style="position: relative; right: -150px; top: 10px;"-->

<?php

if($_REQUEST['COMPLETE'] == '' || $error != '' || $invalid_email != '' || $invalid_security != '') {
?>
<!--div align="center"-->
  <form action="offline.php" method="post" name="offline_message_form" id="offline_message_form">
    <table border="0" align="center" cellspacing="0" cellpadding="0" bordercolor="#111111" style="border:0px solid #CCCCCC;padding:7 20 7 20;" height="350px" width="420px">
     <tr>
     <td valign="bottom"><strong><?php  echo($unfortunately_offline_label); ?></strong>
     <br> <?php echo($fill_details_below_label); ?>: </td>
     </tr>
<?php
        if ($invalid_email != '' || $error == true) {
?>
      <tr>
        <td valign="bottom"><strong><?php echo($invalid_email_error_label); ?></strong></td>
      </tr>
<?php
        } else if ($invalid_security != '') {
?>
      <tr>
        <td valign="bottom"><strong><?php echo($invalid_security_error_label); ?></strong></td>
      </tr>
<?php
        }
?>
      <tr>
        <td align="left">
                    <strong><?php echo($your_name_label); ?></strong>:<br>
                    <input name="NAME" type="text" id="NAME" value="<?php echo($name); ?>" size="40" style="width:420px;">
                  </td>
      </tr>
      <tr>
        <td align="left">
                    <strong><?php echo($your_email_label); ?></strong>:<br>
                         <input name="EMAIL" type="text" id="EMAIL" value="<?php echo($email); ?>" size="40" style="width:420px;">
                  </td>
      </tr>
      <tr>
        <td align="left">
                    <strong><?php echo($message_label); ?></strong>:<br>
          <textarea name="MESSAGE" cols="30" rows="3" id="MESSAGE" style="width:420px; vertical-align: middle; font-family:<?php echo($chat_font_type); ?>; font-size:<?php echo($guest_chat_font_size); ?>;"><?php echo($message); ?></textarea>
        </td>
      </tr>
      
     
<?php
    if  ($captcha ==1) {
    
        if ((function_exists('imagepng') || function_exists('imagejpeg')) && function_exists('imagettftext')) {
//      if (true) {
?>
      <tr>
        <td align="left" valign="middle">
                    <strong><?php echo($security_code_label); ?></strong>:<br>
                         <span style="height: 32px; vertical-align: middle;"><input name="SECURITY" type="text" id="SECURITY" value="" size="6" style="width:100px;"></span>
                         <img src="security.php?URL=<?=urlencode($_REQUEST['URL'])?>">
             </td>
      </tr>
<?php
        } 
     }   
?>

      <tr>
        <td align="left">
          <input name="BCC" type="checkbox" value="1">
          <?php echo($send_copy_label); ?>
                  </td>
      </tr>
      <tr>
        <td colspan="2" align="left" valign="top">
            <input name="COMPLETE" type="hidden" id="COMPLETE" value="1">
            <input name="SERVER" type="hidden" id="SERVER" value="<?php echo htmlspecialchars($_REQUEST['SERVER'], ENT_QUOTES ); ?>">

            <table border="0" cellpadding="2" cellspacing="2">
              <tr>
                <td align="right">
                    <input type="Submit" name="Submit" value="<?php echo($send_msg_label); ?>">
                 </td>
                <td><input type="Button" name="Close" onClick="self.close();" value="<?php echo($close_window_label); ?>"></td>
              </tr>
            </table>

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

	 <input name="URL" type="hidden" id="URL" value="<?php echo htmlspecialchars($_REQUEST['URL'], ENT_QUOTES); ?>">
  </form>
<!--/div-->
<?php
} else {
?>
<div align="center">
  <?php echo($thank_you_enquiry_label); ?><br>
  <?php echo($contacted_soon_label); ?><br>
  <table border="0" align="center" cellspacing="0" bordercolor="#111111" style="border-collapse: collapse" width="90%">
<?php
        if ($status != '') {
?>
<?php
        }
?>
    <tr>
      <td width="260" valign="bottom"><strong><?php echo($your_email_label); ?></strong>:</td>
      <td><em><?php echo($email); ?></em></td>
    </tr>
    <tr>
      <td valign="bottom"><?php echo($your_name_label); ?>:</td>
      <td><em><?php echo($name); ?></em></td>
    </tr>
    <tr>
      <td valign="top"><?php echo($message_label); ?>:</td>
      <td align="right" valign="top"><div align="center">
          <textarea name="MESSAGE" cols="20" rows="6" id="MESSAGE" style="width:300px; font-family:<?php echo($chat_font_type); ?>; font-size:<?php echo($guest_chat_font_size); ?>;"><?php echo($message); ?></textarea>
        </div></td>
    </tr>
    <tr>
      <td colspan="2" align="right" valign="top"><div align="center">
          <table border="0" cellpadding="2" cellspacing="2">
            <tr>
              <td><input type="Button" name="Close" onClick="window.close();" value="<?php echo($close_window_label); ?>">
              </td>
            </tr>
          </table>      
    </tr>
  </table>
</div>
<?php
}
?>
</body>
</html>

