<?php

  require_once( "../core/settings.php" );

  // Members tables for member interface control
  $members = "user_user_group";
  
  // Tables on either side of members tables
  $memjoin = "user_group"
    ."|user";
  
  // Report pages /report/<name>
  $reports = "agenda"
    ."|dashboard"
    ."|deferred"
    ."|features"
    ."|issue_response_due"
    ."|schema";
    
  // Wizards
  $wizards = "my_profile";
  
  // Tables which the label printer interface is available for
  // $label = "project";
  $label = "";
  
  // $args = "\/?([^\/]+)?\/?([^\/]+)?\/?([^\/]+)?\/?([^\/]+)?\/?([^\/]+)?\/?([^\/]+)?\/?([^\/]+)?\/?([^\/]+)?\/?([^\/]+)?\/?([^\/]+)?\/?([^\/]+)?";
  $args = "((\/([^\/]+)\/([^\/]+))+)?";
  
  $aRoutes = array();

  // Reports
  $aRoutes[] = array( 
    "regexp" => 'report\/('.$reports.')'.$args,
    "script" => 'report/index.php',
    "args" => array( "report", "" )
  );
  $aRoutes[] = array( 
    "regexp" => 'report\/_sendnow\/('.$reports.')'.$args,
    "script" => 'report/_sendnow.php',
    "args" => array( "report", "" )
  );

  // Wizards
  $aRoutes[] = array( 
    "regexp" => 'wizard\/('.$wizards.')'.$args,
    "script" => 'view/wizard.php',
    "args" => array( "wizard", "" )
  );
  $aRoutes[] = array( 
    "regexp" => 'wizard\/_action'.$args,
    "script" => 'view/_wizard_action.php',
    "args" => array()
  );

  # Members interface
  // $aRoutes[] = '('.$members.')\/(edit)\/('.$memjoin.')\/(\d+) view/form.php?model=$1&action=$2&id=$4&context=$3';
  $aRoutes[] = array( 
    "regexp" => '('.$members.')\/(edit)\/('.$memjoin.')\/(\d+)\/?([^\/]+)?',
    "script" => 'view/form.php',
    "args" => array( "model", "action", "context", "id", "options" )
  );
  
  # Edit, mail, field select
  // $aRoutes[] = '('.$edit.')\/(new|edit|mail)\/?(\d+)? view/form.php?model=$1&action=$2&id=$3';
  $aRoutes[] = array( 
    // "regexp" => '('.$edit.')\/(edit|mail)\/?(\d+)?',
    "regexp" => '([a-z_]+)\/(edit|mail|wizard)\/?(\d+)?',
    "script" => 'view/form.php',
    "args" => array( "model", "action", "id" )
  );
  
  // New
  $aRoutes[] = array( 
    // "regexp" => '('.$edit.')\/(new)'.$args,
    "regexp" => '([a-z_]+)\/(new)'.$args,
    "script" => 'view/form.php',
    "args" => array( "model", "action" )
  );

  // AJAX fields
  $aRoutes[] = array( 
    // "regexp" => '('.$edit.')\/_ajax_fields\/?(\d+)?',
    "regexp" => '([a-z_]+)\/_ajax_fields\/?(\d+)?',
    "script" => 'view/_ajax_fields.php',
    "args" => array( "model", "id" )
  );
  
  # Delete (prompt)
  // $aRoutes[] = '('.$delete.')\/(edit|view|delete)\/([^\/]+)?\/?([^\/]+)? view/do.php?id=$3&action=$2&model=$1&options=$4';
  $aRoutes[] = array( 
    // "regexp" => '('.$delete.')\/(edit|view|delete)\/([^\/]+)?\/?([^\/]+)?',
    "regexp" => '([a-z_]+)\/(edit|view|delete|wizard)\/([^\/]+)?\/?([^\/]+)?',
    "script" => 'view/do.php',
    "args" => array( "model", "action", "id", "options" )
  );
  
  # Action, search, suggest, repeat, export, random
  // $aRoutes[] = '('.$action.')\/_(action|search|suggest|repeat) view/_$2.php';
  $aRoutes[] = array( 
    // "regexp" => '('.$action.')\/_(action|search|suggest|repeat|ajax_get_calculation_dependants|ajax_do_calculations|ajax_get_search_summary)',
    "regexp" => '([a-z_]+)\/_(action|search|suggest|repeat|ajax_get_calculation_dependants|ajax_do_calculations|ajax_get_search_summary)',
    "script" => 'view/_$2.php',
    "args" => array()
  );
  $aRoutes[] = array( 
    // "regexp" => '('.$action.')\/_(ajax_search|ajax_autocomplete)',
    "regexp" => '([a-z_]+)\/_(ajax_search|ajax_autocomplete)',
    "script" => 'view/_$2.php',
    "args" => array("model")
  );

  // Import users
  $aRoutes[] = array( 
    "regexp" => '(user_import)\/_import\/([^\/]+)\/([^\/]+)',
    "script" => 'view/_import.php',
    "args" => array( "model", "from", "name" )
  );
  
  // Create label
  $aRoutes[] = array( 
    "regexp" => '('.$label.')\/(label)\/?(\d+)?',
    "script" => 'view/_label.php',
    "args" => array( "model", "action", "id" )
  );

  $aRoutes[] = array( 
    "regexp" => '([a-z_]+)\/_export'.$args,
    "script" => 'view/export.php',
    "args" => array( "model", "" )
  );
  $aRoutes[] = array( 
    // "regexp" => '('.$list.')\/field_select'.$args,
    "regexp" => '([a-z_]+)\/field_select'.$args,
    "script" => 'view/field_select.php',
    "args" => array( "model", "args" )
  );
  $aRoutes[] = array( 
    // "regexp" => '('.$list.')\/_field_select_reset',
    "regexp" => '([a-z_]+)\/_field_select_reset',
    "script" => 'view/_field_select_reset.php',
    "args" => array( "model", "" )
  );
  $aRoutes[] = array( 
    // "regexp" => '('.$list.')\/_random',
    "regexp" => '([a-z_]+)\/_random',
    "script" => 'view/_random.php',
    "args" => array( "model", "" )
  );
  $aRoutes[] = array( 
    // "regexp" => '('.$list.')\/_goto\/([^\/]+)?',
    "regexp" => '([a-z_]+)\/_goto\/([^\/]+)?',
    "script" => 'view/_goto.php',
    "args" => array( "model", "value" )
  );
  $aRoutes[] = array( 
    "regexp" => 'logout\/_switch_user\/([^\/]+)',
    "script" => 'logout/_switch_user.php',
    "args" => array( "user" )
  );
  
  /* custom actions support in model definition for any model that has action access */
  $aRoutes[] = array( 
    // "regexp" => '('.$action.')\/customAction\/([^\/]+)\?'.$args,
    "regexp" => '([a-z_]+)\/customAction\/([^\/]+)\?'.$args,
    "script" => 'view/_custom_action.php',
    "args" => array("model","customAction","")
  );
  // Timesheet admin area
  $aRoutes[] = array(
    "regexp" => "timesheet_admin\/request\/(\d+)",
    "script" => "timesheet_admin/request.php",
    "args" => array( "user" )
  );
  $aRoutes[] = array(
    "regexp" => "timesheet_admin\/approve",
    "script" => "timesheet_admin/approve.php",
    "args" => array( "user" )
  );


// $aRoutes[] = '('.$list.')'.$args.' view/index.php?model=$1&$2=$3&$4=$5&$6=$7&$8=$9';
  $aRoutes[] = array( 
    // "regexp" => '('.$list.')'.$args,
    "regexp" => '([a-z_]+)'.$args,
    "script" => 'view/index.php',
    "args" => array( "model" )
  );
  
  
  // Issue form
  /*
  $aRoutes[] = array( 
    "regexp" => '(issue)'.$args,
    "script" => 'issue/index.php',
    "args" => array( "model" )
  );
  */
  
  
  # Add team member
  // $aRoutes[] = 'team\/(add|manager)\/(.*)? team/add.php?email=$2&action=$1';
  
  # Accept team membership
  // $aRoutes[] = '(team_user)\/(accept)\/(\d+)\/([^\/]+) view/do.php?model=$1&action=$2&id=$3&verification=$4';
  
  # Team user = team interface
  // $aRoutes[] = 'team_user\/? team/';
  
  $match = false;
  $sr = str_replace( "/", "\/", SITE_ROOT );
  // echo $_SERVER["REQUEST_URI"]."<br/>\n";
  
  $aRequest = preg_split( "/\//", preg_replace( "/^".str_replace( "/", "\/", SITE_ROOT )."/", '', $_SERVER["REQUEST_URI"] ) ); // OK
  
  while( !$match && sizeof( $aRoutes ) > 0 ){
    $regexp = '/^'.$sr.$aRoutes[0]["regexp"].'/';
    // echo $regexp."<br/>\n";
    if( preg_match( $regexp, $_SERVER["REQUEST_URI"], $aMatch ) ){
      $match = true;
      $path = $aRoutes[0]["script"];
      $args = $aRoutes[0]["args"];
      foreach( $args as $key => $arg ){
        if( isset( $aMatch[$key+1] ) ) $_GET[$arg] = $aMatch[$key+1];
      }
      if( sizeof( $args ) > 0 ){
        for( $i=sizeof( $args ); $i<sizeof( $aRequest ); $i+=2 ){
          if( isset( $aRequest[$i+1] ) ) $_GET[$aRequest[$i]] = $aRequest[$i+1];
        }
      }
    }
    array_shift( $aRoutes );
  }
  
  if( !$match ){
    // Redirect to home
    header( "Location: ".SITE_ROOT );
    exit;
  }
  for( $i=0; $i<sizeof( $aMatch ); $i++ ){
    $path = str_replace( '$'.$i, $aMatch[$i], $path );
  }
  require( "../".$path );
?>
