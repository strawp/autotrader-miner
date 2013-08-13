<?php
  require_once( "core/settings.php" );
  require_once("flash.class.php");
  require_once("session_user.class.php");
  header( 'Content-type: text/html; charset='.SITE_CHARSET );
	addLogMessage( "Header started, settings included", "Header" );
  // Be absolutely sure the site is 100% XML compatible (including AJAX stuff) before uncommenting the following line:
  // header("Content-Type:application/xhtml+xml; charset=ISO-8859-1");
  $msie = strstr( $_SERVER["HTTP_USER_AGENT"], "MSIE" ) !== false;
  globalAuth();
  $aElementIds = array();
  // unset( $_SESSION["cache"] );
  // print_r( $_SESSION["cache"] );
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
  <title><?php 
    echo SITE_NAME; 
    $title = "";
    if( isset( $model ) ){
      if( $model->id > 0 ){
        $title = htmlentities( $model->getName() );
        echo ": ".htmlentities( $model->getName() );
      }else{
        $title = plural( $model->displayname );
        echo ": ".h(plural( $model->displayname ));
      }
      $title = ucfirst( $model->action )." ".$title;
      if( $model->action != "search" ){
        echo ": ".strip_tags( $model->action );
      }
    }elseif( isset( $page_title ) ){
      $title = $page_title;
      echo ": ".$page_title;
    }
    Breadcrumb::init();
    $ref = isset( $_SERVER["HTTP_REFERER"] ) ? $_SERVER["HTTP_REFERER"] : "";
    Breadcrumb::addByReferer( $title, $_SERVER["REQUEST_URI"], $ref );
    addLogMessage( "Worked out page title", "Header" );
      ?></title>
  <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />
  <link rel="stylesheet" type="text/css" media="all" href="<?php echo SITE_BASE; ?>css/default.css?<?php echo SITE_LASTUPDATE; ?>" />
  <link rel="stylesheet" type="text/css" media="all" href="<?php echo SITE_BASE."css/".SITE_BRANDCSS."?".SITE_LASTUPDATE; ?>" />
  <!--[if IE]>
  <link rel="stylesheet" type="text/css" media="all" href="<?php echo SITE_BASE; ?>css/ie.css?<?php echo SITE_LASTUPDATE; ?>" />
  <![endif]-->
  <link rel="stylesheet" type="text/css" href="<?php echo SITE_BASE; ?>css/cupertino/jquery-ui-1.8.16.custom.css?<?php echo SITE_LASTUPDATE; ?>" />	  
  <link rel="stylesheet" type="text/css" media="all" href="<?php echo SITE_BASE; ?>js/jscalendar/calendar-staffnet.css?<?php echo SITE_LASTUPDATE; ?>" title="Calendar" />
  <link rel="stylesheet" type="text/css" media="all" href="<?php echo SITE_BASE; ?>css/thickbox.css?<?php echo SITE_LASTUPDATE; ?>" />
  <link rel="stylesheet" type="text/css" href="<?php echo SITE_BASE; ?>css/tabs_fix.css?<?php echo SITE_LASTUPDATE; ?>" />
  <link rel="stylesheet" type="text/css" href="<?php echo SITE_BASE; ?>css/jquery.autocomplete.css?<?php echo SITE_LASTUPDATE; ?>" />
  <link rel="stylesheet" type="text/css" media="handheld, only screen and (max-device-width:480px)" href="<?php echo SITE_BASE; ?>css/handheld.css?<?php echo SITE_LASTUPDATE; ?>" />
  <link rel="shortcut icon" href="<?php echo SITE_BASE; ?>favicon.ico" />
  <script type="text/javascript" src="<?php echo SITE_BASE; ?>js/jscalendar/calendar.js?<?php echo SITE_LASTUPDATE; ?>"></script>
  <script type="text/javascript" src="<?php echo SITE_BASE; ?>js/jscalendar/lang/calendar-en.js?<?php echo SITE_LASTUPDATE; ?>"></script>
  <script type="text/javascript" src="<?php echo SITE_BASE; ?>js/jscalendar/calendar-setup.js?<?php echo SITE_LASTUPDATE; ?>"></script>
  <script type="text/javascript">
  var globalSettings = new Object();
<?php 
  echo "    globalSettings.site_root = \"".SITE_ROOT."\";\n";
  echo "    globalSettings.sessidhash = \"".preg_replace( "/[^a-z0-9]/", "", SessionUser::getProperty("sessidhash") )."\";\n";
  echo "    globalSettings.site_periodoffset = ".SITE_PERIODOFFSET.";\n";
?>
  </script>
<?php
  if( !isset( $pageclass ) ) $pageclass = "";
  $a = preg_split( "/\?/", $_SERVER["REQUEST_URI"] ); // OK
  $pageclass .= str_replace( array( SITE_ROOT, "/" ), " ", preg_replace( "/&.*$/", "", strip_tags( $a[0] ) ) );
  $pageclass .= trim( $pageclass ) == "" ? "home" : "";
  if( !SessionUser::isLoggedIn() ) $pageclass .= " nologin";
  addLogMessage( "Worked out page class", "Header" );
?>
  <script type="text/javascript" src="<?php echo SITE_BASE; ?>js/jquery-1.6.2.min.js?<?php echo SITE_LASTUPDATE; ?>"></script>
  <script type="text/javascript" src="<?php echo SITE_BASE; ?>js/thickbox.js?<?php echo SITE_LASTUPDATE; ?>"></script>
  <script type="text/javascript" src="<?php echo SITE_BASE; ?>js/form.js?<?php echo SITE_LASTUPDATE; ?>"></script>
  <script type="text/javascript" src="<?php echo SITE_BASE; ?>js/jquery.history.js?<?php echo SITE_LASTUPDATE; ?>"></script>
  <script type="text/javascript" src="<?php echo SITE_BASE; ?>js/jquery.tablesorter.js?<?php echo SITE_LASTUPDATE; ?>"></script>
  <script type="text/javascript" src="<?php echo SITE_BASE; ?>js/jquery.textarea-expander.js?<?php echo SITE_LASTUPDATE; ?>"></script>
<?php if( !$msie ){ ?>  <script type="text/javascript" src="<?php echo SITE_BASE; ?>js/jquery.extensions.js?<?php echo SITE_LASTUPDATE; ?>"></script><?php } ?>
  <script type='text/javascript' src="<?php echo SITE_BASE; ?>js/jquery.bgiframe.min.js?<?php echo SITE_LASTUPDATE; ?>"></script>
  <script type='text/javascript' src="<?php echo SITE_BASE; ?>js/jquery.ajaxQueue.js?<?php echo SITE_LASTUPDATE; ?>"></script>
  <script type='text/javascript' src="<?php echo SITE_BASE; ?>js/jquery-ui-1.8.16.custom.min.js?<?php echo SITE_LASTUPDATE; ?>"></script>
  <script type="text/javascript" src="<?php echo SITE_BASE; ?>js/ui.js?<?php echo SITE_LASTUPDATE; ?>"></script>
<?php
  if( isset( $model ) && $model->hasCustomJs() ){
    echo "  <script type=\"text/javascript\" src=\"".$model->getCustomJsPath()."?".SITE_LASTUPDATE."\"></script>\n";
  }
  if( isset( $model ) && $model->hasCustomCss() ){
    echo "  <link rel=\"stylesheet\" type=\"text/css\" media=\"all\" href=\"".$model->getCustomCssPath()."?".SITE_LASTUPDATE."\" />\n";
  }
  if( isset( $page_js ) ){
    if( !is_array( $page_js ) && $page_js != "" ){
      echo "  <script type=\"text/javascript\" src=\"".$page_js."?".SITE_LASTUPDATE."\"></script>\n";
    }elseif( is_array( $page_js ) ){
      $page_js = array_unique( $page_js );
      foreach( $page_js as $js ){
        echo "  <script type=\"text/javascript\" src=\"".$js."?".SITE_LASTUPDATE."\"></script>\n";
      }
    }
  }
  if( isset( $page_css ) ){
    if( !is_array($page_css) && $page_css != "" ){
      echo "  <link rel=\"stylesheet\" type=\"text/css\" media=\"all\" href=\"".$page_css."?".SITE_LASTUPDATE."\" />\n";
    }elseif( is_array( $page_css ) ){
      $page_css = array_unique( $page_css );
      foreach( $page_css as $css ){
        echo "  <link rel=\"stylesheet\" type=\"text/css\" media=\"all\" href=\"".$css."?".SITE_LASTUPDATE."\" />\n";
      }
    }
  }
  if( isset( $page_inlinejs ) ){
    echo "  <script type=\"text/javascript\" >".$page_inlinejs."\n  </script>\n";
  }
  if( isset( $page_inlinecss ) ){
    echo "  <style>".$page_inlinecss."\n  </style>\n";
  }

?></head>
<body><?php if( !isset( $_GET["_contentonly"] ) ){ ?>
  <div id="jumplinks">
    <a href="#content" accesskey="2">Skip to content</a> | 
    <a href="#navigation" accesskey="3">Skip to navigation</a> | 
    <a href="#footer" accesskey="4">Skip to footer</a>
  </div>
  <div id="container">
  <?php
  // if (SITE_TYPE == "TEST") echo "<div class=\"server_info\">You are now working on the TEST site!</div>\n";
  ?>
    <div id="header">
      <h1><a href="<?php echo SITE_ROOT; ?>" accesskey="1"><?php echo SITE_NAME; ?></a></h1>
      <p class="tagline"><?php echo SITE_TAGLINE; ?></p>
    </div>
    <div id="main">
<?php require( "login.php" ); 
  addLogMessage( "Login included", "Header" );
?>    
<?php require( "navigation.php" ); 
  addLogMessage( "Navigation included", "Header" );
  } // close contentonly if statement
?>    
    <div id="content" class="<?php echo h($pageclass); ?>">
      <a name="content" class="anchor"></a>
<?php
  // Display flash notice if available
  echo Flash::getHtml();
  addLogMessage( "End", "Header" );
?>
