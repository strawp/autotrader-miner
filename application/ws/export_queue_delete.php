<?php
  require_once( "../core/settings.php" );
  if( ExportQueue::deleteWS( $_GET ) ){
    echo "OK";
  }else{
    echo "FAIL";
  }
?>