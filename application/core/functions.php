<?php
  require_once( "core/settings.php" );
  require_once( "core/cache.class.php" );
  
  // Define static, application-wide variables
  define( "DISPLAY_STRING", 1 );
  define( "DISPLAY_HTML", 2 );
  define( "DISPLAY_FIELD", 4 );
  define( "DISPLAY_SEARCH", 8 );
  define( "DISPLAY_FIELDSELECT", 16 );
  define( "DISPLAY_INCLUDE_SEARCH", 32 );
  define( "DISPLAY_INCLUDE_RESULTS", 64 );
  
  $aEventLog = array();
  
  if( !function_exists("sys_getloadavg") ){
    // Add something for windows here
    function sys_getloadavg(){
      return array( 0, 0, 0 );
    }
  }
  if (!function_exists('fnmatch')) {
    /**
     * This is only declared in the case that it does not already exist (does not exist in Win32 builds of PHP)
     */
    function fnmatch($pattern, $string) {
      return @preg_match(
        '/^' . strtr(addcslashes($pattern, '/\\.+^$(){}=!<>|'),
        array('*' => '.*', '?' => '.?')) . '$/i', $string
      );
    }
  }
  
  /**
  * Automagically load class files when needed
  * This is a built-in PHP feature http://uk3.php.net/oop5.autoload
  * @param string name of the class to load
  */
  function __autoload($class_name) {
  
    $class_name = preg_replace( "/[^A-Za-z0-9]/", "", $class_name );
    
    $cwd = getcwd();
    chdir( SITE_COREDIR );
    $filename = "";
    
    // Is this core, field or model?
    // Fields:
    if( preg_match( "/([A-Za-z]{3})Field$/", $class_name, $m ) ){
      if( file_exists( "../core/fields/".strtolower( $m[1] ).".field.class.php" ) ) 
        $filename = "core/fields/".strtolower( $m[1] ).".field.class.php";
    }
    
    // Interfaces
    elseif( preg_match( "/^i(.+)/", $class_name, $m ) ){
      $name = camelToUnderscore( $m[1] );
      if( file_exists( "../core/".$name.".interface.php" ) ){
        $filename = "core/".$name.".interface.php";
      }
    }
    
    // Core
    elseif( file_exists( "../core/".camelToUnderscore( $class_name ).".class.php" ) ){
      $filename = "core/".camelToUnderscore( $class_name ).".class.php";
    }
    
    // Model classes
    elseif( file_exists( "../models/".camelToUnderscore( $class_name ).".model.class.php" ) ){
      $filename = "models/".camelToUnderscore( $class_name ).".model.class.php";
    }
    
    // Report classes
    elseif( preg_match( "/(.+)Report$/", $class_name, $m ) ){
      if( file_exists( "../report/classes/".camelToUnderscore( $m[1] ).".report.class.php" ) ){ 
        $filename = "report/classes/".camelToUnderscore( $m[1] ).".report.class.php";
      }
    }
    
    // Graphs
    elseif( preg_match( "/(.+)Graph$/", $class_name, $m ) ){
      if( file_exists( "../report/classes/".camelToUnderscore( $m[1] ).".graph.class.php" ) ){ 
        $filename = "report/classes/".camelToUnderscore( $m[1] ).".graph.class.php";
      }
    }
    
    // Widgets
    elseif( preg_match( "/(.+)Widget$/", $class_name, $m ) ){
      if( file_exists( "../report/widgets/".camelToUnderscore( $m[1] ).".widget.class.php" ) ){ 
        $filename = "report/widgets/".camelToUnderscore( $m[1] ).".widget.class.php";
      }
    }
    
    // DB classes
    elseif( preg_match( "/(.*)DB$/", $class_name, $m ) ){
      $type = $m[1];
      if( $type != "" ) $type = camelToUnderscore( $type ).".";
      if( file_exists( "../core/".$type."db.class.php" ) ){
        $filename = "core/".$type."db.class.php";
      }
    }
    
    // Wizard classes
    elseif( preg_match( "/(.+)Wizard$/", $class_name, $m ) ){
      if( file_exists( "../wizards/".camelToUnderscore( $m[1] ).".wizard.class.php" ) ){
        $filename = "wizards/".camelToUnderscore( $m[1] ).".wizard.class.php";
      }
    }
    
    // Wizard steps
    elseif( preg_match( "/(.+)WizardStep$/", $class_name, $m ) ){
      if( file_exists( "../wizards/".camelToUnderscore( $m[1] ).".wizard_step.class.php" ) ){
        $filename = "wizards/".camelToUnderscore( $m[1] ).".wizard_step.class.php";
      }
    }
    
    // Connectors
    elseif( preg_match( "/(.+)Connector$/", $class_name, $m ) ){
      if( file_exists( "../connectors/".camelToUnderscore( $m[1] ).".connector.class.php" ) ){
        $filename = "connectors/".camelToUnderscore( $m[1] ).".connector.class.php";
      }
    }
    
    // Misc classes
    elseif( file_exists( "../lib/".camelToUnderscore( $class_name ).".class.php" ) ){
      $filename = "lib/".camelToUnderscore( $class_name ).".class.php";
    }
    
    if( $filename != "" ){ 
      require_once( $filename );
      chdir( $cwd );
      return true;
    }
    chdir( $cwd );
    return false;
  }
  
 
  /**
  * Wait until the CPU load has dropped below a certain point (first figure in /proc/loadavg)
  * @param double $load load to wait for
  * @param int $interval time interval between load checks
  */
  function sleepUntilLoadIsBelow($load,$interval=2){
    do{
      $aLoad = sys_getloadavg();
      sleep($interval);
    }while( $aLoad[0] > $load );
  }
  
  /**
  * Parse a user agent and attempt to determine: 
  * - OS
  * - Browser
  * - version
  */
  function parseUserAgent( $useragent ){
    $rtn = array( 
      "os" => "Unknown",
      "browser" => "",
      "version" => ""
    );
    
    // Test for windows
    if( preg_match( "/windows ([^;\)\(]+)/i", $useragent, $m ) ){
      if( $m[1] == "CE" ) $rtn["os"] = $m[0];
      else{
        $rtn["os"] = "Windows ";
        switch( $m[1] ){
          case "NT 5.1":
            $rtn["os"] .= "XP";
            break;
          case "NT 5.2":
            $rtn["os"] .= "Server 2003 or XP 64-bit";
            break;
          case "NT 6.0":
            $rtn["os"] .= "Vista";
            break;
          case "NT 6.1":
            $rtn["os"] .= "7";
            break;
          case "NT 6.2":
            $rtn["os"] .= "8";
            break;
        }
        $rtn["os"] = trim( $rtn["os"] );
      }
    }
    
    // Test for Linux
    elseif( preg_match( "/linux/i", $useragent, $m ) ){
      // Ubuntu version
      if( preg_match( "/ubuntu\/([\.0-9])/i", $useragent, $m ) ){
        $rtn["os"] = "Ubuntu ".$m[1];
      }
      
      // Android version
      elseif( preg_match( "/android ([0-9\.]+)/i", $useragent, $m ) ){
        $rtn["os"] = "Android ".$m[1];
      }else{
        $rtn["os"] = "Linux";
      }
    }
    
    // Test for Mac
    elseif( preg_match( "/macintosh/i", $useragent, $m ) ){
      if( preg_match( "/mac os x ([0-9\._]+)/i", $useragent, $m ) ){
        $ver = str_replace( "_", ".", $m[1] );
        $rtn["os"] = "Mac OS X ".$ver;
      }else{
        $rtn["os"] = "Macintosh";
      }
    }
    
    // iPad / iPhone
    elseif( preg_match( "/(ipod|ipad|iphone);.* os ([0-9\._]+)/i", $useragent, $m ) ){
      $ver = str_replace( "_", ".", $m[2] );
      $rtn["os"] = $m[1]." OS $ver";
    }
    
    // Browsers which fit into the <name>/<version> scheme in descending order of specificity
    $aNames = array(
      "firefox",
      "chrome",
      "safari",
      "applewebkit",
      "webkit",
      "gecko",
      "lynx",
      "links"
    );
    
    // Test for IE
    if( preg_match( "/(ms)ie ([0-9\.]+)/i", $useragent, $m ) ){
      $rtn["browser"] = "Internet Explorer";
      $rtn["version"] = $m[2];
    }
    
    // Test for Firefox, chrome
    else{
      $match = false;
      foreach( $aNames as $name ){
        if( $match ) continue;
        if( preg_match( "/(?<name>$name)(\/(?<ver>[0-9\.]+))?/i", $useragent, $m ) ){
          $rtn["browser"] = $m["name"];
          $rtn["version"] = $m["ver"];
          $match = true;
        }
      }
    }
    
    // Fallback anything/number
    if( !$rtn["browser"] ){
      if( preg_match( "/([^\/]+)\/([0-9\.]*)/i", $useragent, $m ) ){
        $rtn["browser"] = $m[1];
        $rtn["version"] = $m[2];
      }
    }
    
    return $rtn;
  }
  
  /**
  * Return a TSV file as a keyed array
  *
  * Parses a tab-separated values file denoted by $path into an array of the following format:
  * <pre>
  *   $aReturn["headers"] = array( "column name 1", "column name 2" );
  *    $aReturn["rows"] = array(
  *      array( "column name 1" => "row 0 column 1 data", "column name 2" => "row 0 column 2 data" ),
  *      array( "column name 1" => "row 1 column 1 data", "column name 2" => "row 1 column 2 data" )
  *    );</pre>
  */
  function parseTsvFile( $path, $headersonly=false ){
  
    $start = time();
  
    $debug = false;
    if( $debug ) echo "parseTsvFile(\"".$path."\")<br>\n";
    if( !file_exists( $path ) ) return false;
    if( $debug ) echo "File exists<br>\n";
    
    if( filesize( $path ) == 0 ) return false;
    if( $debug ) echo "File size greater than 0<br>\n";
    
    $fp = @fopen( $path, "r" );
    $linecount = 0;
    $aRows = array();
    if( $fp ){
      while( !feof( $fp ) ){
        $line = fgets( $fp, 4096 );
        
        // Get headers
        if( $linecount == 0 ){
          $aHeaders = preg_split( "/\t/", $line ); // OK
          if( sizeof( $aHeaders ) == 0 ) return false;
          if( $debug ) echo "Headers exist<br>\n";
          foreach( $aHeaders as $key => $value ){
            $aHeaders[$key] = trim( $value );
          }
          if( $headersonly ) break;
        }
        
        // Get the rest of the data
        else{
          if( trim( $line ) == "" ) continue;
          $a = preg_split( "/\t/", $line ); // OK
          $aRow = array();
          for( $i=0; $i<sizeof( $aHeaders ); $i++ ){
            if( !isset( $a[$i] ) ) continue;
            $aRow[$aHeaders[$i]] = trim( preg_replace( "/^ *\"|\" *$/", "", trim( $a[$i] ) ) );
          }
          $aRows[] = $aRow;
        }
        $linecount++;
      }
      fclose($fp);
    }else{
      return false;
    }
    $time = time() - $start;
    if( $debug ) echo "Read in entire file - ".sizeof( $aRows )." rows - took ".$time." seconds<br>\n";
  
    for( $i=0; $i < sizeof( $aHeaders ); $i++ ){
      $aHeaders[$i] = trim( $aHeaders[$i] );
    }
    
    $aReturn = array();
    $aReturn["headers"] = $aHeaders;
    $aReturn["rows"] = $aRows;
    return $aReturn;
  } 
  
  /**
  * Return an array of hex colours for a rainbow based on a "colour wheel"
  *
  * @param int $size the number of hex colours to return
  * @param bool $contrast attempt to position colours next to their opposites for highest contrast look
  * @return array List of hex colours
  */
  function getRainbow( $size, $contrast=false, $floor=130, $start_angle=0 ){
    
    // $floor = 130;
    $ceiling = 220;
    $sector = 60;
    // $start_angle = 0;
    
    $aReturn = array();
    
    for( $i=1; $i<=$size; $i++ ){
      $angle = $start_angle + ($i/$size)*360;
      if( $angle > 360 ) $angle-=360;
      $stage = floor( $angle / $sector );
      
      // echo "i: $i, angle: $angle, stage: $stage<br>\n";
      
      switch( $stage ){
      
        // Falling green
        case 6:
        case 0:
          $red = $ceiling;
          $green = $ceiling - ( ( $angle % $sector ) / $sector ) * ( $ceiling - $floor );
          $blue = $floor;
          break;
        
        // Rising blue
        case 1:
          $red = $ceiling;
          $green = $floor;
          $blue =  ( ( $angle % $sector ) / $sector ) * ( $ceiling - $floor ) + $floor;
          break;
        
        // Falling red
        case 2:
          $red = $ceiling - ( ( $angle % $sector ) / $sector ) * ( $ceiling - $floor );
          $green = $floor;
          $blue = $ceiling;
          break;
        
        // Rising green
        case 3:
          $red = $floor;
          $green = ( ( $angle % $sector ) / $sector ) * ( $ceiling - $floor ) + $floor;
          $blue = $ceiling;
          break;
        
        // Falling blue
        case 4:
          $red = $floor;
          $green = $ceiling;
          $blue = $ceiling - ( ( $angle % $sector ) / $sector ) * ( $ceiling - $floor );
          break;
        
        // Rising red
        case 5:
          $red = ( ( $angle % $sector ) / $sector ) * ( $ceiling - $floor ) + $floor;
          $green = $ceiling;
          $blue = $floor;
          break;
      }
      
      $aReturn[] = "#".dechex( $red ).dechex( $green ).dechex( $blue );
    }
    
    // Make the colours sit next to their opposites, if possible
    if( $contrast ){
      $op = round( ( $size ) / 2 );
      for( $i=0; $i<$size-$op; $i+=2 ){
        $tmp = $aReturn[$i];
        $swap = ($i+$op);
        // echo "size: $size, op: $op, i: $i, swap: $swap<br>\n";
        $aReturn[$i] = $aReturn[$swap];
        $aReturn[($i+$op)%$size] = $tmp;
      }
    }
    
    return $aReturn;
  }
  
  
  /**
  * Turn global logging off (v. important for incredibly busy scripts to conserve memory
  */
  function disableLogging(){
    global $page_enablelogging;
    $page_enablelogging = false;
  }
  
  /**
  * Turn global loggin on
  */
  function enableLogging(){
    global $page_enablelogging;
    $page_enablelogging = true;
  }
  
  /**
  * Show the backtrace at the current position
  */
  function showBacktrace(){
    $aBacktrace = debug_backtrace(0);
    array_shift( $aBacktrace );
    $rtn = "<p><strong>Backtrace:</strong></p><ol class=\"backtrace\">";
    foreach( $aBacktrace as $bt ){
      $rtn .= "<li>";
      if( isset( $bt["class"] ) ){
        $rtn .= "<p class=\"method\">".$bt["class"].$bt["type"];
      }
      $rtn .= $bt["function"]."(";
      $c = "";
      foreach( $bt["args"] as $arg ){
        if( is_string( $arg ) ) $rtn .= $c.'"'.$arg.'"';
        elseif( is_array( $arg ) ) $rtn .= $c."Array";
        elseif( is_object( $arg ) ) $rtn .= $c.get_class( $arg );
        else $rtn .= $c.$arg;
        $c = ", ";
      }
      $rtn .= ")</p>";
      $rtn .= "<p class=\"file\">".$bt["file"].":".$bt["line"]."</p>";
      $rtn .= "</li>\n";
    }
    $rtn .= "</ol>";
    return $rtn;
  }
  
  /**
  * Add a log message to the global $aEventLog array
  */
  function addLogMessage( $str, $group="", $flag="" ){
    global $aEventLog, $page_enablelogging;
    // echo $group." ".$str.", ";
    if( $page_enablelogging ){
      $callStack = debug_backtrace();
      $idx = 0;
      // print_r( $callStack );
      $aParentTrace = array();
      
      // Get max id of 'add' debug functions  
      foreach($callStack as $lkey => $lvalue) {
        if( in_array($callStack[$lkey]['function'],  array("addLogMessage") ) == true ){
          $idx = $lkey;
        }else{
          $aParentTrace[] = $lvalue["file"].":".$lvalue["line"];
        }
      }

      $file = !empty($callStack[$idx]['file']) ? $callStack[$idx]['file'] : '';
      $line = !empty($callStack[$idx]['line']) ? $callStack[$idx]['line'] : '';
      $function = !empty($callStack[$idx+1]['function']) ? $callStack[$idx+1]['function'] : '';
      $class = !empty($callStack[$idx+1]['class']) ? $callStack[$idx+1]['class'] : '';
      $type = !empty($callStack[$idx+1]['type']) ? $callStack[$idx+1]['type'] : '';
      $parent = !empty($callStack[$idx+1]['file']) ? $callStack[$idx+1]['file'].":".$callStack[$idx+1]['line'] : '';
      if( $class == "" ) $group = $file;
      else $group = $class.$type.$function;
      $log = array( 
        "time" => microtime(true), 
        "event" => $str, 
        "group" => $group, 
        "flag" => $flag, 
        "file" => $file, 
        "line" => $line, 
        "parent" => $parent,
        "trace" => $aParentTrace
      );
      // print_r( $log );
      $aEventLog[] = $log;
    }
  }
  
  /**
  * Output $aEventLog into a table with columns of Time, &Delta; Time and Event (description)
  */
  function renderLog($onlyerrors=false){
    global $aEventLog, $page_starttime;
    // Set the duration for each item
    $lastkey = -1;
    foreach( $aEventLog as $key => $log ){
      if( isset( $aEventLog[$lastkey] ) ){
        $aEventLog[$lastkey]["duration"] = $log["time"] - $aEventLog[$lastkey]["time"];
      }
      $lastkey = $key;
    }
    $str = "";
    if( sizeof( $aEventLog ) == 0 ) return "";
    $str .= "<ol class=\"event_log\">\n";
    $lasttime = $page_starttime;
    $lastgroup = "";
    $event_id = 0;
    $dropcount = 0;
    $depth = 0;
    foreach( $aEventLog as $event ){
      // $str .= join( " : ", $event )."<br>\n";;
      if( $onlyerrors && $event["flag"] == "" ) continue;
      $event_id++;
      if( $event["event"] == "End" ){
        $lastevent = $event["event"];
        $lasttime = $event["time"];
        if( $event["group"] != "" ) $lastgroup = $event["group"];
        $dropcount++;
        $depth--;
        continue;
      }
      
      if( $lastgroup != $event["group"] ){
        
        if( $lastevent == "End" ){
          // $str .= "Dropping down $dropcount<br>\n";
          for( $i=0; $i<$dropcount; $i++ ){
            // End the group
            $str .= "     </ol>\n";
            $str .= "       </li>\n";
          }
          $dropcount = 0;
        }else{
          $depth++;
        }
        
        // $str .= "Depth: ".$depth."<br>\n";
        
        // Start a new group
        $str .= "   <li class=\"group_name ".$event_id."\"><span>".h($event["group"])."</span>\n";
        $str .= "    <ol class=\"".$event_id."\">\n";
      }elseif( $lastevent == "End" ){
        $dropcount--;
      }
      $time = round( $event["time"] - $page_starttime, 3 );
      $duration = round( $event["duration"], 3 );
      $str .= "       <li class=\"";
      if( $duration > 0.05 ){
        $event["flag"].=" slow";
      }
      if( $event["flag"] != "" ){
        $str .= " ".$event["flag"];
      }else{
        $str .= " ok";
      }
      $str .= "\">".$time." : ".$duration." : <pre>".h($event["event"])."</pre></li>\n";
      $lastevent = $event["event"];
      $lasttime = $event["time"];
      if( $event["group"] != "" ) $lastgroup = $event["group"];
    }
    $str .= "    </ol>\n";
    $str .= "  </li>\n";
    $str .= "</ol>\n";
    return $str;
  }
  
  /**
  * Convert a column name to MS XML friendly name
  */
  function stringToMsXmlTagName( $name ){
    $name = preg_replace( "/a-zA-Z0-9_/", "", str_replace( " ", "_x0020_", $name ) );
    return $name;
  }
  
  /**
  * Determine basic level of access based on session
  *
  * All outwardly visible scripts should call this function at some point to verify user is logged in
  * Script exits or redirects to site root if minimum authorisation is not met
  */
  function globalAuth(){
    $aAllowed = array(
      SITE_ROOT,
      SITE_ROOT."_login.php"
    );
    
    // Check using the right protocol
    if( SITE_PROTOCOL == "https" && ( !isset( $_SERVER["HTTPS"] ) || $_SERVER["HTTPS"] != "on" ) ){
      header( "X-Auth: Using wrong protocol" );
      header( "Location: ".SITE_PROTOCOL."://".$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"] );
      exit;
    }
    
    $ok = false;
    if( !SessionUser::isLoggedIn() ){
    
      /*
      echo "Session not set<br>";
      echo "Requested: ".$_SERVER["REQUEST_URI"]."<br>";
      exit;
      */
      
      foreach( $aAllowed as $page ){
        if( $_SERVER["REQUEST_URI"] == $page ){
          $ok = true;
        }
      }
      if( !$ok ){
        
        // Set the location of the page they were trying to get to in the session
        /*
        echo $_SERVER["REQUEST_URI"];
        exit;
        */
        if( !isset( $_SESSION["redirect"] ) ) $_SESSION["redirect"] = $_SERVER["REQUEST_URI"];
        
      }
    }else{
      $ok = true;
    }
    
    // Check the user isn't trying to go into a model directly via URL arguments
    if( preg_match( "/^.+\?.+$/", $_SERVER["REQUEST_URI"] ) ){
      $ok = false;
    }
    
    if( !$ok ){
      // header( "Location: ".SITE_PROTOCOL."://".$_SERVER["SERVER_NAME"].SITE_ROOT."deadline" );
      header( "X-Auth: No query strings allowed" );
      header( "Location: ".SITE_PROTOCOL."://".$_SERVER["SERVER_NAME"].SITE_ROOT );
      exit;
    }
    
  }
  
  /**
  * Take a string which may possibly be in all-caps and drop it down a peg
  *
  * @param string $s a string which might be in all-caps
  * @return string a string with only the first letters in caps if the original was all-caps
  */
  function stfu( $s ){
  
    // i.e. not a single lower-case character in the entire thing
    if( isAllCaps( $s ) ){
      return ucfirst( strtolower( $s ) );
    }
    return $s;
  }
  
  /**
  * Alias of htmlentities
  * @param string $s string to have all HTML entities converted
  */
  function h( $s ){
    $s = convert_smart_quotes( $s );
    return htmlentities( $s, ENT_QUOTES | ENT_XHTML | ENT_DISALLOWED );
  }
  
  /**
  * Output print_r surrounded by <pre> tags
  */
  function pre_r( $v ){
    echo "<pre>";
    print_r( $v );
    echo "</pre>\n";
  }
  
  /**
  * Replace MS single and double "smart quotes"
  */
  function convert_smart_quotes($string){ 
      $search = array(chr(145), 
                      chr(146), 
                      chr(147), 
                      chr(148), 
                      chr(151),
                      'â€"'
                      ); 

      $replace = array("'", 
                       "'", 
                       '"', 
                       '"', 
                       '-',
                       '...'
                       ); 

      return str_replace($search, $replace, $string); 
  }
  
  /**
  * Does this string have too many exclamation marks?
  * @param string
  * @return bool
  */
  function hasMultiExclamation( $str ){
    return preg_match( "/\!\!+/", $str );
  }
  
  /**
  * Is this string in all caps
  * @param string 
  * @return bool
  */
  function isAllCaps( $str ){
    $str = trim( $str );
    $str = preg_replace( "/[^a-zA-Z]+/", "", $str );
    if( strlen( $str ) == 0 ) return false;
    if( !preg_match( "/[a-z]/", $str ) ){
      return true;
    }else{
      return false;
    }
  }
  
  
  
  /**
  * Return a random string e.g. for temporary passwords
  *
  * @param int $len required length of resulting string
  * @return string a random string
  */
  function randomString( $len=10 ){
    $chars = "123456789ABCDEFGHJKLMNOPQRSTUVWXYZ";
    $str = "";
    for( $i=0; $i<$len; $i++ ){
      $str .= substr( $chars, rand( 0, strlen( $chars )-1 ), 1 );
    }
    return $str;
  }

  // $_GET["startrow"] or $_GET["options"] startrow=...
  /**
  * Get the startrow from URL arguments and total number of rows, if either exist
  *
  * @param int $total The total number of rows in a result set that is to be paged through
  * @return int the suggested start row to pick from a result set
  */
  function getStartRow( $total=0 ){
    $start = 1;
    if( !empty( $_GET["startrow"] ) ) $start = intval( $_GET["startrow"] );
    if( $total > 0 && $start > $total ) $start = 1;
    return $start;
  }

  if( !function_exists( "json_encode" ) ){
    /**
    * Declaration of json_encode if not already in PHP install. Simply calls php2js
    * @see php2js
    */
    function json_encode($s){
      return php2js( $s );
    }
  }

  /**
  * Serialise arbitrary PHP into JSON code 
  * @see json_encode
  * @param var $a Any PHP variable
  * @return string JSON-encoded string
  */
  function php2js($a) {
    if (is_null($a)) return 'null';
    if ($a === false) return 'false';
    if ($a === true) return 'true';
    if (is_scalar($a)) {
       $a = addslashes($a);
       $a = str_replace("\n", '\n', $a);
       $a = str_replace("\r", '\r', $a);
       $a = preg_replace('{(</)(script)}i', "$1'+'$2", $a);
       return "'$a'";
    }
    $isList = true;
    for ($i=0, reset($a); $i<count($a); $i++, next($a))
       if (key($a) !== $i) { $isList = false; break; }
    $result = array();
    if ($isList) {
      foreach ($a as $v) $result[] = php2js($v);
      return '[ ' . join(', ', $result) . ' ]';
    } else {
      foreach ($a as $k=>$v)
         $result[] = php2js($k) . ': ' . php2js($v);
      return '{ ' . join(', ', $result) . ' }';
    }
  }
  
  /**
  * Construct a slashed series of key/value pairs from the $_GET array to create links preserving search arguments
  * @return string Arguments in a slashed URL format
  */
  function constructSearchArgs(){
    $args = "";
    $argv = array();    
    foreach( $_GET as $key => $value ){
      if( !preg_match( "/^(model|action|id|startrow|options|args)$/", $key ) ){
        if( $key == "" ) continue;
        $argv[] = "$key/$value";
      }
    }
    if( sizeof( $argv ) > 0 ) $args = "/".implode( "/", $argv );
    return $args;
  }

  /**
  * Create an HTML control with links to page through search results for the current object
  * @param string $items The item plural noun in question
  * @param int $total Total number of nouns
  * @param int $startrow The row of results to start from
  * @return string HTML of the paging control element
  */
  function renderPaging( $items, $total, $startrow=1 ){
    $html = "";
    
    if( !isset( $_GET["model"] ) ) return "";
    
    $startrow = getStartRow($total);

    // Reconstruct any other args
    $args = h(constructSearchArgs());
    $args .= "#results";
    
    $html .= "      <div class=\"paging\">Displaying ".h($items)." <var class=\"start\">$startrow</var> to <var class=\"end\">".min( SITE_PAGING + $startrow - 1, $total )."</var> of <var class=\"total\">$total</var>\n";
    $base = SITE_ROOT.htmlentities( $_GET["model"] )."/startrow/";
    // $startstr = !empty( $_GET["startrow"] ) ? "?startrow=" : "";
    
    // Get number of pages
    if( $total == 0 ) $total_pages = 1;
    else $total_pages = ceil( $total / SITE_PAGING );
    
    // Get current page
    if( $startrow == 0 ) $current_page = 1;
    else $current_page = ceil( $startrow / SITE_PAGING );
    
    // Show first and last links plus 3 pages either side
    if( $total_pages > 1 ) $html .= "        <ul class=\"jumptopage\">\n";
    $start = max( $current_page - 10, 1 );
    if( $total_pages > 1 ) $html .= "          <li>Jump to page:</li>\n";
    if( $total_pages > 1 && $current_page > 1 ) $html .= "          <li class=\"first\"><a href=\"".$base."1".$args."\">First</a></li>\n";
    if( $total_pages > 1 ){
      for( $i=$start; $i<=min( $total_pages, $start + 20 ); $i++ ){
        $startstr = ( $i - 1 ) * SITE_PAGING + 1;
        $class = "";
        if( $i == $current_page ) $class .= " current_page";
        $html .= "          <li class=\"".$class."\">";
        if( $i == $current_page ) $html .= $i;
        else $html .= "<a href=\"".$base.$startstr.$args."\">".$i."</a>";
        $html .= "</li>\n";
      }
    }
    if( $total_pages > 1 && $current_page < ( $total_pages - 1 ) ){ 
      $last_startrow = ( $total_pages - 1 ) * SITE_PAGING + 1;
      $html .= "          <li class=\"last\"><a href=\"".$base.$last_startrow.$args."\">Last</a></li>\n";
    }
    if( $total_pages > 1 ) $html .= "        </ul>\n";
    
    /*
    $startstr = "";
    $prev = $startrow > 1 ? "<a href=\"".$base.$startstr.max( 1, $startrow - SITE_PAGING )."$args\">Previous</a>" : "&nbsp;";
    if( ( $startrow > 1 ) || ( $total > ( $startrow + SITE_PAGING  - 1 ) ) ){ 
      $html .= "        <ul>\n";
      $html .= "          <li class=\"prev\">$prev</li>\n";
    }
    if( $total > ( $startrow + SITE_PAGING - 1 ) ) {
      $html .= "          <li class=\"next\"><a href=\"".$base.$startstr.min( $total, $startrow + SITE_PAGING )."$args\">Next</a></li>\n";
    }
    if( ( $startrow > 1 ) || ( $total > ( $startrow + SITE_PAGING ) ) ) $html .= "        </ul>\n";
    */
    $html .= "        <br/>\n";
    $html .= "      </div>\n";
    return $html;
  }
  
  /**
  * Convert a month number into an academic period number
  * @param int $month 
  * @return int period
  */
  function monthNumberToPeriodNumber( $month ){
    $month = intval($month);
    if( $month == 0 ) return 0;
    $month -= SITE_PERIODOFFSET;
    if( $month < 1 ) $month += 12;
    return $month;
  }
  function getCurrentPeriod(){
    return monthNumberToPeriodNumber(date("n"));
  }
  function getCurrentAcademicYear(){
    $current_period = getCurrentPeriod();
    $acad_year = date("Y");
    if( $current_period > 5 ){ 
      $acad_year -= 1;
    }
    return $acad_year;
  }
  
  /**
   * Get list of weekdays (M-F) between two timestamps (inclusive).
   *
   * @param string $from the timestamp to start measuring from
   * @param string $to the timestamp to stop measuring at
   * @param string $normalise whether the time of day should be ignored (forces times to yyyy-mm-ddT00:00:00+00:00)
   * @return int the number of weekdays between the two timestamps
   * @author Matt Harris
   */
  function getWeekdays($from, $to, $normalise=true) {
    $_from = is_int($from) ? $from : strtotime($from);
    $_to   = is_int($to) ? $to : strtotime($to);

    // normalising means partial days are counted as a complete day.
    if ($normalise) {
      $_from = strtotime(date('Y-m-d', $_from));
      $_to = strtotime(date('Y-m-d', $_to));
    }

    $all_days = @range($_from, $_to, 60*60*24);

    if (empty($all_days)) return 0;

    $week_days = array_filter(
      $all_days,
      create_function('$t', '$d = date("w", strtotime("+{$t} seconds", 0)); return !in_array($d, array(0,6));')
    );

    return $week_days;
  }
  
  /**
  * Pass a date string, get the academic period string back
  * @param string $date strtotime compatible date
  * @param bool $includeyear whether to return the year in the period (defaults true)
  * @return string
  */
  function dateToAcademicPeriod( $date, $includeyear=true ){
    $date = strtotime( $date );
    $current_period = monthNumberToPeriodNumber(date("n",$date));
    $acad_year = date("Y",$date);
    if( $current_period > date("n",$date) ){ 
      $acad_year -= 1;
    }
    $period = "";
    if( $includeyear ) $period = $acad_year;
    $period .= str_pad( $current_period, 2, "0", STR_PAD_LEFT );    
    return $period;
  }
  
  /**
  * Take a string of format YYYYPP and turn it into a date
  * @param string $period
  * @return int timestamp
  */
  function academicPeriodToDate( $period ){
    $year = substr( $period, 0, 4 );
    $pnum = substr( $period, -2 );
    $month = $pnum;
    $month += SITE_PERIODOFFSET;
    if( $month > 12 ){
      $year++;
      $month-=12;
    }
    return strtotime( $year."-".$month."-01" );
  }

  /**
  * Based on the current URL and the user's permission status, initiate the current class file and model
  * @return object The initialised object
  */
  function setupModel(){
    
    addLogMessage( "Setting up model from URL", "setupModel" );
    
    if( empty( $_GET ) ) $_GET = $_POST;
    if( empty( $_GET["model"] ) ) return false;
    $model = preg_replace( "/[^a-z_]+/", "", $_GET["model"] );
    
    $model = Cache::getModel( underscoreToCamel( $model ) );
    if( $model === false ){
      header("HTTP/1.0 404 Not Found");
      echo "404";
      exit;
    }
    if( $model->name == "" ){ 
      header( "Location: ".SITE_ROOT );
      exit;
    }
    $model->access = $model->getAuth();
    if( !$model->isAuth() ){
       $loc = isset( $_SESSION["redirect"] ) ? $_SESSION["redirect"] : SITE_ROOT;
      header( "x-auth: not authorised" );
      header( "Location: ".$loc );
      exit;
    }
    addLogMessage( "End", "setupModel" );
    return $model;
  }
  
  /**
  * Load a serialised copy of the current model from the session (used to pass back posted forms which had errors)
  * @param object the model to have values copied over it
  */
  function loadCurrentModel( $model ){
    if( isset( $_SESSION["currentmodel"] ) ){ 
      $aVal = unserialize( $_SESSION["currentmodel"] );
      if( $aVal["id"] == $model->id ){
        foreach( $aVal as $key=>$value ){
          if( array_key_exists( $key, $model->aFields ) ){
            $model->aFields[$key]->value = $value;
          }
        }
      }
      unset( $_SESSION["currentmodel"] );
    }
    return $model;
  }
  
  /**
  * Splits a camel case string into separate words
  * @param string $str The string to split
  * @return string The original string, split with spaces
  * @see camelToUnderscore
  */
  function camelSplit( $str ){
    $a = preg_split( "/([A-Z])/", $str, -1, PREG_SPLIT_DELIM_CAPTURE ); // OK
    $return = "";
    for( $i=0; $i<sizeof( $a ); $i++ ){
      if( $i % 2 != 0 && $i != 0  ){
        $space = " ";
      }else{
        $space = "";
      }
      $return .= $space.$a[$i];
    }
    return trim( $return );
  }
  
  /**
  * Rough guess at what the plural of a noun is supposed to be
  * @param string $noun
  * @return string Plural of $noun
  */
  function plural( $noun, $count=2 ){
    // List of common plural nouns
    $aPluralNouns = array(
      "people",
      "relevance",
      "evidence"
    );
    $a = preg_split( "/\s/", $noun );
    $last = strtolower( array_pop( $a ) );
    if( $count == 1 ) return $noun;
    if( array_search( $last, $aPluralNouns ) !== false ) return $noun;
    if( substr( $noun, -1, 1 ) == "y" ) return substr( $noun, 0, strlen( $noun ) -1 )."ies";
    if( substr( $noun, -1, 1 ) == "s" ) return $noun;
    return $noun."s";
  }
  
  /**
  * Turn an array of strings into "a, b and c" format
  * @return string
  * @param array
  */
  function arrayToSentence( $a ){
    $tot = sizeof( $a );
    $rtn = "";
    $delim = "";
    $i=1;
    foreach( $a as $s ){
      $rtn .= $delim.$s;
      $i++;
      if( $i == $tot ) $delim = " and ";
      else $delim = ", ";
    }
    return $rtn;
  }
  
  /** 
  * Function for changing from camel case to underscore case
  * 
  * Underscore case is referred to as being all lowercase words, separated by underscores
  * @param string $str
  * @return string
  * @see underscoreToCamel
  * @see camelSplit
  */
  /*
  function camelToHungarian( $str ){
    return camelToUnderscore( $str );
  }
  */
  function camelToUnderscore( $str ){
    return strtolower( str_replace( " ", "_", camelSplit( $str ) ) );
  }
  
  /** 
  * Function for changing from underscore case to camel
  * 
  * Underscore case is referred to as being all lowercase words, separated by underscores
  * @param string $str
  * @return string
  * @see camelToUnderscore
  * @see underscoreSplit
  */
  /*
  function hungarianToCamel( $str ){
    return underscoreToCamel( $str );
  }
  */
  function underscoreToCamel( $str ){
    return str_replace( " ", "", underscoreSplit( $str ) );
  }
  
  /**
  * Split underscore case words into separate space-separated words with leading upper case chars
  * @param string $str
  * @return string
  * @see underscoreToCamel
  * @see camelSplit
  */
  /*
  function hungarianSplit( $str ){
    return underscoreSplit( $str );
  }
  */
  function underscoreSplit( $str ){
    $a = preg_split( "/_/", $str ); // OK
    $return = "";
    for( $i=0; $i<sizeof( $a ); $i++ ){
      $a[$i] = ucfirst( $a[$i] );
    }
    return implode( " ", $a );
  }
  
  /**
  * Format a filesize like the -h option in "ls"
  * @param int $size a file size in bytes
  * @return string A human-readable formatted filesize
  */
  function formatFilesize($size) {
    $kb = 1024;         // Kilobyte
    $mb = 1024 * $kb;   // Megabyte
    $gb = 1024 * $mb;   // Gigabyte
    $tb = 1024 * $gb;   // Terabyte

    /* If it's less than a kb we just return the size, otherwise we keep going until
    the size is in the appropriate measurement range. */
    if($size < $kb) {
       return $size." B";
    }
    else if($size < $mb) {
       return number_format( round($size/$kb,2), 1 )." KB";
    }
    else if($size < $gb) {
       return number_format( round($size/$mb,2), 1 )." MB";
    }
    else if($size < $tb) {
       return number_format( round($size/$gb,2), 1 )." GB";
    }
    else {
       return number_format( round($size/$tb,2), 1 )." TB";
    }
  }
  
  /**
  * Format a period in time
  * @param int $tt Length of time in seconds to format
  * @param bool $seconds If true, return the period accurate to one second
  * @return string Period in format "x days HH:mm:ss"
  */
  function formatPeriod( $tt, $seconds=false ){
    if( $tt == 0 ) return 0;
    // $days = floor( $tt / ( 60 * 60 * 24 ) );
    $days = 0;
    $hours = floor( ( $tt - ( $days * 60 * 60 * 24 ) ) / ( 60 * 60 ) );
    $mins = str_pad( floor( ( $tt - ( $hours * 60 * 60 ) ) / 60 ), 2, "0", STR_PAD_LEFT );
    $secs = str_pad( floor( ( $tt - ( $hours * 60 * 60 ) - ( $mins * 60 ) ) ), 2, "0", STR_PAD_LEFT );
    
    $str = "";
    if( $days > 0 ) $str .= "$days days ";
    $str .= "$hours:$mins";
    if( $seconds ) $str .= ":".$secs;
    return $str;
  }

  /**
   * Checks if the GET $name variable equals $val
   *
   * @param $name - name of GET variable
   * @param $val - value to compare
   * @returns boolean;
   *
   */
  function checkGetValue($name,$val){
    if (!isset($_GET[$name])) return false;
    if ($_GET[$name] == $val) return true; else return false;
  }
  
  /**
  * Write string to STDERR
  */
  function stderr( $s ){
    fwrite( STDERR, $s );
  }

  /**
  * Create a reply-to address based on what generated it
  */
  function createReplyToAddress( $objectname, $parentcolumn, $parentid, $replyid=0 ){
    $mail = SITE_REPLYTOALIAS.SITE_REPLYTODELIM.$objectname.".".$parentcolumn.".".$parentid;
    if( $replyid > 0 ) $mail .= ".replyto.".$replyid;
    $mail .= "@".SITE_REPLYTOHOST;
    return $mail;
  }

?>
