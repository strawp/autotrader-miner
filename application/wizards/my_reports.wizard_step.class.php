<?php
  /**
  * Set up risk management
  */
  class MyReportsWizardStep extends WizardStep {
    function __construct(){
      parent::__construct();
      $this->name = "My Areas";
      $this->description = "Searches and reports can be bookmarked and retrieved here. Use the \"Options\" drop down on report and search pages to add reports. Uncheck reports you wish remove from this page.";
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
            AND subscription_type = 'bookmark'
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
          $f->appendHTML = "<a href=\"".$url."\" class=\"view\">view</a> <a href=\"".SITE_ROOT."user_report/edit/".intval($row["id"])."\" class=\"edit\">edit</a>";
          $f->value = true;
          $this->addField($f);
        }
      }
      
      $this->initFieldAccess();
    }
    
    function save(){
      $aUr = array();
      foreach( $this->aFields as $key => $f ){
        
        // User reports
        if(preg_match("/^user_report_(\d+)/",$key,$m)){
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
          form.wizard div.cnf label {
            width: 30%;
          }
      ";
    }
  }
?>