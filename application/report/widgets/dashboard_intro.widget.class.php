<?php
  require_once( "core/settings.php" );
  class DashboardIntroWidget extends Widget {
    function __construct(){
      parent::__construct();
      $this->title = "Dashboard Introduction";
      $this->description = "A helpful introductory guide to the dashboard report";
      $this->aJsFiles = array( "dashboard_intro.js" );
      $this->width=1;
      $this->priority = 1;
      $this->aOptions = array();
    }
    function compile(){
      $this->aJsFiles = array( "dashboard_intro.js" );
      $html = '
        <h3>Welcome to the '.SITE_NAME.' dashboard!</h3>
        <p>Please follow the quick tour to learn how to use the features of the new dashboard.</p>
        <p>Once you have finished you can remove this widget to not see the tour again.</p>
      ';
      $this->setHtml( $html );
    }

  }
?>