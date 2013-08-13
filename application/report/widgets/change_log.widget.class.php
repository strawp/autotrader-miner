<?php
  require_once( "core/settings.php" );
  class ChangeLogWidget extends Widget {
    function __construct(){
      parent::__construct();
      $this->title = "Recent Changes To ".SITE_NAME;
      $this->description = "Lists changes recently made to the ".SITE_NAME." software";
      $this->configurable = true;
      $this->aOptions["limit"] = 1;
      $this->priority = 21;
    }
    function compile(){
      $html = "";
      $html .= "      <h3>".h($this->title)."</h3>\n";
      $cl = Cache::getModel( "ChangeLog" );
      $html .= $cl->renderList( "SELECT * FROM change_log ORDER BY date DESC LIMIT ".intval($this->aOptions["limit"]), false );
      $this->setHtml( $html );
    }
    function renderOptions(){
      $html = "";
      $html .= "
      <div>\n";
      $html .= "<h4>Size of list</h4>\n
        <p>Select how many change items you want to list</p>\n";
      $lst = Field::create( "lstLimit" );
      for( $i=1; $i<=20; $i++ ){
        $lst->listitems[$i] = $i;
      }
      
      $lst->value = isset( $this->aOptions["limit"] ) ? intval( $this->aOptions["limit"] ) : 3;
      $html .= $lst->render();
      $html .= "
      </div>\n";
      return $html;
    }
  }
?>