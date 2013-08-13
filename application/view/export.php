<?php
  // Writes a CSV export of all data
  session_start();
  require_once( "../core/settings.php" );
  require_once( "core/functions.php" );
  require_once( "core/db.class.php" );

  require_once( "../lib/mailer.class.php" );
  globalAuth();
  $model = setupModel();
  if( !$model->hasinterface ) exit;
  if( !$model->isAuth() ){  
    exit;
  }
  ini_set( "memory_limit", "128M" );
  
  $debug = false;
  
  $send_mail = false;
  
  if( SITE_ENABLEEXPORTQUEUE ){
    $model->setupUserFields();
    // Just add the search to the export queue
    $eq = new ExportQueue();
    $url = preg_replace( "/_export\/?/", "", $_SERVER["REQUEST_URI"] );
    $eq->Fields->Url = $url;
    $eq->Fields->Name = $model->displayname." data export for ".SessionUser::getFullName();
    $eq->Fields->Columns = join(",",$model->aResultsFields);
    $eq->save();
    Flash::setNotice("You will receive an email with an Excel file attached shortly. If you have requested a large amount of data, this may take several minutes.");
    header( "Location: ".SITE_BASE.$model->tablename.constructSearchArgs()."/" );
    exit;
  }else{
  
    // Init the model
    $model->doInits();
    // $tbl = $model->getSearchResultsAsTable();
    $tmpfile = $model->writeSearchResultsToTempHtmlFile();
    
    
    $extension = "xls";
    if( $send_mail ){
      $mail = new Mailer();
      $mail->setSubject( "Excel export of ".$model->displayname." search" );
      $mail->addCurrentUserAsRecipient();
      $body = "At ".date( "H:i \o\\n \\t\h\e jS \o\\f M Y" )." you requested this excel export (attached) of a search in ".SITE_NAME;
      $mail->wrapBody( $body );
      // $mail->AddStringAttachment( $tbl->getHtml(), $model->tablename."_".date( "Y-m-d_His" ).".".$extension, "base64", "text/csv" );
      $mail->AddAttachment( $tmpfile, $model->tablename."_".date( "Y-m-d_His" ).".".$extension, "base64", "text/csv" );
      if( $mail->Send() ){
        Flash::setNotice("You will receive an email with your Excel export file attached shortly.");
      }else{
        Flash::addError(
          "There was a problem emailing you the data you requested. ".
          "<blockquote>".$mail->ErrorInfo."</blockquote>".
          "The recommended work around is to use a browser other than Internet Explorer or to have a colleague download the file for you."
        );
        Flash::setHtmlAllowed();
      }
      header( "Location: ".SITE_PROTOCOL."://".$_SERVER["SERVER_NAME"].SITE_ROOT.$model->tablename.constructSearchArgs() );
      exit;
    }
    if( !$debug ){
      header( "Content-type: text/csv" );
      header( "Content-disposition: Attachment; filename=".$model->tablename."_".date( "Y-m-d_His" ).".".$extension );
      header( "Pragma: " ); // For IE
    }
    // echo $str;
    // echo $tbl->getHtml();
    readfile( $tmpfile );
    unlink( $tmpfile );
  }
?>
