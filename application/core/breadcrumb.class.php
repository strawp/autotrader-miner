<?php
  /**
  * Methods for maintaining a trail of the user's history to generate a useful breadcrumb trail
  */
  class Breadcrumb {
    static function init(){
      // $_SESSION["breadcrumb"] = array();
      if( !isset( $_SESSION["breadcrumb"] ) ){
        $_SESSION["breadcrumb"] = array(
          array(
            array(
              "name" => "Home",
              "url" => SITE_ROOT
            )
          )
        );
      }
      /*
      echo "<pre>";
      print_r( $_SESSION["breadcrumb"] );
      echo "</pre>\n";
      */
    }
    
    /**
    * Add a breadcrumb to the trail
    * @param string $name
    * @param string $url
    * @param int $parentid The id of the entry representing the history of the current page
    */
    static function add( $name, $url, $parentid=null ){
      $url = preg_replace( "/[\"']/", "", htmlentities( $url ) );
      $url = preg_replace( "/^https?:\/\//", "", $url );
      $name = htmlentities( $name );
      $bc = array( "name" => $name, "url" => $url );
      if( array_key_exists( $parentid, $_SESSION["breadcrumb"] ) ){
        $hist = $_SESSION["breadcrumb"][$parentid];
        
        // See if the user is just jumping back in history
        $hist2 = array();
        $found = false;
        foreach( $hist as $item ){
          if( $found ) continue;
          if( $item["url"] != $url ){
            $hist2[] = $item;
          }else{
            $found = true;
          }
        }
        $hist = $hist2;
        
        // Avoid duplicating the last entry
        if( isset($hist[sizeof($hist)-1]) && $hist[sizeof($hist)-1]["url"] == $bc["url"] ) return $parentid;
        $hist[] = $bc;
      }else{
      
        // Check it's not just recreating a bunch of page refreshes
        $hist = self::getLastHistoryTrail();
        if( $hist && $hist[sizeof($hist)-1]["url"] == $bc["url"]){
          return sizeof($_SESSION["breadcrumb"])-1;
        }
        $hist = array( $bc );
      }
      $len = sizeof( $_SESSION["breadcrumb"] );
      $_SESSION["breadcrumb"][$len] = $hist;
      return $len;
    }
    
    static function getLastHistoryTrail(){
      if( sizeof( $_SESSION["breadcrumb"] ) == 0 ) return false;
      else return $_SESSION["breadcrumb"][sizeof($_SESSION["breadcrumb"])-1];
    }
    
    
    /**
    * Add by a referer
    * @param string $name
    * @param string $url
    * @param string $referer
    */
    static function addByReferer( $name, $url, $referer ){
      $ref = "/".str_replace( SITE_BASE, "", $referer );
      $parent = self::getParentIdFromUrl( $ref );
      return self::add( $name, $url, $parent );
    }
    
    /**
    * Guess at a parentid from a URL, picks item where the last url matches
    * @param string $referer
    */
    static function getParentIdFromUrl( $url ){
      self::init();
      if( sizeof( $_SESSION["breadcrumb"] ) == 0 ) return null;
      $a = $_SESSION["breadcrumb"];
      /*
      echo "<pre>";
      print_r( $a );
      echo "</pre>\n";
      */
      foreach( $a as $id => $hist ){
        while( $bc = array_pop( $hist ) ){
          if( $bc["url"] == $url ){ 
            return $id;
          }
        }
      }
      return null;
    }
    
    /**
    * Get the history of a page
    */
    static function getHistoryArray( $parentid = null ){
      if( !$parentid ) return array();
      if( !array_key_exists( $parentid, $_SESSION["breadcrumb"] ) ) return array();
      $hist = $_SESSION["breadcrumb"][$parentid];
      return $hist;
    }
    
    /**
    * Get the history as an HTML list
    */
    static function getHistoryHtml( $parentid = null ){
      $hist = self::getHistoryArray( $parentid );
      $html = "";
      // print_r( $hist );
      // Limit to this many items
      $limit = 5;
      $count = 0;
      $size = sizeof( $hist );
      while( $count < $limit ){
        $bc = array_pop( $hist );
        $count++;
        if( !$bc ) continue;
        $html = "  &gt; <a href=\"".$bc["url"]."\">".$bc["name"]."</a>".$html;
        
      }
      /*
      foreach( array( "size", "count", "limit" ) as $a ){
        echo $a.": ".$$a."<br>\n";
      }
      */
      if( $size > $limit && $count >= $limit ){
        $html = " &gt; ...".$html;
      }
      /*
      foreach( $hist as $id => $bc ){
        $html .= "  &gt; <a href=\"".$bc["url"]."\">".$bc["name"]."</a>";
      }
      */
      $html = "<div class=\"breadcrumb\">\nHistory: ".$html."</div>\n";
      return $html;
    }
   
    /**
    * Get the back link for the given parent
    */
    static function getBackLinkItem($parentid = null){
      $hist = self::getHistoryArray( $parentid );
      array_pop( $hist );
      $item = array_pop( $hist );
      
      return $item;
    }
    
    static function getBackLinkItemFromCurrentPage(){
      $parent = self::getParentIdFromUrl( $_SERVER["REQUEST_URI"] );
      return self::getBackLinkItem( $parent );
    }
    
    /**
    * Get the history HTML from the given URL
    */
    static function getHistoryHtmlFromUrl( $url ){
      $parent = self::getParentIdFromUrl( $url );
      return self::getHistoryHtml( $parent );
    }
    
    /**
    * Render from current page
    */
    static function getHistoryFromCurrentPage(){
      return self::getHistoryHtmlFromUrl( $_SERVER["REQUEST_URI"] );
    }
  }
?>