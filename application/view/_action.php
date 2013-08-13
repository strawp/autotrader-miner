<?php
  session_start();
  require_once( "../core/settings.php" );
  require_once( "core/session_user.class.php" );
  $model = setupModel();
  $model->setAction( $_POST["action"] );
  if( !$model->hasinterface ){
    exit;
  }
  if( $model->action == "mail" ){
    $model->retrieve( $_POST["id"], true );
  }
  if( method_exists( $model, "memberRetrieve" ) ){
    $model->memberRetrieve( $_POST );
  }
  $aFields = array();
  if( $model->action == "wizard" ){
    $wiz = $model->getWizard();
    $wiz->setCurrentStep( $_POST["step"] );
    $aFields = array_keys( $wiz->CurrentStep()->aFields );
  }
  $model->doInits();
  @$model->getForm($aFields);
  $model->doCalculations();
  
  if( $model->action != "mail" && $model->action != "field_select" && !$model->validate() ){
    
    // Put errors in flash
    $flash = array(
      "positive" => false,
      "errors" => $model->aErrors,
      "notice" => "There was a problem submitting your form:"
    );
  
    // Where to send back to
    $return_url = "Location: ".SITE_ROOT.$model->tablename."/".strip_tags( $model->action );
    if( !empty( $_POST["id"] ) ){
      $id = strip_tags( $_POST["id"] );
      $return_url .= "/$id";
    }
    $aVal = array();
    foreach( $model->aFields as $field ){
      $aVal[$field->columnname] = $field->value;
    }
    $aVal["id"] = $model->id;
    $_SESSION["currentmodel"] = serialize( $aVal );
    
    if( isset( $_POST["ajax_form"] ) ){
      
      $obj = array( "flash" => $flash );
      echo json_encode( $obj );
      
    }else{
      Flash::setNotice($flash["notice"]);
      foreach($flash["errors"] as $error){ 
        if( empty( $error["fieldname"]) ) $error["fieldname"] = "";
        Flash::addError($error["message"],$error["fieldname"]); 
      }
      header( $return_url );
    }
    exit;
  }
  if( isset( $_SESSION["currentmodel"] ) ) unset( $_SESSION["currentmodel"] );
  
  // CSRF mitigation for mail and field_select forms
  switch( $model->action ){
    case "field_select":
    case "mail":
      if( !SessionUser::isLoggedIn() || SessionUser::getProperty("sessidhash") != $model->capturedsessidhash ){
        header( "Location: ".SITE_ROOT.$model->returnpage."/" );
        exit;
      }
      break;
  }
  
  switch( $model->action ){
  
    case "field_select";
      
      // Valid user?
      if( !SessionUser::isLoggedIn() ){
        header( "Location: ".SITE_ROOT.$model->returnpage."/" );
        exit;
      }
      $model->setUserFields();
      $model->saveUserFields();      
      $return_url = "Location: ".SITE_ROOT.$model->returnpage;
      if( $_POST["args"] == "" ) $return_url .= "/";
      else $return_url .= urldecode( $_POST["args"] );
      break;
    
    case "mail":
      $body = isset( $_POST["txtBody"] ) ? $_POST["txtBody"] : "";
      $cc = isset( $_POST["cnfCc"] ) ?  SessionUser::getProperty("mail") : "";
      $other = isset( $_POST["cnfOtherAddresses"] ) ? $_POST["cnfOtherAddresses"] : array();
      $occ = isset( $_POST["emaOcc"] ) ? $_POST["emaOcc"] : "";
      $to = isset( $_POST["cnfTo"] ) ? $_POST["cnfTo"] : array();
      $attachments = isset( $_POST["attachments"] ) ? $_POST["attachments"] : "";
      // $logit = isset( $_POST["cnfLogit"] ) ? $_POST["cnfLogit"] : "";
      $logit = true;  // Log all comments
      $subject = isset( $_POST["strSubject"] ) ? $_POST["strSubject"] : "Re: \"".$model->getName()."\" on ".SITE_NAME;
      
      if( $model->emailOwner( $to, $cc, $occ, $body, $subject, $logit, $other, $attachments ) ){
        $return_url = "Location: ".SITE_ROOT.$model->returnpage."/edit/".$model->id;
      }
      else{
        $return_url = "Location: ".SITE_ROOT.$model->returnpage."/mail/".$model->id;
      }
      break;
      
    case "delete":
      $model->delete();
      $return_url = "Location: ".SITE_ROOT.$model->returnpage."/";
      break;
      
    case "wizard":
      $nextstep = $wiz->getCurrentStep();
      switch( $_POST["btnSubmit"] ){
        case "previous":
          if( $wiz->getCurrentStep() > 1 ) $nextstep = $wiz->getCurrentStep() - 1;
          if( $wiz->CurrentStep()->editable ){
            $model->doUploadFunctions();
            $model->save();
          }
          break;
          
        case "next":
          if( $wiz->getCurrentStep() < $wiz->getTotalSteps() ) $nextstep = $wiz->getCurrentStep() + 1;
          if( $wiz->CurrentStep()->editable ){
            $model->doUploadFunctions();
            $model->save();
          }
          break;
          
        case "skip":
          if( $wiz->CurrentStep()->skipable && $wiz->getCurrentStep() < $wiz->getTotalSteps() ) $nextstep = $wiz->getCurrentStep() + 1;
          break;
          
        case "save":
          $nextstep = $wiz->getCurrentStep();
          if( $wiz->CurrentStep()->editable ){
            $model->doUploadFunctions();
            $model->save();
          }
          break;
      }
      $return_url = "Location: ".SITE_ROOT.$model->tablename."/wizard/".$model->id."/step/".intval( $nextstep );
      if( $_POST["btnSubmit"] == "finish" ){
        $return_url = "Location: ".SITE_ROOT.$model->tablename."/edit/".$model->id;
      }
      break;
      
    default:
      $model->doUploadFunctions();
      $model->save();
      $model->alignAttachments();
      $return_url = "Location: ".SITE_ROOT.$model->tablename."/edit/".$model->id;
      break;
  }
  if( isset( $_POST["ajax_form"] ) ){
    echo json_encode( array( 
      "flash" => $_SESSION["flash"],
      "action" => $model->action,
      "id" => $model->id,
      "tablename" => $model->tablename
    ) );
    Flash::clear();
  }else{
    header( $return_url );
  }
?>
