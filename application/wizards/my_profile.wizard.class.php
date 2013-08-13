<?php
  /**
  * User's My Profile wizard class
  */
  class MyProfileWizard extends Wizard implements iFeature {
  
    function getFeatureDescription(){
      return "Provides an interface for users to manage which emails and reports they are subscribed to and to check their key info held on the system about them";
    }
    
    function __construct(){
      parent::__construct();
      
      $this->name = "My Profile";
      $this->showsummary = false;
      
      // Core user details
      // $this->addStep( new CoreUserDetailsWizardStep() );
      
      // Email reports subscription step
      $this->addStep( new CoreUserDetailsWizardStep() );
      $this->addStep( new EmailReportSubscriptionWizardStep() );
      $this->addStep( new MyReportsWizardStep() );
      if( SITE_AUTH == "db" ) $this->addStep( new PasswordChangeWizardStep() );
      
    }
    
    function init($args){
      $this->id = SessionUser::getId();
      $this->auth = $this->getAuth();
      parent::init($args);
    }
    
    function getAuth(){
      if( !SessionUser::isLoggedIn() ) return "";
      return "crud";
    }
    
    function render(){
      if( strpos( $this->getAuth(), "r" ) === false ) return "You are not authorised to access this wizard";
      if( $this->id == 0 ) return "you are not logged in";
      return parent::render();
    }
  }
?>