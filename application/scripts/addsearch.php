<?php
  chdir( dirname( $_SERVER["PHP_SELF"] ) );
  require( "../core/settings.php" );
  $s = new Search();
  $s->Fields->Url = $argv[1];
  $s->save();
  echo "Run \"php runsearches.php\" to import cars from this search\n";
?>
