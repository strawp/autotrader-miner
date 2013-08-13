<?php
  require_once( "../core/settings.php" );  
  require_once( "core/header.php" );  
  
  echo "      <h2>Site map</h2>\n";
  
  echo $menu->render( "", "site_map", true );
  
  require_once( "core/footer.php" );  
?>