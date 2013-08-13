<?php
  require_once( "core/settings.php" );
  session_start();
  
  addLogMessage( "Start of main index", "site root" );

  if( SessionUser::isLoggedIn() ){
    renderDashboard();
  }
  
  // Render login form
  else {
    renderLoginForm();
  }
  
  addLogMessage( "End", "site root" );
  require( "core/footer.php" );  
  
  function renderLoginForm(){
    require_once( "core/header.php" );
    if( SITE_AUTH == "db" ) require_once( "core/db.login.class.php" );
    else require_once( "core/login.class.php" );
    $login = new Login();
    $login->action = "login";
    $login->access = "u";
  ?>    
      <h2>Login</h2>
        <p><?php echo $login->prompt; ?></p>
  <?php
      if( isset( $aCookieData["first_name"] ) ){ 
        $login->aFields["first_name"]->value = strip_tags( $aCookieData["first_name"] );
      }
      if( isset( $aCookieData["first_name"] ) ) $login->aFields["last_name"]->value = strip_tags( $aCookieData["last_name"] );
      echo $login->renderForm( "_login.php", "post", "Log in" );
  }
  
  function renderDashboard(){
    $r = new DashboardReport();
    $r->setOptions( $_GET );
    if( !$r->userHasReadAccess(SessionUser::getUser())){
      echo "<p>You do not have access to this report</p>";
      exit;
    }
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
      $st = "/subscription_type/periodic";
      $html .= "<div class=\"optionscontainer\"><p>Options</p>\n";
      $html .= "<ul class=\"reportoptions\">\n";
      $html .= "  <li><a class=\"subscribe\" href=\"".$url.$st."/format/pdf\">Subscribe to periodic PDF emails of this report</a></li>\n";
      $html .= "  <li><a class=\"html_subscribe\" href=\"".$url.$st."/format/html\">Subscribe to periodic HTML emails of this report</a></li>\n";  // email_open_image
      $html .= "  <li><a class=\"bookmark\" href=\"".$url."/subscription_type/bookmark\">Add this to \"My Areas\"</a></li>\n";  // email_open_image
      $html .= "</ul>\n";
      $html .= "</div>\n";
      echo "<div class=\"dashboard report\">".$html."</div>";
    }
  }
?>  