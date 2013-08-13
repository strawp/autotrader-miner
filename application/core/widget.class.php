<?php
  require_once( "core/settings.php" );
  class Widget extends Report {
    function __construct(){
      $this->index = 1;           // Order on the dashboard
      $this->user_widget_id = 0;  // id of related user widget row
      $this->user_id = 0;         // id of related user 
      $this->id = preg_replace( "/_widget$/", "", camelToUnderScore(get_class($this)));      // The shortname
      $this->configurable = false;
      $this->width=1;
      
      /**
      * When constructing widget lists for a user, use this to determine what order they're in
      * 1-10: Reserve for system stuff, e.g. intro widget
      * 11-20: High priority - quick access widgets
      * 21+: Everything else
      */
      $this->priority = 99;
    }
    
    /**
    * Render the options available for this widget
    */
    function renderOptions(){
      return "";
    }
    
    /**
    * Available to override to allow a widget to configure visibility based on which users it is appropriate for
    * Used as a filter for available widgets, not for locking down sensitive widgets
    */
    function isVisibleToUser($userid){
      return true;
    }
    
    /**
    * Save this to the UserWidget model
    */
    function save(){
      $uw = Cache::getModel( "UserWidget" );
      if( $this->user_widget_id > 0 ) $uw->get( $this->user_widget_id );
      $uw->Fields->UserId = $this->user_id;
      // print_r( $this->aOptions );
      $uw->Fields->Name = $this->title;
      $uw->Fields->Options = $this->getOptionsString();
      $uw->Fields->Widget = underscoreToCamel( $this->id );
      $uw->Fields->Position = $this->index;
      $uw->Fields->Width = $this->width;
      $uw->save();
      $this->user_widget_id = $uw->id;
    }
    
    /**
    * Delete this UserWidget
    */
    function delete(){
      $uw = Cache::getModel( "UserWidget" );
      if( $this->user_widget_id > 0 ) $uw->get( $this->user_widget_id );
      else {
        Flash::addError( "Could not find specific user's widget" );
        return false;
      }
      $uw->delete();
    }
    static function getAvailable(){
      $dh = opendir( SITE_WEBROOT."/report/widgets" );
      $aWidgets = array();
      while( $file = readdir( $dh ) ){
        if( !preg_match( "/^([a-z_]+)\.widget\.class\.php$/", $file, $m ) ) continue;
        $w = Cache::getModel( underscoreToCamel( $m[1] )."Widget" );
        if( !($w instanceof Widget) ) continue;
        $aWidgets[] = $w;
      }
      return $aWidgets;
    }
    static function prioritySort( $a, $b ){
      if( !($a instanceof Widget) ) return -1;
      if( !($b instanceof Widget) ) return 1;
      if( $a->priority == $b->priority ) return 0;
      return ($a->priority < $b->priority ) ? -1 : 1;
    }
  }
?>