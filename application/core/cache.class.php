<?php

/**
* Cache things to avoid having to keep creating them
*/

require_once( "core/settings.php" );
require_once( "core/functions.php" );

class Cache{
  
  public static function init(){
    if (!isset($_SESSION["cache"])) $_SESSION["cache"] = array();
    if (!isset($_SESSION["cache"]["models"])) $_SESSION["cache"]["models"] = array();
    if (!isset($_SESSION["cache"]["wizards"])) $_SESSION["cache"]["wizards"] = array();
  }
  
  /**
  * Clear wizard cache
  */
  public static function flushWizards(){
    Cache::init();
    $_SESSION["cache"]["wizards"] = array();
  }
  
  /**
  * Test if the cache contains a wizard
  * @param $name string the name of the wizard to test for 
  * @param $id int id of the wizard
  * @return bool
  */
  public static function hasWizard( $name, $id ){
    return isset( $_SESSION["cache"]["wizards"][$name][$id] );
  }
  
  /**
  * Flush just one model from Cache
  * @param string the name of the model to flush
  */
  public static function flushWizard( $name ){
    if( isset( $_SESSION["cache"]["wizards"][$name] ) ) unset( $_SESSION["cache"]["wizards"][$name] );
  }
  
  /**
  * Store an instance of a wizard (with things set in it)
  * @param object $model
  */
  public static function storeWizard( $model ){
    $name = get_class( $model );
    if( !isset( $_SESSION["cache"]["wizards"][$name] ) ) $_SESSION["cache"]["wizards"][$name] = array();
    $_SESSION["cache"]["wizards"][$name][$model->id] = serialize( $model );
  }

  /**
  * Create a model or access one from the cache if it exists
  * @return object model
  */
  public static function getWizard( $name, $id ){
    Cache::init();
    
    if( Cache::hasWizard( $name, $id ) ){
      $o = unserialize( $_SESSION["cache"]["wizards"][$name][$id] );
      if( $o->timecreated > SITE_LASTUPDATE || $o->timecreated == 0 ){ 
        if( method_exists( $o, "restore" ) ){
          $o->restore();
        }
        return $o;
      }else{
        addLogMessage( "Cache expired (Created ".date( SITE_DATETIMEFORMAT, $o->timecreated ).", last update ".date( SITE_DATETIMEFORMAT, SITE_LASTUPDATE )."), re-caching", "Cache::getModel()" );
      }
    }else{
      addLogMessage( "Not found in cache", "Cache::getModel()" );
    }
    
    // Not found in cache, create and then cache
    $o = new $name();
    $o->id = $id;
    if( !$o ) return false;
    
    Cache::storeWizard( $o );
    return $o;
  }

  /**
  * Clear model cache
  */
  public static function flushModels(){
    Cache::init();
    $_SESSION["cache"]["models"] = array();
  }

  /**
  * Test if the cache contains a model
  * @param string the name of the model to test for 
  * @return bool
  */
  public static function hasModel( $name ){
    $tablename = camelToUnderscore( $name );
    return isset( $_SESSION["cache"]["models"][$tablename] );
  }
  
  /**
  * Flush just one model from Cache
  * @param string the name of the model to flush
  */
  public static function flushModel( $name ){
    $tablename = camelToUnderscore( $name );
    if( Cache::hasModel( $name ) ){
      unset( $_SESSION["cache"]["models"][$tablename] );
    }
  }
  
  /**
  * Store an instance of a model (with things set in it)
  * @param object $model
  * @param string $name the area of cache to store this object to
  */
  public static function storeModel( $model, $name ){
    addLogMessage( "Storing model to ".$name, "Cache::storeModel()" );
    $name = camelToUnderscore( $name );
    if( !isset( $model->timecreated ) ) return;
    $_SESSION["cache"]["models"][$name] = serialize( $model );
    addLogMessage( "End", "Cache::storeModel()" );
  }
  
  /**
  * Create a model or access one from the cache if it exists
  * @return object model
  */
  public static function getModel( $name ){
    addLogMessage( $name, "Cache::getModel()" );
    
    // No assumption that the model name is correct
    
    $tablename = camelToUnderscore( $name );
    
    Cache::init();
    
    if( Cache::hasModel( $name ) ){
      addLogMessage( "Hit", "Cache::getModel()" );
      $o = unserialize( $_SESSION["cache"]["models"][$tablename] );
      if( !is_object( $o ) ) return false;
      // addLogMessage( $o->timecreated." : ".SITE_LASTUPDATE );
      if( $o->timecreated > SITE_LASTUPDATE || intval($o->timecreated) == 0 ){ 
        if( method_exists( $o, "restore" ) ){
          addLogMessage( "Restoring ".$name, "Cache::getModel()" );
          $o->restore();
        }
        addLogMessage( "End", "Cache::getModel()" );
        return $o;
      }else{
        addLogMessage( "Cache expired (Created ".date( SITE_DATETIMEFORMAT, $o->timecreated ).", last update ".date( SITE_DATETIMEFORMAT, SITE_LASTUPDATE )."), re-caching", "Cache::getModel()" );
      }
    }else{
      addLogMessage( "Not found in cache", "Cache::getModel()" );
    }
    
    // Not found in cache, create and then cache
    if( class_exists( $name ) ){
      try{
        $ref = new ReflectionClass( $name );
        if( $ref->isAbstract() ) return false;
        $o = new $name;
      }
      catch( Exception $e ){
        return false;
      }
    }else{
      return false;
    }
    
    // $_SESSION["cache"]["models"][$tablename] = serialize( $o );
    Cache::storeModel( $o, $tablename );
    addLogMessage( "End", "Cache::getModel()" );
    return $o;
  }
}
