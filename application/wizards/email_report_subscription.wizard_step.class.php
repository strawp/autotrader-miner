<?php
  /**
  * Set up risk management
  */
  class EmailReportSubscriptionWizardStep extends WizardStep {
    function __construct(){
      parent::__construct();
      $this->name = "Email report subscriptions";
      $this->description = "Choose which reports to receive by email";
    }
    
    /*
    * Save the GenJob to QLX
    */
    function save(){
      $aUr = array();
      foreach( $this->aFields as $key => $f ){
        if( array_key_exists( $key, $this->aGroups ) !== false ){
          $code = $this->aGroups[$key];
          $ug = new UserGroup();
          $ug->getByCode( $code );
          if( $f->value ){
            $ug->addUserById( $this->userid );
          }else{
            $ug->removeUserById( $this->userid );
          }
        }
        
        // User reports
        elseif(preg_match("/^user_report_(\d+)/",$key,$m)){
          if( !$f->value ) $aUr[] = $m[1];
        }
      }
      if( sizeof( $aUr ) > 0 ){
        $sql = "
          DELETE
          FROM user_report
          WHERE id IN (".join(",",$aUr).")
            AND user_id = ".SessionUser::getId()."
        ";
        $db = Cache::getModel("DB");
        $db->query( $sql );
      }
      return $this->isComplete();
    }
    
    function init( $wiz ){
      parent::init($wiz);
      $this->userid = SessionUser::getId();
      
      // Add in Personal Report subscriptions
      if( SessionUser::isLoggedIn() ){
        $sql = "
          SELECT *
          FROM user_report
          WHERE user_id = ".intval(SessionUser::getId())."
          AND subscription_type = 'periodic'
        ";
        $db = Cache::getModel( "DB" );
        $db->query( $sql );
        while( $row = $db->fetchRow() ){
          $ur = Cache::getModel( "UserReport" );
          $ur->initFromRow( $row );
          $f = Field::create( "cnfUserReport_".$row["id"] );
          $url = preg_match( "/^\//", $row["url"] ) ? $row["url"] : SITE_ROOT.$row["url"];
          $url = htmlentities( $url );
          $f->displayname = $row["name"];
          $f->appendHTML = "<a href=\"".$url."\">view</a>";
          $f->helphtml = $ur->Fields->FrequencyId." custom ".$ur->Fields->Format." report from ".$ur->Fields->Url;
          if( intval( $row["last_run"] ) > 0 ) $f->helphtml .= ". Last ran ".$ur->Fields->LastRun;
          else $f->helphtml .= ". Has not yet been run";
          $f->value = true;
          $this->addField($f);
        }
      }
      
      // Set field values
      foreach( $this->aFields as $key => $f ){
        if( array_key_exists( $key, $this->aGroups ) !== false ){
          $code = $this->aGroups[$key];
          $ug = new UserGroup();
          $ug->getByCode( $code );
          $this->aFields[$key]->value = $ug->userIsAMember( $this->userid );
        }
      }      
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
    * Inline CSS
    */
    function getInlineCss(){
      return " 
        form.wizard div.cnf {
          width: 100%;
          float: none;
        }
      ";
    }
  }
?>