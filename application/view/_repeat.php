<?php
  session_start();
  require_once( "../core/settings.php" );
  $model = setupModel();
  if( !$model->hasinterface ) exit;
  // This should happen in getForm() if( isset( $_POST["id"] ) ) $model->get( $_POST["id"] );
  if( !empty( $_GET["fields"] ) ){
    $model->aSearchFields = array();
    $model->aResultsFields = array();
    $a = preg_split( "/,/", $_GET["fields"] ); // OK
    foreach( $a as $f ){
      if( array_key_exists( $f, $model->aFields ) ){
        if( !$model->aFields[$f]->display ) continue;
        $model->aSearchFields[] = $f;
        $model->aResultsFields[] = $f;
      }
    }
    $model->getForm( $model->aSearchFields );
  }else{
    $model->getForm();
    $model->setupUserFields();
    $model->doInits();
    $model->doCalculations();
  }
  $model->setAction( $_POST["action"] );
  $return_url = "Location: ".SITE_ROOT.strip_tags( $_POST["context"] )."/edit/".strip_tags( $_POST["context_id"] )."#hdr".$model->name;
  if( $model->action != "get" && !$model->validate() ){
    
    // Put errors in flash
    $flash = array(
      "positive" => false,
      "errors" => $model->aErrors,
      "notice" => "There was a problem submitting your form:"
    );
  
    // Where to send back to
    if( !empty( $_POST["id"] ) ){
      $id = strip_tags( $_POST["id"] );
      $return_url .= "/$id";
    }
    if( !isset( $_POST["ajax_form"] ) ){ 
      Flash::setNotice($flash["notice"]);
      foreach($flash["errors"] as $error){ Flash::addError($error["message"],$error["fieldname"]); }
      header( $return_url );
    }else{
      echo json_encode( array( "flash" => $flash ) );
    }
    exit;
  }
  switch( $model->action ){
    case "get":
      break;
      
    case "delete":
      $model->delete();
      break;
      
    default:
      $model->save();
      break;
  }
  if( isset( $_POST["ajax_form"] ) ){ 
    // $model->get();
    $model->retrieve( $model->id, true );
    $model->doInits();
    $model->doCalculations();
    echo $model->toJson();
    Flash::clear();
  }else{
    header( $return_url );
  }
?>
