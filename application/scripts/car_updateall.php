<?php
  // Retrieve and save all cars
  require_once( "../core/settings.php" );
  $db = new DB();
  $db->query( "SELECT * FROM car where active = 1" );
  $i=1;
  while( $row = $db->fetchRow() ){
    echo "Updating car ".$i."/".$db->numrows."\r";
    $car = new Car();
    $car->initFromRow( $row );
    $car->save();
    $i++;
  }
  echo "\n";
?>
