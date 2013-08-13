<?php
  require_once( "core/settings.php" );
  class SearchWidget extends Widget {
    function __construct(){
      parent::__construct();
      $this->title = "Search";
      $this->description = "Run a quick search on a part of ".SITE_NAME;
      $this->width = 1;
      $this->configurable = true;
      $this->aJsFiles = array( "search.widget.js" );
      $this->priority = 10;
      $this->aOptions = array();
    }
    function compile(){
      if( !isset( $this->aOptions["model"] ) ){
        $html = 'Click "Options" to set up this widget';
      }else{
        $model = $this->aOptions["model"];
        
        // Check model exists
        $o = Cache::getModel( $model );
        if( !$o ){
          $html = 'Click "Options" to set up this widget';
          $this->setHtml( $html );
          return;
        }
        $this->title = $o->displayname.' Search';
    
        // Check user has access to model
        $u = Cache::getModel("User");
        $u->get( $this->user_id );
        if( !$o->userHasReadAccess( $u ) ){
          $html = 'Sorry, you are currently not allowed read access to '.$o->displayname;
          $this->setHtml( $html );
          return;
        }
        
        // Check there is a name field
        if( !isset( $o->aFields["name"] ) ){
          $this->setHtml( 'Sorry, '.plural( $o->displayname )." can't currently be searched on using this widget" );
          return;
        }
        
        // Construct quick search form
        $html = '
        <h3>'.htmlentities($this->title).'</h3>
        <form id="frmSearch" class="search" action="'.SITE_BASE.$o->tablename.'/_search" method="get">
          <label>Name:</label> '
          .$o->aFields["name"]->toField().
          '<input type="hidden" value="'.$o->tablename.'" name="model" />
          <input type="hidden" value="name" name="fields" />
          <input type="submit" value="Search" id="btnSubmit" name="btnSubmit" class="button" />
        </form>
        <p><a href="'.SITE_BASE.$o->tablename.'">Perform a more detailed search</a></p>
        ';
      }
      $this->setHtml( $html );
    }
    
    function renderOptions(){
      
      // List of things to search on
      $aModels = array(
        "User" => "People",
        "OrgUnit" => "Organisation Units",
        "ChangeLog" => "Changes",
      );
      
      $html = "";
      $uid = $this->user_id;
      $html .= "
      <div>\n";
      
      $lst = Field::create( "lstModel", "displayname=Area to search on" );
      $lst->htmlname = "strModel";
      $lst->listitems = $aModels;
      
      if( isset( $this->aOptions["model"] ) ) $lst->value = $this->aOptions["model"];
      $html .= $lst->render();
      $html .= "
      </div>\n";
      return $html;
    }
  }
?>