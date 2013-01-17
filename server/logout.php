<?php
include_once('import/constants.php');

include('./import/config_database.php');
include('./import/class.mysql.php');
include('./import/config.php');


ignore_user_abort(true);

if (isset($_REQUEST['DOMAINID'])){
  $domainId = (int) $_REQUEST['DOMAINID'];
}

$_REQUEST['RATING'] = !isset( $_REQUEST['RATING'] ) ? '' : (string) $_REQUEST['RATING'];
$_REQUEST['COMPLETE'] = !isset( $_REQUEST['COMPLETE'] ) ? '' : (string) $_REQUEST['COMPLETE'];
$_REQUEST['SEND_SESSION'] = !isset( $_REQUEST['SEND_SESSION'] ) ? '' : (string) $_REQUEST['SEND_SESSION'];
$_REQUEST['EMAIL'] = !isset( $_REQUEST['EMAIL'] ) ? '' : htmlspecialchars( (string) $_REQUEST['EMAIL'], ENT_QUOTES );

//Declaration variables
$complete = $_REQUEST['COMPLETE'];
$rating = $_REQUEST['RATING'];
$send_session = stripslashes($_REQUEST['SEND_SESSION']);
$email = $_REQUEST['EMAIL'];


// visitor default email

  $query = "SELECT `email` FROM " . $table_prefix . "sessions  WHERE `id` = '$guest_login_id'";
  $row = $SQL->selectquery($query);
  if (is_array($row)) {
     $visitor_email = $row['email'];
     }


  $query = "UPDATE " . $table_prefix . "sessions SET active = -1 WHERE `id` = '$guest_login_id'";
  $SQL->miscquery($query);

if ($rating != '') {

        $query = "UPDATE " . $table_prefix . "sessions SET `rating` = '$rating', active = -1 WHERE `id` = '$guest_login_id'";
        $SQL->miscquery($query);

        // chat session
  if ($send_session == true) {       
        $query = "Select `username` , `message` , TIME_FORMAT(datetime,'%l:%i:%s') `time` " .  ' from ' . $table_prefix . 'messages' . " where session =  '$guest_login_id'". ' order by id ';            
        $rows = $SQL->selectall($query);
                               
         
    if (is_array($rows)) {
        foreach ($rows as $key => $row) {
                if (is_array($row)) {

                  $msg .= '(' .$row['time']. ')' . ' ' . $row['username'] . ' : ' . $row['message']  ."\n";                                                                    
                
                   } 
                  } 
                 }  
                                       
        // from email                               
        $query = "SELECT value FROM " . $table_prefix . "settings where name = 'from_email' and id_domain = $domainId";
                $row = $SQL->selectquery($query);
                if (is_array($row)) {
                        $from_email = $row['value'];
                }
        
       // from name         
        $query = "SELECT value FROM " . $table_prefix . "settings where name = 'site_name' and id_domain = $domainId";
                $row = $SQL->selectquery($query);
                if (is_array($row)) {
                        $from_name = $row['value'];
                }
                
                     
        
        
        
        //$subject = str_ireplace("www.", "", $from_name) .' Chat Transcript (' . $guest_login_id . ' )';                        
        //mail($email, $subject, $msg, $headers);
        
     }   
        
       header('Location: ./logout.php?COMPLETE=1&LANGUAGE='.$_REQUEST['LANGUAGE'].'&DOMAINID='.$domainId.'&URL='. urlencode( $_REQUEST['URL'] ) );

}

else {
        $query = "SELECT `request`, `active` FROM " . $table_prefix . "sessions WHERE `id` = '$guest_login_id'";
        $row = $SQL->selectquery($query);
        if (is_array($row)) {
                $operator_login_id = $row['active'];

                $request_id = $row['request'];

                $query = "UPDATE " . $table_prefix . "requests SET active = '-1' WHERE `id` = '$request_id'";
                $SQL->miscquery($query);

                if ($operator_login_id != '-1' || $operator_login_id != '-3') {
                        $query = "UPDATE " . $table_prefix . "sessions SET `active` = '-1' WHERE `id` = '$guest_login_id'";
                        $SQL->miscquery($query);
                        $query = "UPDATE " . $table_prefix . "requests SET `initiate` = '0' WHERE `id` = '$request_id'";
                        $SQL->miscquery($query);
                }
        }
}

include('import/settings_default.php');

header('Content-type: text/html; charset=' . CHARSET);

$language_file = './i18n/' . LANGUAGE_TYPE . '/lang_guest_' . LANGUAGE_TYPE . '.php';
if (file_exists($language_file)) {
        include($language_file);
}
else {
        include('./i18n/en/lang_guest_en.php');
}

// Send transcription

if ($send_session == true) {  
    
 $headers = "From: " . str_ireplace("www.", "", $from_name). " <" . $from_email . ">\n";   
 $subject = str_ireplace("www.", "", $from_name). " " . $chat_transcript_label . ' (' . $guest_login_id . ' )'; 
 mail($email, $subject, $msg, $headers);
 
 }
 
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title><?php echo($livehelp_name); ?></title>
<link href="<?=$install_directory?>/style/styles.php?<?echo('DOMAINID='.$domainId);?>" rel="stylesheet" type="text/css">
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
<div align="center">
  <iframe name="printFrame" id="printFrame" src="blank.php?<?echo('DOMAINID='.$domainId);?>" frameborder="0" border="0" width="0" height="0" style="visibility: hidden"></iframe>
  <table border="0" align="right" cellpadding="0" cellspacing="0">
    <tr>
      <td>
        <!--
          <a href="#" onClick="parent.close();" class="normlink"><?php echo($close_window_label); ?></a>
        -->
      </td>
    </tr>
  </table>
                
  <p align="left" style="width: 90%;"><b><?php echo($logout_message_label); ?></b></p>
                           
<?
if ($complete != '') {
?>                   
     <p align="left" style="width: 90%;"><strong><?php echo($rating_thank_you_label); ?></strong></p>
<?
}
else {
?>
        
  <form name="rateSession" method="post" action="logout.php?client_domain_id=<?php echo($domain_id);?><?echo('&DOMAINID='.$domainId);?>&URL=<?php echo urlencode($_REQUEST['URL']); ?>">
    <table border="0" cellspacing="0" cellpadding="0" width="90%" align="center">    
     
         
       <tr>
            <td colspan="2" align="left">
               <p><?php echo($please_rate_service_label); ?>:</p>
           </td>
      </tr>
      <tr>
        <td align="left" width="0"><br><b><?php echo($rate_service_label); ?></b>: <select name="RATING" id="RATING">

            <?php
              echo("<option value='5'>".$excellent_label."</option>");
              echo("<option value='4'>".$very_good_label."</option>");
              echo("<option value='3'>".$good_label."</option>");
              echo("<option value='2'>".$fair_label."</option>");
              echo("<option value='1'>".$poor_label."</option>");
           ?>
          </select>
                         <input type="submit" name="Submit" value="<?php echo($rate_label); ?>">
                  </td>
      </tr>
            
      <tr>
        <td colspan="2" align="left"><br><b><strong><?php echo($your_email_label); ?></strong>:
         <input name="EMAIL"  type="text" id="EMAIL" value="<?php echo($visitor_email); ?>" size="40" style="width:240px;">
        </td>
      </tr>          
      <tr>      
        <td align="left">
          <input name="SEND_SESSION" type="checkbox" value="1">
          <?php echo($send_copy_session); ?>
      </td>
      </tr>            
      
                <tr>
                        <td>
                                <br>
                          <p><?php echo($further_assistance_label); ?></p>
                          </p>


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
         <input type="Hidden" name="LANGUAGE" value="<?=LANGUAGE_TYPE?>">
  </form>
  </p>
  <?php
}
?>
</div>
</body>
</html>