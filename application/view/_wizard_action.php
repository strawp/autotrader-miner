<?php
  /**
  * Receives a POST from the wizard form, handles it, redirects to next step in wizard
  */
  require_once( "../core/settings.php" );
  session_start();
  if( !isset( $_POST["model"] ) ) die( "No classname provided" );
  Cache::flushWizards();
  $wizname = underscoreToCamel( $_POST["model"] )."Wizard";
  try{
    $wiz = Cache::getWizard($wizname, intval( $_POST["id"] ) );
  }
  catch( Exception $ex ){
    die( $ex );
  }
  if( !array_key_exists( "sessidhash", $_POST ) || $_POST["sessidhash"] != SessionUser::getProperty("sessidhash") ){
    die( "The page you were previous on has expired. Please hit \"back\" and try again." );
  }
  $wiz->init( $_POST );
  $wiz->setCurrentStep( intval( $_POST["step"] ) );
  
  // Save
  $wiz->CurrentStep()->getForm();
  
  // Validation
  $valid = true;
  switch( $_POST["btnSubmit"] ){
    case "previous":
    case "next":
    case "save":
    case "finish":
      $valid = !$wiz->CurrentStep()->editable || $wiz->CurrentStep()->validate();
      break;
  }
  if( !$valid ){
    Flash::addErrors( $wiz->CurrentStep()->aErrors, "There was a problem with this step" );
    $url = $wiz->interfacebase."/step/".$wiz->getCurrentStep();
    header( "Location: ".$url );
    exit;
  }
  
  $nextstep = $wiz->getCurrentStep();
  switch( $_POST["btnSubmit"] ){
    case $wiz->CurrentStep()->previouslabel:
      if( $wiz->getCurrentStep() > 1 ) $nextstep = $wiz->getCurrentStep() - 1;
      if( $wiz->CurrentStep()->editable ){
        $wiz->CurrentStep()->save();
      }
      break;
      
    case $wiz->CurrentStep()->finishlabel:
    case $wiz->CurrentStep()->nextlabel:
      if( $wiz->getCurrentStep() < $wiz->getTotalSteps() ) $nextstep = $wiz->getCurrentStep() + 1;
      if( $wiz->CurrentStep()->editable ){
        $wiz->CurrentStep()->save();
      }
      break;
      
    case $wiz->CurrentStep()->skiplabel:
      if( $wiz->CurrentStep()->skipable && $wiz->getCurrentStep() < $wiz->getTotalSteps() ) $nextstep = $wiz->getCurrentStep() + 1;
      break;
      
    case $wiz->CurrentStep()->savelabel:
      $nextstep = $wiz->getCurrentStep();
      if( $wiz->CurrentStep()->editable ){
        $wiz->CurrentStep()->save();
      }
      break;
  }
  $wiz->CurrentStep()->complete = $wiz->CurrentStep()->isComplete();
  Cache::storeWizard($wiz);
  $interfacebase = $wiz->CurrentStep()->custominterfacebase != "" ? $wiz->CurrentStep()->custominterfacebase : $wiz->interfacebase;
  
  if( $_POST["btnSubmit"] == "finish" ){ 
    if( $wiz->showsummary ) $url = $interfacebase."/checklist/";
    else $url = $interfacebase."/step/".$wiz->getCurrentStep();
  }
  else $url = $interfacebase."/step/".$nextstep;
  header( "Location: ".$url );
?>