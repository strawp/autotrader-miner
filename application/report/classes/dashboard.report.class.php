<?php
  /**
  * User dashboard report
  */
  require_once( "core/report.class.php" );

  class DashboardReport extends Report implements iFeature {
    function __construct(){
      parent::__construct();
      $this->title = "My Dashboard";
      $this->subtitle = "";
      // $this->addCssFile( "../css/default.css" );
      $this->addCssFile( "reports.css" );
      $this->addJsFile( "dashboard.js" );
      $this->aOptions["user_id"] = SessionUser::getId();
    }
    function getFeatureDescription(){
      return "Renders a user-configurable dashboard of widgets showing anything from task lists to breakdown of expenditure on projects";
    }
    
    
    // Ensure the user is only requesting their own dashboard
    function sendEmailReport($format,$user,$name=""){
    
      if( !is_object( $user ) ){
        if( $this->debug ) "User passed isn't a class\n";
        return false;
      }
      $this->aOptions["user_id"] = $user->id;
      return parent::sendEmailReport($format,$user,$name);
    }
    
    function setOptions($opts=array()){
      if( isset( $opts["user_id"] ) ) unset( $opts["user_id"] );
      return parent::setOptions($opts);
    }
    /**
    * Override to tidy the HTML before creating emails out of it
    */
    function getWrappedHtml($html=''){
      // Remove inline styles
      $html = preg_replace( "/(<div id=\"widget_[^>]+)style=\"[^\"]+\"/", "$1", $html );
      
      // Remove widget options
      $html = preg_replace( "/<p class=\"option[^\"]*\"[^>]*><a[^>]+>[^<]+<\/a><\/p>/", "", $html );
      
      return parent::getWrappedHtml($html);
    }
    
    function compile(){
      
      if( !isset( $this->aOptions["user_id"] ) ) $this->aOptions["user_id"] = SessionUser::getId();
    
      if( !isset( $this->aOptions["screen"] ) ){
        // Get list of widgets in index order
        $aWidgets = UserWidget::getWidgetsForUser( intval( $this->aOptions["user_id"] ) );
        $html = "";
        $index = 0;
        $rownum = 1;
        $gridwidth = 2;
        $total = sizeof( $aWidgets );
        $html .= "<p class=\"option\"><a class=\"add\" href=\"".SITE_ROOT."report/dashboard/screen/add\">Add another widget</a></p>\n";
        $tbl = new Table("Widgets");
        $tr = new TableRow("row$rownum");
        $rownum++;
        foreach( $aWidgets as $w ){
          if( ( $index > 0 && $index%$gridwidth == 0) || $w->width == $gridwidth ){
            while( sizeof( $tr->aCells ) < $gridwidth ){
              $cell = new TableCell( "", "col".($index%$gridwidth) );
              $cell->classname .= " cell cell$index";
              $tr->addCell( $cell );
              $index++;
            }
            $tbl->addRow( $tr );
            $tr = new TableRow("row$rownum");
            $rownum++;
          }
          $w->compile();
          if( $w->getGraphs() ) $this->aGraphs = array_merge( $w->getGraphs(), $this->aGraphs );
          if( !isset( $w->aJsFiles ) ) $w->aJsFiles = null;
          if( is_array( $w->aJsFiles ) ){ 
            $this->aJsFiles = array_merge( $w->aJsFiles, $this->aJsFiles );
          }
          $whtml = "<div id=\"widget_".intval($w->user_widget_id)."\" class=\"widget_container ".htmlentities($w->id)."\" title=\"".htmlentities($w->title)."\">";
          $whtml .= "<div class=\"options\">";
          if( $w->configurable ) $whtml .= "<p class=\"option\"><a class=\"configure\" href=\"".SITE_ROOT."report/dashboard/screen/configure/user_widget/".intval($w->user_widget_id)."\">Options</a></p>";
          $whtml .= "<p class=\"option\"><a class=\"delete\" href=\"".SITE_ROOT."report/dashboard/screen/_delete/user_widget/".intval($w->user_widget_id)."\">Remove widget</a></p>";
          $whtml .= "</div>\n";
          $whtml .= "  <div class=\"content\">".$w->html."</div>\n";
          /*
          if( $index > 1 ) $whtml .= "   <p class=\"option\"><a class=\"up\" href=\"".SITE_ROOT."report/dashboard/screen/_up/user_widget/".intval($w->user_widget_id)."\">Move up</a></p>\n";
          if( $index < $total ) $whtml .= "   <p class=\"option\"><a class=\"down\" href=\"".SITE_ROOT."report/dashboard/screen/_down/user_widget/".intval($w->user_widget_id)."\">Move down</a></p>\n";
          */
          $whtml .= "</div>\n";
          $cell = new TableCell( $whtml, "col".($index%$gridwidth) );
          $cell->colspan = $w->width;
          $cell->classname .= " cell cell$index";
          $cell->id = "widget_cell$index";
          $tr->addCell( $cell );
          $index+=$w->width;
        }
        $tbl->addRow( $tr );
        $totalwidgets = $index;
        $aVars = array( "gridwidth", "totalwidgets" );
        $html .= "<div class=\"vars\">";
        foreach( $aVars as $var ){
          $html .= "<var class=\"$var\">".$$var."</var>";
        }
        $html .= "</div>\n";
        $html .= $tbl->getHtml();
        $html .= "<div id=\"dragged_widget\"></div>";
      }else{
        switch( $this->aOptions["screen"] ){
          case "add":
            $html = $this->renderAddScreen();
            break;
          case "_add":
            $html = $this->doAddAction();
            break;
          case "_delete":
            $html = $this->doDeleteAction();
            break;
            
          case "configure":
            $html = $this->renderConfigureScreen();
            break;
          case "_configure":
            $html = $this->doConfigureAction();
            break;
            
          case "_up":
            $html = $this->doMoveAction(-1);
            break;
          case "_down":
            $html = $this->doMoveAction(1);
            break;
            
          case "_resize":
            $html = $this->doResizeAction();
            break;
            
          case "get_widget":
            $html = $this->renderGetWidgetScreen();
            break;
            
          case "_saveorder":
            $html = $this->doSaveOrderAction();
            break;
        }
      }
      
      $this->setHtml( $html );
    }
    
    function doSaveOrderAction(){
      $uw = Cache::getModel( "UserWidget" );
      $aIds = preg_split( "/,/", $this->aOptions["ids"] );
      $db = Cache::getModel("DB");
      foreach( $aIds as $k => $v ){
        $sql = "UPDATE user_widget SET position = ".($k+1)." WHERE id = ".intval($v)." AND user_id = ".intval( SessionUSer::getId() );
        $db->query($sql);
      }
      echo json_encode( $aIds );
      exit;
    }
    
    function renderGetWidgetScreen(){
      $uw = Cache::getModel( "UserWidget" );
      $uw->get( $this->aOptions["user_widget"] );
      if( !DashboardReport::checkCurrentUserAuthorisation( $uw, "retrieved" ) ){
        header( "Location: ".SITE_ROOT."report/dashboard" );
        exit;
      }
      $w = $uw->getWidget();
      $w->compile();
      echo $w->renderWebPage();
      exit;
    }

    function doResizeAction(){
      $uw = Cache::getModel( "UserWidget" );
      $uw->get( $this->aOptions["user_widget"] );
      if( !DashboardReport::checkCurrentUserAuthorisation( $uw, "resized" ) ){
        exit;
      }
      $width = intval( $this->aOptions["width"] );
      $uw->Fields->Width = $width;
      $uw->save();
      header( "Location: ".SITE_ROOT."report/dashboard/screen/get_widget/user_widget/".$uw->id );
      exit;
    }
    
    function doMoveAction($change){
      $uw = Cache::getModel( "UserWidget" );
      $uw->get( $this->aOptions["user_widget"] );
      if( !DashboardReport::checkCurrentUserAuthorisation( $uw, "Moved" ) ){
        header( "Location: ".SITE_ROOT."report/dashboard" );
        exit;
      }
      $db = Cache::getModel("DB");
      $currentidx = (int)$uw->Fields->Position->toString();
      $newidx = intval($currentidx+$change);
      if( $newidx < 1 ){
        $newidx = 1;
      }
      $uid = SessionUser::getId();
      $sql = "
        UPDATE user_widget 
        SET position = ".intval( $currentidx )." 
        WHERE position = ".intval( $newidx )." 
          AND user_id = ".SessionUser::getId();
      $db->query( $sql );
      $sql = "
        UPDATE user_widget 
        SET position = ".intval( $newidx )." 
        WHERE id = ".intval( $uw->id )." 
          AND user_id = ".SessionUser::getId();
      $db->query( $sql );
      
      if( isset( $this->aOptions["ajax"] ) ){
        echo json_encode( array( "newidx" => $newidx, "currentidx" => $currentidx ) );
      }else{
        header( "Location: ".SITE_ROOT."report/dashboard" );
      }
      exit;
    }
    
    function renderConfigureScreen(){
      $uw = Cache::getModel( "UserWidget" );
      $uw->get( $this->aOptions["user_widget"] );
      if( $uw->id == 0 ){
        return "The selected widget is not valid";
      }
      if( intval( $uw->Fields->UserId->value ) != SessionUser::getId() ){
        return "The selected widget is not one of your own";
      }
      $html = "";
      $w = $uw->getWidget();
      $html .= "<form id=\"frmWidgetOptions\" action=\"".SITE_ROOT."report/_go.php\" method=\"get\">\n".$w->renderOptions();
      $html .= "<input type=\"hidden\" value=\"_configure\" name=\"screen\" />\n";
      $html .= "<input type=\"hidden\" value=\"dashboard\" name=\"report\" />\n";
      $html .= "<input type=\"hidden\" value=\"".intval($this->aOptions["user_widget"])."\" name=\"lstUserWidget\" />\n";
      $html .= "<button type=\"submit\">Save</button>\n";
      $html .= "</form>\n";
      if( isset( $this->aOptions["ajax"] ) ) die( $html );
      return $html;
    }
    function doConfigureAction(){
      $uw = Cache::getModel( "UserWidget" );
      $uw->get( $this->aOptions["user_widget"] );
      if( $uw->id == 0 ){
        Flash::addError( "The widget you selected could not be configured" );
      }else{
        if( intval( $uw->Fields->UserId->value ) != SessionUser::getId() ){
          Flash::addError( "The selected widget is not one of your own" );
        }else{
          $str = "";
          $amp = "";
          foreach( $this->aOptions as $k => $v ){
            if( preg_match( "/^(|screen|report|user_widget|user_id)$/", $k ) ) continue;
            $str .= $amp.urlencode( $k )."=".urlencode( $v );
            $amp = "&";
          }
          $uw->Fields->Options = $str;
          $uw->save();
          $anc = "#widget_".intval($uw->id);
        }
      }
      header( "Location: ".SITE_ROOT."report/dashboard$anc" );
      exit;
    }
    
    function renderAddScreen(){
      $aWidgets = Widget::getAvailable();
      
      // Sort by popularity
      $db = Cache::getModel("DB");
      $db->query("
        SELECT widget, COUNT(*) as count FROM user_widget GROUP BY widget ORDER BY COUNT(*) DESC
      ");
      $aCounts = array();
      while( $row = $db->fetchRow() ){
        $aCounts[$row["widget"]] = $row["count"];
      }
      foreach( $aWidgets as $k => $w ){
        $name = underscoreToCamel( $w->id );
        if( !isset( $aCounts[$name] ) ) continue;
        $aWidgets[$k]->popularity = $aCounts[$name];
      }
      usort( $aWidgets, array( "self", "popularitySort" ));
      
      $html = "";
      $html .= "<form id=\"frmAddWidget\">\n";
      $html .= "  <h3>Add a widget to your dashboard</h3>\n";
      if( sizeof( $aWidgets ) > 0 ){
        $html .= "<ul class=\"widgets\">\n";
        foreach( $aWidgets as $w ){
          if( !$w->isVisibleToUser( $this->aOptions["user_id"] ) ) continue;
          $html .= "  <li>\n";
          $html .= "    <h4>".h($w->title)."</h4>\n";
          if( $w->description != "" ) $html .= "    <p class=\"description\">".h($w->description)."</p>\n";
          $html .= "    <p><a class=\"add\" href=\"".SITE_ROOT."report/dashboard/screen/_add/widget/".$w->id."\">Add to dashboard</a></p>\n";
          $html .= "  </li>\n";
        }
        $html .= "</ul>\n";
      }
      $html .= "</form>\n";
      if( isset( $this->aOptions["ajax"] ) )die( $html );      
      return $html;
    }
    
    /**
    * Add the selected widget to the user's list, forward the user on to the dashboard with the recently added one highlighted
    */
    function doAddAction(){
      $w = Cache::getModel(underscoreToCamel($this->aOptions["widget"])."Widget");
      if( !($w instanceof Widget) ){
        // error
        Flash::addError( "The widget you selected could not be added" );
      }else{
        $sql = "UPDATE user_widget SET position = position + 1 WHERE user_id = ".intval(SessionUser::getId());
        $db = Cache::getModel( "DB" );
        $db->query( $sql );
        $w->index = 1;
        $w->user_id = SessionUser::getId();
        $w->save();
      }
      header( "Location: ".SITE_ROOT."report/dashboard" );
      exit;
    }
    
    function doDeleteAction(){
      $uw = Cache::getModel( "UserWidget" );
      $uw->get( $this->aOptions["user_widget"] );
      if( $uw->id == 0 ){
        Flash::addError( "The widget you selected could not be removed" );
      }else{
        if( intval( $uw->Fields->UserId->value ) != SessionUser::getId() ){
          Flash::addError( "The selected widget is not one of your own" );
        }else{
          $sql = "UPDATE user_widget SET position = position - 1 WHERE user_id = ".intval(SessionUser::getId())." AND position > ".intval( $uw->Fields->Position->toString() );
          $db = Cache::getModel( "DB" );
          $db->query( $sql );
          $uw->delete();
        }
      }
      if( $this->aOptions["ajax"] ){
        $rtn = new StdClass();
        $rtn->id = $uw->id;
        $rtn->flash = Flash::getHtml();
        Flash::clear();
        echo json_encode( $rtn );
        exit;
      }
      header( "Location: ".SITE_ROOT."report/dashboard" );
      exit;
    }
    
    static function checkCurrentUserAuthorisation($uw, $action){
      if( $uw->id == 0 ){
        Flash::addError( "The widget you selected could not be $action" );
        return false;
      }
      if( intval( $uw->Fields->UserId->value ) != SessionUser::getId() ){
        Flash::addError( "The selected widget is not one of your own" );
        return false;
      }
      return true;
    }
    static function popularitySort( $a, $b ){
      if( !isset( $a->popularity ) ) return 1;
      if( !isset( $b->popularity ) ) return -1;
      if( $a->popularity > $b->popularity ) return -1;
      if( $a->popularity < $b->popularity ) return 1;
      return 0;
    }
  }
?>