<?php
  require_once( "core/model.class.php" );

  require_once( "core/db.class.php" );  
  class User extends Model implements iFeature {
  
    function getFeatureDescription(){
      return "Central list of all users and staff for the system";
    }
    function User( $id=0 ){
      $this->Model( "User", $id );
      $this->addField( Field::create( "htmSwitchTo", "displayname=Switch to this user;display=0" ) );
      $this->addField( Field::create( "strName", "displayname=User Name;required=0;unique=1" ) );
      $this->addField( Field::create( "pasPasswordHash", "displayname=Password;display=0" ) );
      $this->addField( Field::create( "htmEmail" ) );
      $this->calculations[] = "setupHtmFields";
      $this->addField( Field::create( "intIdx" ) );
      $this->addField( Field::create( "strFirstName", "index=1" ) );
      $this->addField( Field::create( "strLastName", "index=1" ) );
      $this->addField( Field::create( "strTitle" ) );
      $this->addField( Field::create( "bleIsAdmin" ) );
      $this->addField( Field::create( "intRoleId" ) );
      $show = SessionUser::isInGroup( "EDIT" ) || SessionUser::isAdmin() ? 1 : 0;
      
      // Fuel costs
      $this->addField( Field::create( "intAnnualMileage", "display=1" ) );
      $this->addField( Field::create( "cshPetrolCostPerLitre" ) );
      $this->addField( Field::create( "cshDieselCostPerLitre" ) );
      
      $this->addField( Field::create( "dtmLastLoggedIn", "editable=0" ) );
      $this->addField( Field::create( "bleHasLeft" ) );
      $this->addField( Field::create( "memUserUserGroup", "left=User;right=UserGroup;parent_tablename=".$this->tablename.";parent_displayname=".$this->displayname ) );
      $this->addField( Field::create( "dcmContractualHours", "display=1;displayname=Contractual Hours (weekly)" ) );
      
      $this->addField( Field::create( "dtmCreatedAt", "display=0" ) );
      $this->addField( Field::create( "lstUpdatedById", "belongsto=User;display=1;editable=0;listby=first_name,last_name" ) );
      $this->addField( Field::create( "dtmUpdatedAt", "display=1;editable=0" ) );
      
      $this->addField( Field::create( "chdUserLog", "displayname=Successful logins;display=".$show ) );
      
      $this->listby = "first_name,last_name";
      $this->aSearchFields= array( "first_name", "last_name", "name", "title" );
      $this->aResultsFields = $this->aSearchFields;
      $this->addAuth( "role", "Staff", "r" );
      $this->addAuthGroup( "EDIT", "ru" );
      // $this->access = $this->getAuth();
      $this->allowfieldselect = true;
      $this->allowsearchsummary = true;
      $this->liststyle = "table";
      $this->inits[] = "setVisibleFields";
      $this->inits[] = "setEditableFields";
      $this->aGroups = null;
      if( SessionUser::isInGroup( "EDIT" ) || SessionUser::isAdmin() ){
        $this->aSearchListOptions[] = '<a class="add" href="'.SITE_ROOT.'user_import/">Import people from University directory</a>';
      }
    }
    
    /**
    * Set which users can see what
    */
    function setVisibleFields(){
      if( !SessionUser::isAdmin() ) return;
      if( SITE_AUTH == "db" ){ 
        $this->aFields["password_hash"]->display = true;
      }
    }
    
    /**
    * Set who can edit what
    */
    function setEditableFields(){
      if( $this->action == "search" ) return;
      if( !SessionUser::isAdmin() ){
        foreach( $this->aFields as $key => $field ){
          $this->aFields[$key]->editable = false;
        }
        if( SessionUser::isInGroup( "EDIT" ) ){
          foreach( array( "has_left" ) as $f ){
            $this->aFields[$f]->editable = true;
          }
        }
      }
    }
    
    /**
    * User form override
    */
    function renderForm( $action="_action", $method="post", $button="Save", $aColumns=array() ){
      if( $this->action == "new" && SITE_AUTH == "ldap" ){
        $html = "New users can be imported from the University directory using the <a href=\"".SITE_ROOT."user_import\">Import users area</a>";
      }else{
        $html = parent::renderForm( $action, $method, $button, $aColumns );
      }
      return $html;
    }
    
    /**
    * Sets up the HTML in any htm fields in this model
    *
    * This methods sets up:
    *  - htmEmail to <name>@SITE_EMAILDOMAIN as a mailto anchor
    *  - htmRequestTimesheetAdmin to a link to timesheet_admin/request/<id>
    */
    function setupHtmFields(){
      $email = $this->aFields["name"]->toString()."@".SITE_EMAILDOMAIN;
      $this->aFields["email"]->value = "<a href=\"mailto:".$email."\">".$email."</a>";
      
      // Switch to user URL
      if( SessionUser::isAdmin() ){
        $this->aFields["switch_to"]->value = "<a href=\"".SITE_ROOT."logout/_switch_user/".$this->aFields["name"]->toString()."\">Log in as ".$this->fullName()."</a>";
        $this->aFields["switch_to"]->display = true;
      }
      
    }

    
    // Validate
    function userValidate(){
      
      // You can't delete users
      if( $this->action == "delete" && !SessionUser::isAdmin() ) $this->aErrors[] = array( "message" => "Users can't be deleted" );
      
      // User can only update themselves
      if( $this->id != SessionUser::getId() && !SessionUser::isAdmin() && !SessionUser::isInGroup( "EDIT" ) ){
        $this->aErrors[] = array( "message" => "You can only update your own options" );
      }
      
      // Set up what this person can edit
      // Admin can do everything
      if( SessionUser::isAdmin() ){
        $allowed = array_keys( $this->aFields );
      }else{
        $allowed = array();
      }
      
      // Not allowed to change your name
      $aVals = array();
      foreach( $allowed as $name ){
        $aVals[$name] = $this->aFields[$name]->value;
      }
      $this->retrieve( $this->id );
      foreach( $allowed as $name ){
        $this->aFields[$name]->value = $aVals[$name];
      }
    }
    
    // Get groups this person is a member of
    function getGroups(){
      if( isset( $this->aGroups ) ) return $this->aGroups;
      if( $this->id == 0 ) return false;
      $sql = "
        SELECT ug.*
        FROM user_group ug
        INNER JOIN user_user_group uug ON uug.user_group_id = ug.id AND uug.user_id = ".intval( $this->id )."
      ";
      $db = Cache::getModel( "DB" );
      $db->query( $sql );
      $aGroups = array();
      while( $row = $db->fetchRow() ){
        $aGroups[$row["code"]] = $row;
      }
      $this->aGroups = $aGroups;
      return $this->aGroups;
    }
    
    /**
    * Determine if the current user is in a group
    */
    function isInGroup( $group ){
      $a = $this->getGroups();
      if( !$a ) return false;
      if( isset( $a[$group] ) ) return true;
      return false;
    }
    
    /**
    * Determine if the user is an admin
    */
    function isAdmin(){
      if( $this->id == 0 ) return false;
      if( $this->Fields->IsAdmin->value ) return true;
      return false;
    }
    
    /**
    * Determine if user is a head or associate head of a particular org unit taking into account if the unit is a child of one they are head of
    * @param string $unit_ref
    * @return bool
    */
    function isHeadOfOrgUnit( $unit_ref ){
      $db = new DB();
    
      // Is one of their roles in this (or above) org unit and with the title "head" or "associate head"?
      $sql = "
        SELECT *
        FROM user_position
        WHERE user_id = ".intval( $this->id )." AND head_of_unit = 1
      ";
      $db->query( $sql );
      if( $db->numrows == 0 ) return false;
      
      while( $row = $db->fetchRow() ){
        if( preg_match( "/^".$row["unit_ref"]."/", $unit_ref ) ){
          return true;
        }
      }
      return false;
    }
    
    // Propagate available info from LDAP
    function insertFromLdap($role){
      $ldap = new Ldap();
      $ldap->bindWithApplicationCredentials();
      $aResults = $ldap->search( "OU=".$ldap->escape( $role ), "CN=*".$ldap->escape( $this->aFields["name"]->value )."*" );
      if( sizeof( $aResults ) == 0 || $aResults["count"] == 0 ) return false;
      $this->aFields["first_name"]->value = $aResults[0]["givenname"][0];
      $this->aFields["last_name"]->value = $aResults[0]["sn"][0];
      if( isset( $aResults[0]["title"][0] ) ) $this->aFields["title"]->value = $aResults[0]["title"][0];
      return $this->save();
    }
    
    function fullName(){
      return $this->aFields["first_name"]->value." ".$this->aFields["last_name"]->value;
    }
    
    /**
    * Get email address in "full name <user@domain>" format
    */
    function getFormattedEmailAddress(){
      return $this->fullName()." <".$this->Fields->Name."@".SITE_EMAILDOMAIN.">";
    }
  
    /**
    * Get ajax search result, value == full email address
    */
    function getAjaxResultRow(){
      return array(
        "id" => $this->id,
        "label" => $this->fullName(),
        "value" => $this->getFormattedEmailAddress()
      );
    }
    /**
    * For use in DB user auth. Password hash is 10 char salt followed by hash of salt then password
    */
    function getByCredentials( $username, $password ){
      $db = Cache::getModel("DB");
      $this->retrieveByClause( "WHERE name = '".$db->escape($username)."' AND password_hash = CONCAT( SUBSTR( password_hash, 1, 10 ), SHA2( CONCAT( SUBSTR( password_hash, 1, 10 ), '".$db->escape($password)."' ), 256 ))" );
    }
    
    /**
    * Set up some widgets in this user's dashboard
    */
    function setupDefaultWidgets(){
      
      $aReports = array();
      $db = new DB();
    
      // Leave if the user already has at least one widget
      $sql = "SELECT * FROM user_widget WHERE user_id = ".intval($this->id);
      $db->query( $sql );
      if( $db->numrows > 0 ) return;
      
      // Everyone:
      $aWidgets[] = new DashboardIntroWidget();
      $aWidgets[] = new MyReportsWidget();
      $aWidgets[] = new SearchWidget();
      
      // Sort widgets by priority
      usort( $aWidgets, array( "Widget", "prioritySort" ) );
      
      $aNames = array(); // record names of widgets
      
      foreach( $aWidgets as $i => $w ){
        if( !$w ) continue;
        if( in_array( get_class( $w ), $aNames ) ) continue;
        $aNames[] = get_class( $w );
        $w->user_id = $this->id;
        $w->index = $i+1;
        $w->save();
      }
    }
    /**
    * Default user groups
    */
    function afterCreateTable(){
    
      require_once( "scripts/cli_compat.php" );
      // To run after a model is created on the DB
      $aData = array(
        array( "Admin", "User", "admin", randomstring(20), true )
      );
      
      foreach( $aData as $row ){
        $this->id = 0;
        $this->aFields["first_name"]->value = $row[0];
        $this->aFields["last_name"]->value = $row[1];
        $this->aFields["name"]->value = $row[2];        
        $this->aFields["password_hash"]->set( $row[3] );
        $this->aFields["is_admin"]->value = $row[4];
        echo "Please enter info for ".$row[2].":\n";
        $this->Fields->AnnualMileage = prompt( "Annual mileage (e.g. \"12000\")" );
        $this->Fields->PetrolCostPerLitre = prompt( "Petrol cost per litre (e.g. \"1.40\")" );
        $this->Fields->DieselCostPerLitre = prompt( "Diesel cost per litre (e.g. \"1.40\")" );
        if( $row[4] ) echo "Adding an admin user with username/pass: ".$row[2]."/".$row[3]."\n";
        $this->save();
      }
    }
  }
?>
