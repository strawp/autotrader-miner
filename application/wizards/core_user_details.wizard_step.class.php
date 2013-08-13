<?php
  /**
  * Core details of a user
  */
  class CoreUserDetailsWizardStep extends WizardStep {
    function __construct(){
      parent::__construct();
      $this->name = "My Info";
      $this->description = "Check that your basic information is correct";
      
      $this->addField( Field::create( "htmUserPage" ) );
      $this->aRelevantFields = array(
        "name",
        "first_name",
        "last_name",
        "title",
      );
    }
    
    function save(){
      /*
      $u = $this->getUser();
      if( $u->id == 0 ) die( "Can't save zero id" );
      
      foreach( $this->aFields as $column => $field ){
        if( isset( $p->aFields[$column] ) ) $p->aFields[$column]->value = $this->aFields[$column]->value;
      }
      
      $u->save();
      */
      
      return $this->isComplete();
    }
    
    function init( $wiz ){
      parent::init($wiz);
      
      // Add all the relevant fields from the user
      $u = $this->getUser();
      foreach( $this->aRelevantFields as $k ){
        $field = $u->aFields[$k];
        $field->editable = false;
        $this->addField( $field );
      }
      
      // user page link
      $this->aFields["user_page"]->value = "<a href=\"".SITE_ROOT."user/edit/".$u->id."\">view</a>";
      
      $this->initFieldAccess();
    }
    
    /**
    * Has this step already been completed
    */
    function isComplete(){
      $this->complete = false;
      return $this->complete;
    }
    
    /**
    * Get space-separated list of classes to be added to the form class
    */
    function getFormClasses(){
      return " user ";
    }
    
    /**
    * Get the current user
    */
    function getUser(){
      if( !isset( $this->user ) ){
        $u = Cache::getModel( "User" );
        $u->get( SessionUser::getId() );
        $this->user = $u;
      }
      return $u;
    }
    
  }
?>
