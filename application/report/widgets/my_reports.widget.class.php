<?php
  require_once( "core/settings.php" );
  class MyReportsWidget extends Widget {
    function __construct(){
      parent::__construct();
      $this->title = "My Areas";
      $this->description = "Display a linked list of sections of ".SITE_NAME." that have been bookmarked using the \"My Areas\" feature.";
      $this->configurable = true;
      $this->width = 1;
      $this->priority = 11;
      $this->aOptions["show_tasks"] = 1;
    }
    function compile(){
      $u = Cache::getModel("User");
      $u->get( $this->user_id );
      $sql = "
        SELECT *
        FROM user_report
        WHERE user_id = ".intval($this->user_id )."
          AND subscription_type = 'bookmark'
      ";
      if( !isset( $db ) ) $db = Cache::getModel( "DB" );
      $db = Cache::getModel("DB");
      $db->query( $sql );
      $html = "";
      $html .= "      <h3>".h($this->title)."</h3>\n";
      $html .= "<p>Links to sections of ".SITE_NAME." that you add to \"My Areas\" will appear here</p>\n";
      if( $db->numrows > 0 ){
        $html .= "      <ul class=\"reports\">\n";
        while( $row = $db->fetchRow() ){
          $url = preg_replace( "/^\//", "", $row["url"] );
          $url = SITE_ROOT.htmlentities( $url );
          $html .= "        <li><a href=\"".$url."\">".htmlentities($row["name"])."</a></li>\n";
        }
        $html .= "      </ul>\n";
      }
      if( $this->aOptions["show_tasks"] ){
        $html .= "      <h4>Tasks</h4>\n";
        $html .= "      <ul class=\"tasks\">\n";
        if( $u->isAdmin() )           $html .= "        <li><a href=\"".SITE_BASE."issue\">Review Issues</a></li>\n";
        $html .= "        <li><a href=\"".SITE_BASE."issue/user_id/".$u->id."\">Your active issues</a></li>\n";
        $html .= "      </ul>\n";
      }
      $this->setHtml( $html );
    }
    function renderOptions(){
      $html = "";
      $uid = $this->user_id;
      $html .= "
      <div>\n";      

      $cnf = Field::create( "cnfShowTasks", "helphtml=Show a list of suggested common tasks to people in your role" );
      $cnf->value = isset( $this->aOptions["show_tasks"] ) && $this->aOptions["show_tasks"];
      $html .= $cnf->render();

      $html .= "
      </div>\n";
      return $html;
    }
  }
?>