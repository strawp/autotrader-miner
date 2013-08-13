<?php
  /*
    AUTO-GENERATED CLASS
    Generated 25 Jun 2012 16:18
  */
  require_once( "core/model.class.php" );
  require_once( "core/db.class.php" );

  class Attachment extends Model implements iFeature {
    
    function Attachment(){
      $this->Model( "Attachment" );
      $this->addAuth( "role", "Staff", "cru" );
      $this->addField( Field::create( "fleFile", "uploadFunction=uploadAttachment;displayname=Attach file (max ".ini_get( "upload_max_filesize" ).")" ) );
      $this->addField( Field::create( "htmDownload" ) );
      $this->addField( Field::create( "strName", "required=0" ) );
      $this->addField( Field::create( "strExtension" ) );
      $this->addField( Field::create( "binData" ) );
      $this->addField( Field::create( "sizSize" ) );
      $this->addField( Field::create( "strMimeType", "display=0" ) );
      $this->addField( Field::create( "strModelName" ) );
      $this->addField( Field::create( "intModelId" ) );
      $this->Fields->ModelId->belongsto = "";
      $this->addField( Field::create( "lstCreatedById", "display=1;belongsto=User;listby=first_name,last_name" ) );
      $this->addField( Field::create( "dtmCreatedAt", "display=1;editable=0" ) );
      $this->inits[] = "setVisibility";
      $this->inits[] = "setPrivileges";
      $this->calculations[] = "setHtmFields";
      $this->aResultsFields = array( "download", "name", "size", "created_at", "created_by_id" );
    }
    function getFeatureDescription(){
      return "Allows users to upload attachments to be emailed to PIs and stored alongside a message on the system";
    }
    
    
    /**
    * Custom action to download a file
    * users should only be able to download their own files
    */
    function downloadAction($aArgs){
      $a = Cache::getModel("Attachment");
      $a->get( intval( $aArgs["id"] ) );
      if( !$a->id ) exit;
      if( SessionUser::getId() != $a->Fields->CreatedById->value && !SessionUser::isInGroup("EDIT") ){
        die( "You don't have permission to download this attachment" );
      }
      header( "Content-Type: ".$a->Fields->MimeType->toString() );
      header( 'Content-Disposition: attachment; filename="'.$a->Fields->Name->toString().'"');
      header( "Content-Length: ".$a->Fields->Size->value );
      echo $a->aFields["data"]->toString();
      exit;
    }
    
    /**
    * Set download links
    */
    function setHtmFields(){
      $this->Fields->Download->value = '<a class="download" href="'.SITE_ROOT.'attachment/customAction/download?id='.intval($this->id).'&sessidhash='.SessionUser::getProperty("sessidhash").'">Download file</a>';
    }
    
    /**
    * Set which fields are editable, which records searchable
    * users should only see their own records
    * records should not be editable once uploaded
    */
    function setPrivileges(){
      if( $this->id ){
        foreach( $this->aFields as $k => $f ){
          $this->aFields[$k]->editable = false;
        }
      }
      if( !SessionUser::isAdmin() ){
        switch ($this->getAction()){
          case "search":
            /* show only the logged in user's records */
            $this->getField("created_by_id")->value = SessionUser::getProperty("id");
            $this->getField("created_by_id")->issearchedon = true;
            $this->getField("created_by_id")->display = false;
            $this->getField("created_by_id")->enabled = false;
          break;
        }
      }
    }
    
    // Allow just the file field to be visible on a new file, show everything but the file field on an edit
    function setVisibility(){
      $aFields = array_keys( $this->aFields );
      if( $this->id || $this->action == "search"){
        // Everything except "file"
        $aVisible = array_diff( $aFields, array( "file" ) );
      }else{
        // Just file, model id, model name
        $aVisible = array( "file", "model_id", "model_name" );
      }
      foreach( $aFields as $f ){
        if( !array_key_exists( $f, $this->aFields ) ) continue;
        if( in_array( $f, $aVisible ) ) $this->aFields[$f]->display = true;
        else $this->aFields[$f]->display = false;
      }
      $this->Fields->Data->display = false;
    }
    function renderForm( $action="_action", $method="post", $button="Save", $aColumns=array() ){
      if( $this->getAction() == "new" ) $button = "Attach";
      return parent::renderForm( $action, $method, $button, $aColumns );
    }
    
    function attachmentValidate(){
      if( $this->getAction() == "new" ){
        $aInf = $this->uploadExists( "file" );
        if( !$aInf || $aInf["error"] != 0 || $aInf["size"] == 0 ){
          $this->aErrors[] = array( "message" => "File upload failed" );
          return false;
        }
      }
    }
    
    function uploadAttachment($field){
      $aInf = $this->uploadExists( $field );
      if( !$aInf || $aInf["error"] != 0 ){
        $this->aErrors[] = array( "message" => "File upload failed" );
        return false;
      }
      
      $name = $aInf["name"];
      $name = str_replace( " ", "_", $name );
      $name = preg_replace( "/[^-0-9A-Za-z\._]/", "", $name );
      $this->Fields->Name = $name;
      $aPath = pathinfo( $name );
      $this->Fields->Extension = strtolower( $aPath["extension"] );
      $this->Fields->Size = $aInf["size"];
      $this->Fields->MimeType = $aInf["type"];
      $this->Fields->Data = file_get_contents( $aInf["tmp_name"] );
    }
    
    /**
    * Remove all attachments that don't have model name/id or relate to expired model name / id and are more than 12 hours old
    */
    static function garbageCollect(){
      $dayago = strtotime( "-12 hours" );
      $sql = "DELETE FROM attachment WHERE created_at < ".$dayago." AND ( model_name = '' OR model_name IS NULL OR model_id is null )";
      $db = Cache::getModel("DB");
      $db->query( $sql );
      
      // Get list of all models used
      $sql = "SELECT distinct model_name FROM attachment WHERE model_name <> '' AND created_at < $dayago";
      $db->query( $sql );
      $aModels = array();
      while( $row = $db->fetchRow() ){
        $aModels[] = $row["model_name"];
      }
      
      // Go through each model and make sure the attachments join to something
      $aIds = array();
      foreach( $aModels as $m ){
        $o = Cache::getModel($m);
        $table = $o->tablename;
        $sql = "
          SELECT a.id, ".$db->escape($table).".id as model_id
          FROM attachment a
          LEFT OUTER JOIN ".$db->escape($table)." ON a.model_id = ".$db->escape($table).".id 
          WHERE ".$db->escape($table).".id IS NULL AND a.model_name = '".$db->escape($m)."'
        ";
        $db->query( $sql );
        while( $row = $db->fetchRow() ){        
          $aIds[] = $row["id"];
        }
      }
      if( sizeof( $aIds ) > 0 ){
        $sql = "DELETE FROM attachment WHERE id IN (".join(",",$aIds).")";
        $db->query( $sql );
      }      
    }
  }
?>