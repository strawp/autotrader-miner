<?php
  /**
  * Rewrite htaccess file and create holding page
  */
  echo "Taking site down...\n";
  require_once( "../core/settings.php" );
  $htaccess_path = "../_htaccess";
  $htaccess_old = file_get_contents( $htaccess_path );

  // htaccess file
  $htaccess_new = "RewriteEngine on

# Everything
RewriteRule ^(.+)$ holding.html

# Forbid TRACE and TRACK methods
RewriteCond %{REQUEST_METHOD} ^(TRACE|TRACK)
RewriteRule .* - [F]
";
  file_put_contents( $htaccess_path, $htaccess_new );
  if( !file_exists( $htaccess_path."_old" ) ) file_put_contents( $htaccess_path."_old", $htaccess_old );
  
  echo "Done.\n";
  
?>