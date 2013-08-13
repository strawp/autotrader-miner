<?php
  session_start();
  require_once( "../core/settings.php" );
  if( !SessionUser::isLoggedIn() ) exit;
 
  $eq = new ExportQueue();
  $url = "report/".str_replace( SITE_ROOT."report/_sendnow/", "", $_SERVER["REQUEST_URI"] );
  $url = preg_replace( "/[^A-Za-z0-9 -_\/]/", "", $url );
  $eq->Fields->Url = $url;
  // $eq->Fields->Name = "Report for ".SessionUser::getFullName();
  $eq->save();
  Flash::setNotice("You will receive an email with this report attached shortly.");
  header( "Location: ".SITE_BASE.$url );
  exit;
  
?>