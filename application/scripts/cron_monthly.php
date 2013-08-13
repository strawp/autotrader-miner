<?php
  /**
  * Run miscellaneous jobs that need doing on the first day of the month
  *
  * Do not assume this is *only* run monthly - more frequent runs (manually executed etc) should be non-harmful.
  * Group all tasks into functions and add the function call at the top of the script.
  */
  
  // Make sure the current dir is the dir the file is in
  chdir( dirname( $_SERVER["PHP_SELF"] ) );
  
  require_once( "../core/settings.php" );
  
?>
