<?php
session_start();
require_once( "../core/settings.php" );
require_once( "core/session_user.class.php" );
$model = setupModel();
if( !$model->hasinterface ) exit;
$model->getForm();
$model->doInits();
$model->doCalculations();

if( !isset( $_GET["sessidhash"] ) || SessionUser::getProperty("sessidhash") != $_GET["sessidhash"] ){
  die( "The page you were previously on has expired" );
}

$mname = $_GET["customAction"]."Action";
if(method_exists($model,$mname)) {
 echo $model->$mname($_GET);
}
else {
  echo "Method undefined!\n";
  exit;
}
// header( "Location: ". SITE_ROOT.$model->returnpage ); // This breaks most customactions except for finance which should include its own redirects
?>
