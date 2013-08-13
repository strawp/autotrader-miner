<?php
require_once( "../core/settings.php" );
require_once( "models/user.model.class.php" );
require_once( "models/user_group.model.class.php" );
require_once( "models/user_user_group.model.class.php" );
require_once( "core/flash.class.php" );
require_once( "../lib/ext/class.phpmailer.php" );

if (!SessionUser::isAdmin()){
  $return_url = "Location: /";
  header( $return_url );
  exit;
}

if( !array_key_exists( "sessidhash", $_POST ) ||  $_POST["sessidhash"] != SessionUser::getProperty("sessidhash") ) die( "The page you were on has expired" );

/* add recipients to list */
if (isset($_POST)){
  /* init */

  $db = new DB();
  /* fetch form variables */

  if (isset($_POST['addRcpBtn'])){
    foreach((array)$_POST['lstUserId'] as $id){
      $id = (int)$id;
      if ($id!=0 && !in_array($id,(array)$_SESSION["mods"]["mailer"]["user"])){
        $_SESSION["mods"]["mailer"]["user"][]=$id;
      }
    }
  }
  if (isset($_POST['addGrpBtn'])){
    foreach((array)$_POST['lstUserGroupId'] as $id){
      $id = (int)$id;
      if ($id!=0 && !in_array($id,(array)$_SESSION["mods"]["mailer"]["group"])){
        $_SESSION["mods"]["mailer"]["group"][]=$id;
      }
    }
  }

  if (isset($_POST['delRcpBtn'])){
    foreach((array)$_POST['lstSelectedUsers'] as $id){
      $id = (int)$id;
      unset($_SESSION["mods"]["mailer"]["user"][array_search($id,$_SESSION["mods"]["mailer"]["user"])]);
    }
  }

  if (isset($_POST['delGrpBtn'])){
    foreach((array)$_POST['lstSelectedGroups'] as $id){
      $id = (int)$id;
      unset($_SESSION["mods"]["mailer"]["group"][array_search($id,$_SESSION["mods"]["mailer"]["group"])]);
    }
  }

  if (isset($_POST['resetBtn'])){
    unset($_SESSION["mods"]["mailer"]);
  }

  /* search for date users*/
  if (isset($_POST['srchRcpBtn'])){
    $dteStartDate = strtotime($_POST["dteStartDate"]." 00:00");
    $dteEndDate = strtotime($_POST["dteEndDate"]." 23:59");
    $db->query("select id from user where last_logged_in between $dteStartDate and $dteEndDate");
    while( $row = $db->fetchRow() ){
      if (!in_array($row['id'],(array)$_SESSION["mods"]["mailer"]["user"])){
        $_SESSION["mods"]["mailer"]["user"][]=$row['id'];
      }
    }
  }


  /* send mail */
  if (isset($_POST['sendMailBtn'])){
    $bcc = array();

    foreach((array)$_SESSION["mods"]["mailer"]["user"] as $id){
      $user = new User();
      $user->get($id);
      if($user->getField("has_left")->value != "1") $bcc[] = $user->getField("name")->value."@".SITE_EMAILDOMAIN;
    }

    /* looping through selected groups and adding users to the dst list */
    foreach((array)$_SESSION["mods"]["mailer"]["group"] as $id){
      $db->query("SELECT * from user_user_group WHERE user_group_id=".$db->escape($id));
      while( $row = $db->fetchRow() ){
        if ($row['user_id'] !== null) {
          $user = new User();
          $user->get($row['user_id']);
          $to = $user->getField("name")->value."@".SITE_EMAILDOMAIN;
          /* don't add the user twice and don't add if the user has left the university (disabled account) */
          if(!in_array($to,$bcc) && $user->getField("has_left")->value != "1") $bcc[]=$to;
        }
      }
    }

    if (count($bcc)>0){
      $mail = new PHPMailer();
      $mail->IsMail();
      $mail->From = SITE_ADMINEMAIL;
      $mail->FromName = sprintf("%s %s (".SITE_NAME.")",SessionUser::getProperty("firstname"),SessionUser::getProperty("lastname"));
      foreach($bcc as $one){
        $mail->AddBCC($one);
      }
      $mail->IsHTML(false);                                  

      $mail->Subject = stripslashes($_POST["emailSubject"]);
      $mail->Body    = stripslashes($_POST["emailText"]);

      if($mail->Send()){
        Flash::addOk(sprintf("The mail has been sent to %d recipient(s).\n",count($bcc))); 
      }
      else {
        Flash::addError("There was an error while sending the email.\n".$mail->ErrorInfo );
      }
    }
    else {
      Flash::addError("There were no recipients selected. The email has not been sent.");
    }   
  }  
}

$return_url = "Location: ".SITE_ROOT."mailer/";
header( $return_url );
