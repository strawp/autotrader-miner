<?php
  /**
  * Create a compiled PDF of any report
  */
  
  chdir( dirname( $_SERVER["PHP_SELF"] ) );
  require_once( "../core/settings.php" );
  
  // Command line in the form self.php option=value foo=bar etc=... ReportName
  $aOpts = array();
  
  for( $i=1; $i<sizeof( $argv ); $i++ ){
    if( !preg_match( "/([^=]+)=(.*$)/", $argv[$i], $m ) ) continue;
    $aOpts[$m[1]] = $m[2];
  }
  
  $reportname = $argv[sizeof( $argv )-1]."Report";
  echo "Creating new $reportname\n";
  $r = new $reportname();
  
  if( !$r ) die( "Couldn't create $reportname" );
  echo $r->title."\n";
  // $r->debug = true;

  if( isset( $aOpts["requesting_user"] ) ){
    $mail = new Mailer();
    $mail->AddRecipient( intval( $aOpts["requesting_user"] ) );
    $mail->SetSubject( $r->title." PDF creation started @ ".date( "Y-m-d H:i:s" ) );
    $mail->wrapBody( "You have received this email because you requested to compile the report \"".$r->title."\" "
      ."on ".SITE_NAME.".\n\n" 
      ."You will receive a further email when this has finished."
    );
    $mail->Send();
  } 

  $r->setOptions( $aOpts );
  
  if( sizeof( $r->aOptions ) > 0 ) echo "Options chosen:\n";
  foreach( $r->aOptions as $k => $v ){
    echo $k.": ".$v."\n";
  }
  
  echo "Compiling report...\n";
  $r->compile();
  
  echo "Compiled \"".$r->title."\"\n";
  if( $r->filename == "" ){
    $r->filename = preg_replace( "/[^-A-Za-z0-9\.]/", "_", $r->title ).".pdf";
  }
  
  $destinationfile = SITE_TEMPDIR.$r->filename;
  
  echo "Writing out PDF to ".$destinationfile."...\n";
  $r->writePDF( $destinationfile );
  
  // Notify requesting user by email
  if( isset( $aOpts["requesting_user"] ) ){
    $mail = new Mailer();
    $mail->AddRecipient( intval( $aOpts["requesting_user"] ) );
    $mail->SetSubject( $r->title." PDF creation ended @ ".date( "Y-m-d H:i:s" ) );
    $mail->wrapBody( "You have received this email because you requested to compile the report \"".$r->title."\" "
      ."on ".SITE_NAME." and it has now finished.\n\n" 
      ."You can download this file on the report page at ".SITE_BASE."report/".camelToUnderscore( $r->getName() )
    );
    $mail->Send();
  } 
      
  echo "Done\n";
?>