<?php
  /**
  * Handle args for and render a wizard
  */
  // $_GET["wizard"] required
  
  if( !isset( $_GET["wizard"] ) ) die( "No wizard supplied" );
  
  require_once( "../core/settings.php" );
  session_start();
  // Cache::flushWizards();
  
  $wizname = underscoreToCamel( $_GET["wizard"] )."Wizard";
  // $wiz = new $wizname();
  $id = isset( $_GET["id"] ) ? intval( $_GET["id"] ) : 0;
  $wiz = Cache::getWizard($wizname, $id );
  $wiz->init( $_GET );
  
  // This looks pointless, but it's useful
  if( isset( $_GET["step"] ) ) $wiz->setCurrentStep( intval( $_GET["step"] ) );
  if( isset( $_GET["checklist"] ) ){
    $page_title = $wiz->name.": Checklist";
  }else{
    $page_title = $wiz->name.": ".$wiz->CurrentStep()->name;
  }
  $wiz->actionpage = SITE_ROOT."wizard/_action";
  $page_css = array( SITE_ROOT."css/wizard.css" );
  $page_css = array_merge( $wiz->getPageCss(), $page_css );
  $page_js = $wiz->getPageJs();
  $page_inlinejs = $wiz->getInlineJs();
  $page_inlinecss = $wiz->getInlineCss();
  
  require( "../core/header.php" );
  
  if( isset( $_GET["checklist"] ) ){ 
    echo $wiz->renderSummary();
  }else echo $wiz->render();
  
  require( "../core/footer.php" );
?>