<?php

  /**
  * Send a report in PDF format to an email address
  */
  
  require_once( "../core/settings.php" );
  if( sizeof( $argv ) == 1 ){
    $help = "Send a report in PDF format to an email address\n\nUsage:\n".$argv[0]." <report class> <userid>\n"
      ."e.g. ".$argv[0]." Agenda dave\n";
    print_r( $argv );
    die( $help );
  }
  
  if( !isset( $argv[1] ) ) die( "Need report class name" );
  $report = $argv[1]."Report";
  try{
    $rpt = new $report();
  }
  catch(Exception $e){
    die( "Couldn't create instance of $report: ".$e->getMessage()."\n" );
  }
  
  if( !isset( $argv[2] ) ) die( "Need email address to send to" );
  $email = $argv[2];
  
  $rpt->aCssFiles = array();
  $rpt->compile();
  $rpt->sendPdfEmail( $email );
?>