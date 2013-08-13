<?php
  /**
  * Set up risk management
  */
  class PasswordChangeWizardStep extends WizardStep {
    function __construct(){
      parent::__construct();
      $this->name = "Change Password";
      $this->description = "Change your password";
    }
    
    function init( $wiz ){
      parent::init($wiz);
      $this->userid = SessionUser::getId();
      $this->addField(Field::create( "pasPassword" ));
      $this->addField(Field::create( "pasPasswordConfirm", "displayname=Confirm password" ));
      foreach( $this->aFields as $k => $f ){
        $this->aFields[$k]->hashonset = false;
      }
      $this->initFieldAccess();
    }
    
    function save(){
      if( $this->aFields["password"]->value == "" ) return $this->isComplete();
      if( $this->aFields["password_confirm"]->value != $this->aFields["password"]->value ){ 
        Flash::addError( "The two passwords do not match", "password_confirm" );
        return $this->isComplete();
      }
      
      $u = SessionUser::getUser();
      $u->Fields->PasswordHash->set( $this->aFields["password"]->value );
      $u->save();
      
      return $this->isComplete();
    }
    
    /**
    * Has this step already been completed
    */
    function isComplete(){
      $this->complete = false;
      return $this->complete;
    }
  }
?>