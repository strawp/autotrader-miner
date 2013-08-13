<?php
  
  session_start();
  require_once( "core/functions.php" );
  globalAuth();
  $model = setupModel();
  $model->setAction( $_GET["action"] );
  if( !$model->hasinterface ){
    header( "Location: ".SITE_ROOT );
    exit;
  }
  
  if( !empty( $_GET["id"] ) && ( $model->action == "edit" || $model->action == "mail" || $model->action == "wizard" ) ){
  
    // Do this to stop calculations being run here and then again after loadCurrentModel
    $aCalculations = $model->calculations;
    $model->calculations = array();
    $model->retrieve( $_GET["id"], true );
    if( $model->id == 0 && $model->name != "MemberInterface" ){
      Flash::addError( "The ".$model->name." you tried to access no longer exists" );
      header( "Location: ".SITE_ROOT.$model->returnpage );
      exit;
    }
  }
  $header = true;
  if( !empty( $_GET["options"] ) && $_GET["options"] == "_ajax" ) $header = false;
  
  $model = loadCurrentModel( $model );
  if( isset( $aCalculations ) ) $model->calculations = $aCalculations;
  $model->doInits();
  $model->doCalculations();
  if( method_exists( $model, "setupCustomJs" ) ) $model->setupCustomJs();
  switch( $model->action ){
    case "mail":
      $page_js = SITE_ROOT."js/mailform.js";
      break;
      
    case "wizard":
      $page_css = SITE_ROOT."css/wizard.css";
      break;
  }
  if( $header !== false ){ 
    require_once( "core/header.php" );
  }
  $editable = strstr( $model->getAuth(), "u" ) !== false;
  if( !$editable ){
    foreach( $model->aFields as $key => $field ){
      $model->aFields[$key]->editable = $editable;
    }
  }
  switch( $model->action ){
    case "edit":
      echo "      <h2>Edit ".$model->displayname;
      if( $model->name != "MemberInterface" ) echo ": ".htmlentities( $model->getName() );
      echo "</h2>\n";
      if( $model->description != "" ) echo "    <p>".$model->description."</p>\n";
      break;
    case "new":
      $model->setFieldsFromSearchArgs();
      $model->doCalculations();
      echo "    <h2>Add a new ".$model->displayname."</h2>\n";
      if( $model->description != "" ) echo "    <p>".$model->description."</p>\n";
      break;
    
    case "remove":
      echo "    <h2>Remove ".$model->displayname."</h2>\n";
      break;
      
  }
  
  switch( $model->name ){
      
    default:
      switch( $model->action ){
      
        case "wizard":
          $step = isset( $_GET["step"] ) ? $_GET["step"] : 1;
          echo $model->renderWizard($step);
          break;
      
        case "mail":
        
          // Mail form
          echo $model->renderMailForm();
          break;
          
        default:
          echo $model->renderForm();
          break;
      }
  }

  if( $header !== false ){ 
    require_once( "core/footer.php" );
  }
?>