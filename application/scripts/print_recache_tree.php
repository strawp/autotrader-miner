<?php
  require_once( "../core/settings.php" );
  echo "Get recache tree\n";
  
  if( !isset( $argv[1] ) ) die( "Use: ".$argv[0]." <modelname>\n" );
  
  $model = $argv[1];
  echo "Getting model, $model\n";
  $m = Cache::getModel( $model );

  $oTree = $m->getCacheDependencyTree();
  echo printBranch( $oTree );
  
  function printBranch( $branch ){
    $space = str_pad( "", $branch->depth*2, " " );
    $rtn = "";
    $rtn .= $space.=$branch->name."\n";
    foreach( $branch->dependants as $dep ){
      $rtn.=printBranch( $dep );
    }
    return $rtn;
  }
?>