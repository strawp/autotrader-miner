<?php
  session_start();
  require_once( "../core/settings.php" );
  require_once( "../core/functions.php" );
  require_once( "functions.php" );
  if( SessionUser::getProperty("role") != "Staff" ){
    header( "Location: ".SITE_ROOT );
    exit;
  }

  $page_title = "Reports";
  $page_css = array( SITE_ROOT."css/reports.css" );
  $page_js = array( SITE_ROOT."js/reports.js" );
  
  // echo renderReportBreadCrumb();
  if( !isset( $_GET["report"] ) ){
    // echo "    <h2>Reports</h2>\n";
    // echo $menu->render( "report", "report", true );
    require_once( "core/header.php" );
    if( isset( $menu ) ){
      foreach( $menu->aItems as $k => $item ){
        if( $item->name != "Reports" ) continue;
        echo $menu->render( $k, "index" );
      }
    }
    // Display reports section of nav with descriptions
    
  }else{
  
    $reportname = preg_replace( "/[^a-z0-9_]/", "", $_GET["report"] );
    // echo "report/classes/".$reportname.".report.class.php";
    
    // Look for report class
    if( file_exists( "../report/classes/".$reportname.".report.class.php" ) ){
      $classname = underscoreToCamel( $reportname )."Report";
      $r = new $classname();
      $r->setOptions( $_GET );
      $user = SessionUser::getUser();
      if( !$r->userHasReadAccess($user)){
        echo "<p>You do not have access to this report</p>";
        exit;
      }
      $r->requesting_user = $user;
      $r->compile();
      $page_title = $r->title;
      if( sizeof( $r->aCssFiles ) > 0 ){
        foreach( $r->aCssFiles as $css ){
          $page_css[] = SITE_ROOT."css/$css";
        }
      }
      if( sizeof( $r->aJsFiles ) > 0 ){
        foreach( $r->aJsFiles as $js ){
          $page_js[] = SITE_ROOT."js/$js";
        }
      }
      if( isset( $_GET["gettable"] ) ){ 
        $extension = "xls";
        $tbl = $r->getTable(urldecode($_GET["gettable"]));
        if( $tbl ){ 
          header( "Content-type: text/csv" );
          header( "Content-disposition: Attachment; filename=".$tbl->name."_".date( "Y-m-d_His" ).".".$extension );
          echo $r->getWrappedHtml($tbl->getHtml());
          exit;
        }
        else die( "Didn't find table \"".htmlentities( $_GET["gettable"] )."\" in report \"".$r->name."\"" );
        // echo $r->getTable($_GET["gettable"])->getTsv();
      }else{
        $pageclass = $r->classname;
        $page_inlinecss = $r->getCustomCss();
        require_once( "core/header.php" );
        $html = $r->renderWebPage();
        
        // Subscribe link
        $url = "";
        $url = SITE_ROOT."user_report/new/name/".urlencode( urlencode( "Report: ".$r->title ) );
        $url .= "/url/".urlencode( urlencode( str_replace( SITE_BASE, SITE_ROOT, $r->getOptionsUrl() ) ));
        
        $sendnow = SITE_ROOT."report/_sendnow/".str_replace( SITE_BASE."report/", "", $r->getOptionsUrl() );
        $st = "/subscription_type/periodic";
        $html .= "<div class=\"optionscontainer\"><p>Options</p>\n";
        $html .= "<ul class=\"reportoptions\">\n";
        $html .= "  <li><a class=\"sendnow\" href=\"".$sendnow."\">Send this report via email now</a></li>\n";
        $html .= "  <li><a class=\"subscribe\" href=\"".$url.$st."/format/pdf\">Subscribe to periodic PDF emails of this report</a></li>\n";
        $html .= "  <li><a class=\"html_subscribe\" href=\"".$url.$st."/format/html\">Subscribe to periodic HTML emails of this report</a></li>\n";  // email_open_image
        $html .= "  <li><a class=\"bookmark\" href=\"".$url."/subscription_type/bookmark\">Add this to \"My Areas\"</a></li>\n";  // email_open_image
        $html .= "</ul>\n";
        $html .= "</div>\n";
        echo $html;
      }
    }
    // Look for report page
    elseif( file_exists( "../report/".$reportname.".php" ) ){
      require_once( "core/header.php" );
      require( $_GET["report"].".php" );
    }
  
    // Unknown report
    else{
      require_once( "core/header.php" );
      echo "<p>Sorry, I don't know that report</p>\n";
    }
  }
  
  if( !isset( $_GET["gettable"] ) ){ 
    require( "core/footer.php" );
  }

?>
