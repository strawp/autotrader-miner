<?php
  session_start();
  globalAuth();
  require_once( "../core/settings.php" );
  $model = setupModel();  
  if( isset( $_POST["id"] ) ) $model->get( $_POST["id"] );
  if( !($model instanceof Model) ) exit;
  if( !$model->hasinterface ) exit;
  $model->doInits();
  $model->getForm();
  
  $aReturn = array();
  foreach( $model->aFields as $field ){
    if( sizeof( $field->aUsesFields ) > 0 ){ 
      $aReturn = array_merge( $aReturn, $field->aUsesFields );
    }
  }
  
  // Remove duplications
  $aFields = array();
  $aReturn = array_unique( $aReturn );
  
  // If arrays aren't ordered nicely they will get turned into objects when serialised and then the JS won't work
  foreach( $aReturn as $f ){
    $aFields[] = $f;
  }
  $aReturn = array(
    "fields" => $aFields,
    "model" => $model->tablename
  );

  // $model->doCalculations();
  Flash::clear();
  echo json_encode( $aReturn );
?>