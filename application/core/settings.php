<?php

  /**
  * Exmple settings file
  * You may want to change/simplify as necessary below
  */

  $page_starttime = microtime(true);
  $page_enablelogging = false;
  $query_count = 0;
  $enableldaps = true;
  
  $aPath = preg_split("/\//",str_replace("\\", "/", trim(strtolower(dirname(__FILE__))))); // OK
  array_pop( $aPath );
  $path = join( "/", $aPath );
  set_include_path( get_include_path().PATH_SEPARATOR.$path );
  define( "SITE_WEBROOT", $path );
  define( "SITE_COREDIR", trim(strtolower(dirname(__FILE__))) );
  define( "SITE_TEMPDIR", "/tmp/" );
  define( "SEARCH_POSTCODE", "AB1234C" ); // Change this to your postcode
  define( "AUTOTRADER_DOMAIN", "www2.autotrader.co.uk" );
  define( "AUTOTRADER_BASE", "http://".AUTOTRADER_DOMAIN."/classified/advert/" );
  define( "AUTOTRADER_MOBILE_DOMAIN", "www.autotrader.co.uk" );
  define( "AUTOTRADER_MOBILE_BASE", "http://".AUTOTRADER_MOBILE_DOMAIN."/classified/advert/" );
  define( "AUTOTRADER_MATCH", "/http:\/\/www\d*\.autotrader\.co\.uk\/classified\/advert\/([0-9]+)/" );
  if( !isset( $_SERVER["SERVER_NAME"] ) ) $_SERVER["SERVER_NAME"] = "localhost";
  define( "DESKTOP_USERAGENT", "Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:43.0) Gecko/20100101 Firefox/43.0" );
  define( "MOBILE_USERAGENT", "Mozilla/5.0 (Android 5.1.1; Mobile; rv:45.0) Gecko/45.0 Firefox/45.0" );
  
  // Which installation
  switch( SITE_COREDIR ){
  
    // Default (dev on Windows)
	  default:
      define( "DB_HOST", "localhost" );
      define( "DB_NAME", "cars" );
      define( "DB_USER", "cars" );
      define( "DB_PASS", "cars" );
      
      define( "SITE_ROOT", "/" );
      define( "SITE_PROTOCOL", "http" );
      define( "SITE_TYPE","DEV" );
      define( "LDAP_HOST", "directory.example.com" );
      define( "PROXY_HOST", "" );
      define( "PROXY_PORT", "" );
      define( "SITE_BACKUPDIR", "c:\\backups\\" );
      define( "SITE_ENABLEEXPORTQUEUE", false );
      
      define( "SITE_AUTH", "db" );
      
      // Paths
      define( "PHP_PATH", "/usr/bin/php" );
      define( "WKPDF_PATH", "C:\\wkhtmltopdf\\wkhtmltopdf.exe" );
      define( "MYSQLDUMP_PATH", "/usr/bin/mysqldump" );
      define( "GZIP_PATH", "/bin/gzip" );
      error_reporting( E_ALL & ~E_DEPRECATED ); // & ~E_NOTICE & ~E_STRICT );
      $enableldaps = false;
      break;
  }
  
  putenv("TZ=Europe/London");
  date_default_timezone_set('Europe/London');
  // date_default_timezone_set('GMT');
  
  require_once( "last_updated_date.php" );
  
  define( "SITE_CHARSET", "ISO-8859-1" );
  
  define( "DB_CHARSET", "utf8" );
  define( "DB_ENGINE", "InnoDB" );
  
  /**
  * One site installation can server multiple vhosts, as below
  */
  switch( $_SERVER["SERVER_NAME"] ){
    
    // This could be a cut down UI / different CSS
    case "simple.example.com":
      define( "SITE_DEFAULTHOST", $_SERVER["SERVER_NAME"] );
      define( "SITE_DEFAULTBASE", "https://".SITE_DEFAULTHOST."/" );
      define( "SITE_BASE", SITE_PROTOCOL."://".$_SERVER["SERVER_NAME"].':'.$_SERVER['SERVER_PORT'].SITE_ROOT );
      define( "SITE_REPLYTOHOST", $_SERVER["SERVER_NAME"] );      // Hostname for reply-to address
      define( "SITE_NAME", "Simple intranet" );
      define( "SITE_TAGLINE", "For when you're just fed up looking at all those menus" );
      define( "SITE_BRANDCSS", "simple.css" );
      define( "SITE_INTERFACE", "SIMPLE" );
      break;
    
    default:
      define( "SITE_DEFAULTHOST", "localhost" );
      define( "SITE_DEFAULTBASE", "http://".SITE_DEFAULTHOST.":81/" );

      if( $_SERVER["SERVER_NAME"] == "localhost" ){
        define( "SITE_BASE", SITE_DEFAULTBASE );
        define( "SITE_REPLYTOHOST", SITE_DEFAULTHOST );             // Hostname for reply-to address
      }else{
        define( "SITE_BASE", SITE_PROTOCOL."://".$_SERVER["SERVER_NAME"].SITE_ROOT );
        define( "SITE_REPLYTOHOST", $_SERVER["SERVER_NAME"] );      // Hostname for reply-to address
      }
      define( "SITE_NAME", "Autotrader data miner" );
      define( "SITE_TAGLINE", "All the data" );
      define( "SITE_BRANDCSS", "intranet.css" );
      define( "SITE_INTERFACE", "DEFAULT" );
      break;
  }
  
  /**
  * With DB replication it is possible to offload costly exports to a slave instance of the site which doesn't need to have a public
  * front end. The install location can be specified below.
  */
  switch( SITE_COREDIR ){
    case "/var/www/slave/core":
      define( "SITE_SLAVE", true );
      define( "SITE_MASTERBASE", SITE_DEFAULTBASE );
      break;
      
    default:
      define( "SITE_SLAVE", false );
      define( "SITE_MASTERBASE", SITE_DEFAULTBASE );
      break;
  }
  
  define( "SITE_EMAILHEADER", "" );
  define( "SITE_EMAILFOOTER", "---\nThis is an automated email sent by ".SITE_NAME." (".SITE_BASE.")" );
  define( "SITE_EMAILDOMAIN", "strawp.net" );
  define( "SITE_ADMINEMAIL", "admin@".SITE_EMAILDOMAIN );
  define( "SMTP_HOST", "strawp.net" );
  define( "SITE_FROMADDRESS", "cars@strawp.net" );    // "From" address of all outgoing emails. Preferably somewhere that ignores bounced mail
  define( "SITE_REPLYTOALIAS", "intranet" );                      // A script to handle replied to emails or a person with time on their hands
  define( "SITE_REPLYTODELIM", "+" );                         // Character which separates an alias from the rest of the user name in an email address
  define( "SITE_DATEFORMAT", "j M Y" );
  define( "SITE_DATETIMEFORMAT", "j M Y H:i" );
  define( "SITE_MONTHFORMAT", "F Y" );
  define( "SITE_TIMEFORMAT", "H:i" );
  define( "SITE_PAGING", 15 );
  define( "REPEATER_ROW_LIMIT", 50 );
  
  /**
  * Number of months to offset month number to period number, (month - offset = period ) e.g.
  * 0 = period 1 = January
  * 2 = period 1 = November
  * 7 = period 1 = August
  * month of period 1 - 1 = offset
  */
  define( "SITE_PERIODOFFSET", 7 ); 
  
  // For use in password storage for DB auth
  define( "SITE_SALT", "enter your own random string of characters here" );
  define( "SITE_HASHALGO", "sha256" );
  
  // Cookie params
  ini_set( "session.cookie_secure", SITE_PROTOCOL == "https" );
  ini_set( "session.cookie_httponly", true );
  
  // Domain settings
  if( $enableldaps ){
    define( "LDAP_PROTOCOL", "ldaps" );
    define( "LDAP_PORT", "636" );
  }else{
    define( "LDAP_PROTOCOL", "ldap" );
    define( "LDAP_PORT", "389" );
  }
  define( "LDAP_URL", LDAP_PROTOCOL."://".LDAP_HOST.":".LDAP_PORT );
  
  /**
  * The base for where staff searches are performed
  */
  define( "LDAP_BASE", "OU=Staff,DC=Example,DC=com" );
  
  /**
  * Used is application needs to authenticate on the domain. See the LDAP class for how these are used
  */
  define( "DOMAIN_USER", "intranet" );
  define( "DOMAIN_PASS", "" );
  define( "DOMAIN_NAME", "EXAMPLE" );
  define( "DOMAIN_ROLE", "Servers" );
  define( "DOMAIN_FIRSTNAME", "" );
  define( "DOMAIN_LASTNAME", "" );
  
  require_once( "core/functions.php" );

?>
