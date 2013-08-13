<?php
  require_once( "core/model.class.php" );

  class UserImport extends Model{
    
    function UserImport( $id=0 ){
      $this->Model( "UserImport", $id );
      $this->hastable = false;
      $this->displayname = "Import Users";
      $this->addField( Field::create( "strName" ) );
      $this->addField( Field::create( "strFirstName" ) );
      $this->addField( Field::create( "strLastName" ) );
      $this->addField( Field::create( "strTitle" ) );
      $this->aSearchFields = array( "name" );
      $this->aResultsFields = $this->aSearchFields;
      $this->localusers = array();
      $this->addAuthGroup( "REVI", "r" );
      $this->addAuthGroup( "EDIT", "u" );
      $this->access = $this->getAuth();
      $this->listby = "first_name,last_name";
      $this->liststyle = "list";
    }
    
    function getBySearch($limit=''){
      addLogMessage( "Start", $this->name."->getBySearch()" );
      $ldap = new Ldap();
      $ldap->bindWithApplicationCredentials();
      
      if( isset( $_GET["name"] ) && strlen($_GET["name"]) > 2 ){ 
        $ldap->search( "", "CN=*".$ldap->escape(urldecode(urldecode($_GET["name"])))."*", true );
      
        // Look locally for users
        require_once( "models/user.model.class.php" );

        $user = new User();
        $dbr = $user->getBySearch();
        if( $dbr->numrows > 0 ){
          while( $row = $dbr->fetchRow() ){
            $this->localusers[$row["name"]] = $row["id"];
          }
        }
      }
      addLogMessage( "End", $this->name."->getBySearch()" );
      return $ldap;
    }
    
    function renderRow( $row ){
      $html = "";
      $html .= "        <li class=\"".h($row["id"])."\">\n";
      $aFields = array_keys( $row );
      foreach( $this->aFields as $key => $field ){
        if( $field->hascolumn) $this->aFields[$key]->value = $row[$key];
      }
      $html .= "          <h3>".h($this->getName())."</h3>\n";
      foreach( $aFields as $key ){
        if( empty( $this->aFields[$key] ) ) continue;
        if( $key == "name" && $this->listby == "name" ) continue;
        // Don't display the row ID
        if( preg_match( "/^id/", $key ) ) continue;
        if( !$this->aFields[$key]->display ) continue;
        $html .= "          <h4 class=\"".$this->aFields[$key]->type." ".$this->aFields[$key]->columnname." field\">".$this->aFields[$key]->displayname."</h4>\n";
        $html .= "          <p class=\"".$this->aFields[$key]->type." ".$this->aFields[$key]->columnname." field\">".h($this->aFields[$key]->toString())."</p>\n";
      }
      if( array_key_exists( $row["name"], $this->localusers ) ){
        $html .= "          <p class=\"refresh\">";
        $html .= "This user is in ".SITE_NAME.". <a class=\"refresh\" href=\"".SITE_ROOT.$this->tablename."/_import/ldap/".h($row["name"])."\">Reload user info from directory</a>\n";
        $html .= "\t  <a class=\"edit\" href=\"".SITE_ROOT."user/edit/".h($this->localusers[$row["name"]])."\">View this user</a>\n";
        $html .= "\t  </p>\n";
      }else{
        $html .= "          <p class=\"import\"><a class=\"import\" href=\"".SITE_ROOT.$this->tablename."/_import/ldap/".h($row["name"])."\">Import</a></p>\n";
      }
      $html .= "          <hr/>\n";
      $html .= "        </li>\n";
      return $html;
    }    
  }
?>
