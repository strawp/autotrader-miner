<?php
  
  require_once( "core/model.class.php" );

  require_once( "db.class.php" );
  abstract class MemberInterface extends Model{
    
    function MemberInterface( $left="", $right="", $id=0 ){
      $this->Model( "MemberInterface", $id );
      $this->listby = "id";
      $this->tablename    = camelToUnderscore( $left.$right );
      
      // Left and right - the two sides of models that get joined together
      $this->left = $left;
      $this->right = $right;
      $this->addField( Field::create( "int".$left."Id" ) );
      $this->leftkey = camelToUnderscore( $left );
      $this->addField( Field::create( "int".$right."Id" ) );
      $this->rightkey = camelToUnderscore( $right );
      $this->returnpage = $this->rightkey;
      $this->context = null;
      $this->partner = null;
      $this->aPartnerIds = array();     // The ids of the table opposite the context that this is joined to
      $this->additionalclause = "";     // Used to filter down list of associated things
      $this->init();
      $this->trackchanges = false;    // Track changes to this association
      $this->trackchangesmodel = "";  // Model to log changes into
    }
    
    function init(){
      $this->setContext();
      $this->setPartner();
      $this->setId();
      $this->setDisplayName();
    }
    
    /** Do a retrieve but in the MemberInterface special way 
    * id represents the context model's id, not the member interface row's id
    */
    function memberRetrieve( $args ){
      $db = Cache::getModel( "DB" );
      if( empty( $args["context"] ) ) return $this->retrieve( $args["id"] );
      $this->setContext( $args["context"] );
      $this->setPartner();
      $this->id = intval( $args["id"] );
      $sql = "
        SELECT *, ".$db->escape( $this->partner )."_id as partner_id
        FROM ".$db->escape( $this->tablename )."
        WHERE ".$db->escape( $this->context )."_id = ".intval( $this->id );
      
      $db->query( $sql );
      while( $row = $db->fetchRow() ){
        $this->aPartnerIds[] = $row["partner_id"];
        foreach( $this->aFields as $k => $f ){
          if( $k == $this->context."_id" || $k == $this->partner."_id" ){
            $v = $row[$k];
          }else{
            $v = $row["partner_id"];
          }
          $this->aFields[$k]->set( $v );
        }
      }
    }
    
    function setDisplayName( $name="" ){
      if( $name != "" ) $this->displayname = $name;
      else $this->displayname = plural( underscoreSplit( $this->partner ) )." associated with this ".underscoreSplit( $this->context );
      addLogMessage( $this->displayname, $this->name."->setDisplayName()" );
      addLogMessage( "End", $this->name."->setDisplayName()" );
    }
    
    function setId( $id=0 ){
      if( $id != 0 ){ 
        $this->id = $id;
        addLogMessage( $this->id, $this->name."->setId()" );
        addLogMessage( "End", $this->name."->setId()" );
        return;
      }
      if( isset( $_GET["id"] ) && intval( $_GET["id"] ) > 0 ) $this->id = intval( $_GET["id"] );
      addLogMessage( $this->id, $this->name."->setId()" );
      addLogMessage( "End", $this->name."->setId()" );
    }
    
    function setContext( $context = "" ){
      if( isset( $_GET["context"] ) ){
        $context = $_GET["context"];
      }
      if( !empty( $context ) ){
        $this->context = preg_replace( "/[^-_a-z0-9]/", "", str_replace( "/", "", $context ));
      }else{
        if( isset( $_GET["model"] ) ) $this->context = preg_replace( "/[^-_a-z0-9]/", "", str_replace( "/", "", $_GET["model"] ));
      }
      addLogMessage( $this->context, $this->name."->setContext()" );
      addLogMessage( "End", $this->name."->setContext()" );
    }
    
    function setPartner(){
      $this->partner = $this->context == $this->leftkey ? $this->rightkey : $this->leftkey;
      addLogMessage( $this->partner, $this->name."->setPartner()" );
      addLogMessage( "End", $this->name."->setPartner()" );
    }
    
    function getAssociated(){
      if( $this->context == "" ) return false;
      $this->setPartner();
      $context = $this->context;
      $this->returnpage = $context."/edit/".$this->id;
      $partner = $this->partner;
      $this->listby= "id";
      // $db = new DB();
      $db = Cache::getModel( "DB" );
      
      $sql = "SELECT $partner.*";
      foreach( $this->aFields as $field ){
        $sql .= ", ".$this->leftkey."_".$this->rightkey.".".$field->columnname;
      }
      $sql .= "
        FROM $partner 
        LEFT OUTER JOIN ".$this->leftkey."_".$this->rightkey." ON ".$partner."_id = ".$partner.".id 
        WHERE ".$context."_id = ".$db->escape( $this->id );
      // $aMembers = $this->getBySQL( $sql );
      $dbr = $this->getBySQL( $sql );
      // echo "<pre>".$dbr->getSummary()."</pre>\n";
      return $dbr;
    }
    
    function getPartnerModel(){
      $p = underscoreToCamel( $this->partner );
      return Cache::getModel( $p );
    }
    function getContextModel(){
      $c = underscoreToCamel( $this->context );
      return Cache::getModel( $c );
    }
    
    function renderFields($aColumns=array(), $modifiers=0){
      addLogMessage( "Start", $this->name."->renderFields()" );
      $this->init();
      $html = "";
      if( $this->context == "" ){ 
        addLogMessage( "no context", $this->name."->renderFields()" );
        addLogMessage( "End", $this->name."->renderFields()" );
        return false;
      }
      $context = $this->context;
      $this->returnpage = $context."/edit/".$this->id;
      
      $partner = $context == $this->leftkey ? $this->rightkey : $this->leftkey;
      $p = $this->getPartnerModel();
      $this->listby= "id";
      $db = Cache::getModel( "DB" );
      
      // Make sure the foreign key fields aren't displayed
      if( isset( $this->aFields[$this->rightkey."_id"] ) ) $this->aFields[$this->rightkey."_id"]->display  = false;
      if( isset( $this->aFields[$this->leftkey."_id"] ) ) $this->aFields[$this->leftkey."_id"]->display  = false;
      
      $sql = "SELECT $partner.*";
      foreach( $this->aFields as $field ){
        $sql .= ", ".$this->tablename.".".$field->columnname;
      }
      $sql .= "
        FROM $partner ";
        if( isset( $this->aClauses["join"] ) ) $sql .= $this->aClauses["join"];
        $sql .= "
        LEFT OUTER JOIN ".$this->tablename." ON ".$this->tablename.".".$partner."_id = ".$partner.".id 
        WHERE ".$this->tablename.".".$context."_id = ".$db->escape( $this->id );
        if( isset( $this->aClauses["where"] ) ) $sql .= $this->aClauses["where"];
        $sql .= "
        UNION 
        SELECT $partner.*";
      for( $i=0; $i<sizeof( $this->aFields ); $i++ ){
        $sql .= ", NULL";
      }
      $sql .= "
        FROM $partner ";
        if( isset( $this->aClauses["join"] ) ) $sql .= $this->aClauses["join"];
        $sql .= "
        WHERE $partner.id  
        NOT IN ( 
          SELECT ".$this->tablename.".".$partner."_id 
          FROM ".$this->tablename." 
          WHERE ".$context."_id = ".$db->escape( $this->id )." 
        )";
        if( isset( $this->aClauses["where"] ) ) $sql .= $this->aClauses["where"];
        $sql .= "
        ORDER BY ".$p->listby."
        ";
      
      // echo $sql;
      
      // $aMembers = $this->getBySQL( $sql );
      $dbr = $this->getBySQL( $sql );
      // echo "<pre>".$dbr->getSummary()."</pre>\n";
      
      if( $dbr->numrows > 0 ){
        $html .= "          <ul class=\"checklist\">\n";
        while( $member = $dbr->fetchRow() ){
          $checked = $member[$context."_id"] == $this->id && $this->id != 0 ? " checked=\"checked\"" : "";
          if( $this->id == 0 && isset($member["is_default"]) && $member["is_default"] == 1 ) $checked = " checked=\"checked\"";
          $class = $checked != "" ? " checked" : "";
          if( $this->aFields > 2 ){
            foreach( $this->aFields as $field ){
              $field->value = $member[$field->columnname];
              if( !$field->display ) continue;
              if( !$field->type == "rdo" ) continue;
              if( $field->value > 0 ) $class .= " ".$field->htmlname;
            }
          }
          foreach( $p->aFields as $k => $f ){
            if( $f->hascolumn ) $p->aFields[$k]->value = $member[$f->columnname];
          }
          $html .= "            <li class=\"".$class."\">\n";
          $html .= "              <input class=\"checkbox\" name=\"chk".$this->left.$this->right."[".$member["id"]."]\" id=\"chk".$this->left.$this->right."_".$member["id"]."\" type=\"checkbox\" value=\"1\"$checked />";
          $html .= "<label for=\"chk".$this->left.$this->right."_".$member["id"]."\">".h($p->getName())."</label> ";
          
          // Other fields to go with this one
          if( $this->aFields > 2 ){
            foreach( $this->aFields as $field ){
              if( !$field->display ) continue;
              $html .= "\n              <div class=\"".$field->type."\">".$field->toField( $member["id"] )."<label for=\"".$field->htmlname."[".$member["id"]."]\">".$field->displayname."</label></div>\n";
            }
          }
          $html .= "            </li>\n";
        }
        $html .= "          </ul>\n";
        // $html .= "        </fieldset>\n";
      }
      addLogMessage( "End", $this->name."->renderFields()" );
      return $html;
    }  
    
    function getForm($aFields=array()){
      $this->aPartnerIds = array();
      if( isset( $_POST["id"] ) ){
        $this->id = $_POST["id"];
      }
      if( isset( $_POST["sessidhash"] ) ) $this->capturedsessidhash = $_POST["sessidhash"];
      $this->context = isset( $_POST["context"] ) ? str_replace( "/", "", $_POST["context"] ) : "";
      foreach( $_POST["chk".$this->left.$this->right] as $key => $value ){
        if( $value == 1 ) $this->aPartnerIds[] = $key;
      }
      foreach( $this->aFields as $field ){
        if( !empty( $_POST[$field->htmlname] ) ){
          $this->aFields[$field->columnname]->getSubmittedValue();
        }
      }
    }

    
    function save(){
      if( !$this->id > 0 || $this->context == "" ) return false;
      $db = new DB();
      
      $context = $this->context;
      $this->setPartner();
      $partner = $this->partner; // $context == $this->leftkey ? $this->rightkey : $this->leftkey;
            
      $this->returnpage = $context;
      
      // Get array of IDs that were in the previous association
      $sql = "SELECT ".$partner."_id FROM ".$this->tablename." WHERE ".$context."_id = ".$db->escape( $this->id );
      $db->query( $sql );
      $aPreviousPartnerIds = array();
      while( $row = $db->fetchRow() ){
        $aPreviousPartnerIds[] = $row[$partner."_id"];
      }

      // Work out the differences between previous and current
      $aRemovedIds = array_diff( $aPreviousPartnerIds, $this->aPartnerIds );
      $aAddedIds = array_diff( $this->aPartnerIds, $aPreviousPartnerIds );
      $aKeptIds = array_intersect( $this->aPartnerIds, $aPreviousPartnerIds );

      // Delete existing associations
      $sql = "DELETE FROM ".$this->tablename." WHERE ".$context."_id = ".$db->escape( $this->id ); // AND $partner."_id" IN ( join( ",", $aRemovedIds ) )
      // echo $sql."<br>";
      $db->query( $sql );
      
      // Fields to return in JSON
      $aFields = array();
      
      // Update existing
      /*
      Possibility of updating only that which have remained, but possibly changed. Potentially actually slower than delete + one big insert though?
      $aData = array();
      if( sizeof( $aKeptIds ) ){
        $sql = "UPDATE ".$this->tablename." SET ";
        foreach( $this->aFields as $field ){
          if( $field->columnname == $context."_id" || $field->columnname == $partner."_id" ) continue;
          $aData[] = $field->columnname." = ".$db->escape( 
          
        }
      }
      */
      
      // Insert additional associations
      if( sizeof( $this->aPartnerIds ) > 0 ){
        
        $sql = "INSERT INTO ".$this->tablename." ( ".$context."_id, ".$partner."_id";
        foreach( $this->aFields as $field ){
          if( $field->columnname == $context."_id" || $field->columnname == $partner."_id" ) continue;
          $sql .= ", ".$field->columnname;
        }
        $sql .= " ) VALUES";
        $comma = "";
        foreach( $this->aPartnerIds as $id ){
          $sql .= $comma." ( ".$db->escape( $this->id ).", ".$db->escape( $id );
          $aFields[$id] = array();
          foreach( $this->aFields as $field ){
            if( $field->columnname == $context."_id" || $field->columnname == $partner."_id" ) continue;
            
            // Merely storing the association or values with that association?
            if( is_array( $field->value ) ){
              
              // Store the value in the value for that key if it exists
              $f = Field::create( $field->name );
              if( isset( $field->value[$id] ) ){
                $f->set( $field->value[$id] );
              }else{
                $f->value = "";
              }
              $aFields[$id][] = array( 
                "column" => $field->columnname,
                "name" => $field->displayname,
                "value" => $f->value,
                "string" => $f->toString()
              );
              $sql .= ", ".$f->getDBString( false, false, true );
              
            }else{
              if( $field->value == $id ){
                $sql .= ", 1";
              }else{
                $sql .= ", 0";
              }
            }
          }
          $sql .= " )";
          $comma = ",";
        }
        $db->query( $sql );
      }
      
   
      /*
      print_r( $aPreviousPartnerIds );
      print_r( $this->aPartnerIds );
      print_r( $aRemovedIds );
      print_r( $aAddedIds );
      */
      
      if( $this->trackchanges ) $log = Cache::getModel($this->trackchangesmodel);
      $c = Cache::getModel( underscoreToCamel( $context ) );
      // $c->retrieve( $this->id );
      $p = Cache::getModel( underscoreToCamel( $partner ) );
      $members = array();
      foreach( $this->aPartnerIds as $id ){
        $class = "";
        $p->retrieve( $id );
        $m = array( "name" => $p->getName() );
        if( isset( $aFields[$id] ) ) $m["fields"] = $aFields[$id];
        foreach( $this->aFields as $k => $f ){
          if( !$f->display ) continue;
          if( $f->columnname == "name" ) continue;
          if( $f->value == $id ) $class .= $f->columnname." ";
        }
        $m["classstr"] = $class;
        $members[] = $m;
        
        // Create a log entry for this added item
        if( $this->trackchanges && array_search( $id, $aAddedIds ) !== false ){          
          if( is_object( $log ) ){
            $log->id = 0;
            $log->aFields["date"]->value = time();
            $log->aFields["name"]->set( $p->displayname." changed, ".$p->getName()." added" );
            $log->aFields["to_str"]->value = $p->getName();
            $log->aFields["to_int"]->value = $id;
            $log->aFields["column_name"]->value = $this->tablename;
            $log->aFields["user_id"]->value = SessionUser::getId();
            if( isset( $log->aFields[$this->rightkey."_id"] ) ) $log->aFields[$this->rightkey."_id"]->value = $this->id;
            if( isset( $log->aFields[$this->leftkey."_id"] ) ) $log->aFields[$this->leftkey."_id"]->value = $this->id;
            $log->save();
          }
        }
      }
      
      if( $this->trackchanges ){
        $log = Cache::getModel($this->trackchangesmodel);
        if( is_object( $log ) ){
        
          // Log entries for removed items
          foreach( $aRemovedIds as $id ){
            $p->retrieve( $id );
            $log->id = 0;
            $log->aFields["date"]->value = time();
            $log->aFields["name"]->set( $p->displayname." changed, ".$p->getName()." removed" );
            $log->aFields["from_str"]->value = $p->getName();
            $log->aFields["from_int"]->value = $id;
            $log->aFields["column_name"]->value = $this->tablename;
            $log->aFields["user_id"]->value = SessionUser::getId();
            if( isset( $log->aFields[$this->rightkey."_id"] ) ) $log->aFields[$this->rightkey."_id"]->value = $this->id;
            if( isset( $log->aFields[$this->leftkey."_id"] ) ) $log->aFields[$this->leftkey."_id"]->value = $this->id;
            $log->save();
          }
          
          // Log entries for any changed fields
          foreach( $this->aFields as $k => $f ){
            if( $k == $this->context."_id" || $k == $this->partner."_id" ) continue;
            if( $f->haschanged && $f->type == "rdo" ){
              // Original value and current value will represent partner ids, so change is from one partner to another
              // Only radio fields currently supported
              $p = $this->getPartnerModel();
              $p->retrieve( $f->originalvalue );
              $log->aFields["date"]->value = time();
              $log->aFields["from_str"]->value = $p->getName();
              $name = $f->displayname." changed from ".$p->getName()." to ";
              $p->retrieve( $f->value );
              $log->aFields["to_str"]->value = $p->getName();
              $name .= $p->getName();
              $log->id = 0;
              $log->aFields["name"]->set( $name );
              $log->aFields["from_int"]->value = $f->originalvalue;
              $log->aFields["to_int"]->value = $f->value;
              $log->aFields["column_name"]->value = $this->tablename.".".$f->columnname;
              $log->aFields["user_id"]->value = SessionUser::getId();
              if( isset( $log->aFields[$this->rightkey."_id"] ) ) $log->aFields[$this->rightkey."_id"]->value = $this->id;
              if( isset( $log->aFields[$this->leftkey."_id"] ) ) $log->aFields[$this->leftkey."_id"]->value = $this->id;
              $log->save();
            }
          }
        }
      }
      
      Flash::setNotice("Associations between ".$c->displayname." and ".$p->displayname." saved");
      Flash::setByKey("members",$members);
    }    
  }


