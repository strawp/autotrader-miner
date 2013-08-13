<?php
  /**
  * Sets the last update date in core/last_updated_date.php to now
  */
  chdir( dirname( $_SERVER["PHP_SELF"] ) );
  $file = "../core/last_updated_date.php";
  $time = date( "Y-m-d H:i:s" );
  $str = "<?php define( \"SITE_LASTUPDATE\", strtotime( \"".$time."\" ) ); ?>";
  echo "Attempting to write $str to $file\n";
  if( file_put_contents( $file, $str ) ){
    require_once( "../core/settings.php" );
    $sg = new SiteGlobal();
    $sg->getByName( "LASTUPDATE" );
    $sg->Fields->Value = strtotime( $time );
    $sg->save();
    echo "Done\n";
  }else{
    echo "Failed\n";
  }
?>
