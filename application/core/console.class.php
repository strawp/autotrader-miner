<?php
  /**
  * Method library for console / command line interaction
  */
  class Console {
  
    /**
    * Make a string bold using escape characters
    */
    static function bold( $str ){
      return self::ansiEscape( $str, 1 );
    }
    
    /**
    * Wrap a string in escape chars
    */
    static function ansiEscape( $str, $code ){
      if( !self::supportsAnsiEscapeChars() ) return $str;
      return chr(27)."[".$code."m".$str.chr(27)."[0m";
    }
    
    /**
    * Make just the first line of a string bold
    */
    static function boldFirstLine( $str ){
      $a = preg_split( "/\n/", $str );
      $a[0] = self::bold( $a[0] );
      return join( "\n", $a );
    }
    
    /**
    * Test if the current console environment supports ANSI escape chars
    */
    static function supportsAnsiEscapeChars(){
      if( preg_match( "/WINNT/", PHP_OS ) ) return false;
      return true;
    }
  }

?>