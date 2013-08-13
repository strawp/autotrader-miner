<?php
  session_start();
  require_once( "../core/settings.php" );
  $model = setupModel();
  if( !$model->hasinterface ) exit;
  $aResults = $model->getBySearch();
  
  echo "{ results: [";
  $comma = "";
  foreach( $aResults as $result ){
  	echo "\n".$comma."  { id: \"".$result["id"]."\", value: \"".$result["name"]."\", info: \"\" }";
    $comma = ",";
  }
  echo "] }\n";
  
?>