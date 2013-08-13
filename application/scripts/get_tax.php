<?php
  // Get tax
  require( "../core/settings.php" );
  $db = new DB();
  $db->query( "SELECT * FROM car WHERE tax_band_id IS NULL" );
  while( $row = $db->fetchRow() ){
    $car = new Car();
    $car->initFromRow( $row );
    $car->fetchDetails();
    $car->save();
  }
?>
