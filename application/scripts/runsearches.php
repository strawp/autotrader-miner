<?php
  // Run all searches not updated in last day
  chdir( dirname( $_SERVER["PHP_SELF"] ) );
  require_once( "../core/settings.php" );
  AutotraderConnector::determineAllActive( "-1 days" );
  Search::runAllSince( "-12 hours" );
  
  // Report subscriptions
  UserReport::runReports();
?>
