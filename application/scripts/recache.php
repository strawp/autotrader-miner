<?php
  /**
  * Script to help tidy up items in any model that need to be deleted due to duplication
  * - Updates any items that refer to this model and update it with the replacement ID
  * - Deletes any rows of member tables referring to this item
  */
  require_once( "../core/settings.php" );  
  require_once( "core/cache.class.php" );  
  
  echo "Data recache\n";
  
  if( !isset( $argv[1] ) ) die( "Use: ".$argv[0]." [-t -r -p -h -n] <modelname>\n" );
  
  $aArgs = array();
  $recachetree = false;   // Descend through all models depending on the one stated and recache those too
  $printtree = false;     // Print out tree to STDOUT
  $showhelp = false;      // Show help on the model's cache function, if available
  $noprocess = false;     // Don't process anything, just output what was asked for
  $loadlimit = false;     // Limit each query in the recache method to wait until the CPU load drops below a certain point
  $model = null;
  
  for( $i=1; $i<sizeof( $argv ); $i++ ){
    if( !isset( $argv[$i] ) ) continue;
    if( preg_match( "/^-([a-z]+)/", $argv[$i], $m ) ){
      // This is a switch
      switch( $m[1] ){
        case "r":  // "recurse"
        case "t":  // "tree"
          $recachetree = true;
          break;
          
        case "p":
          $printtree = true;
          break;
          
        case "h":
          $noprocess = true;
          $showhelp = true;
          break;
          
        case "n":
          $noprocess = true;
          break;
        
        case "l":
          $loadlimit = true;
          break;
      }
    }else{
      if( !isset( $model ) ){ 
        $model = trim( $argv[$i] );
      }
      else{
        // Arguments for recache method
        $aArgs[] = $argv[$i];
      }
    }
  }
  
  if( $printtree || $recachetree || $showhelp ){
    $m = Cache::getModel( $model );
  }
  if( $showhelp ){
    echo $m->getRecacheHelpText()."\n";
  }
  
  if( $printtree || $recachetree ){
    $m = Cache::getModel( $model );
    $oTree = $m->getCacheDependencyTree();
    echo "Recache tree:\n".printBranch( $oTree );
  }
  
  if( $noprocess ) exit;
  
  if( $recachetree ){
    recacheBranch( $oTree, $loadlimit, $aArgs );
  }else{
    doRecache( $model, $aArgs, $loadlimit );
  }
  
  
  function printBranch( $branch, $depth=0 ){
    $space = str_pad( "", $depth*2, " " );
    $rtn = "";
    $rtn .= $space.=$branch->name."\n";
    foreach( $branch->dependants as $dep ){
      $rtn.=printBranch( $dep, $depth+1 );
    }
    return $rtn;
  }
  
  function recacheBranch( $branch, $loadlimit, $aArgs=array() ){
    doRecache( $branch->name, $aArgs, $loadlimit );
    if( sizeof( $branch->dependants ) > 0 ){
      echo $branch->name." has dependants: ";
      $comma = "";
      foreach( $branch->dependants as $dep ){
        echo $comma.$dep->name;
        $comma = ", ";
      }
      echo "\n";
    }
    foreach( $branch->dependants as $dep ){
      recacheBranch( $dep, $loadlimit );
    }
  }
  
  function doRecache( $model, $aArgs=array(), $loadlimit ){
    echo "Recache: $model\n";
    $m = Cache::getModel( $model );
    
    if(!$m) die( "Couldn't get model $model\n" );
    
    echo "Running recache method...\n";
    $start = microtime(true );
    if( $loadlimit ) $limit = 0.8;
    else $limit = null;
    
    if( sizeof( $aArgs ) > 0 ) $m->recache( $aArgs, $limit );
    else $m->recache(array(),$limit);
    
    $duration = microtime(true)-$start;
    
    echo "Finished ".$model."->recache(), took ".formatPeriod( $duration, true )."\n\n";
  }
  
?>