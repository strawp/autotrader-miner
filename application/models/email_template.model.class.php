<?php
  
  require_once( "core/model.class.php" );

  class EmailTemplate extends Model implements iFeature {
    
    function getFeatureDescription(){
      return "Maintains a list of email used in the \"log a comment\" email area";
    }
    
    function EmailTemplate( $id=0 ){
      $this->Model( "EmailTemplate", $id );
      $this->addAuthGroup( "REVI", "r" );
      $this->addAuthGroup( "EDIT" );
      $this->addField( Field::create( "strName" ) );
      $this->addField( Field::create( "strForModels", "displayname=Forms this is used on (comma separated)" ) );
      $this->addField( Field::create( "strSubject" ) );
      $html = "<p>Email body should be plain text and accepts field names in square brackets, e.g. [user_id] which will be converted to text. 
        Special fields also include:</p>
        <ul>
          <li>[@current_user] - the name of the person composing the email</li>
          <li>[@date] - the current date</li>
          <li>[@datetime] - the current date/time</li>
          <li>[@site_url] - the site's root URL</li>
          <li>[@id] - the refering object's ID</li>
        </ul>
      ";
      $this->addField( Field::create( "txtBody", "helphtml=".$html ) );
      $this->aResultsFields = array( "name", "subject", "body" );
    }
    
    /**
    * custom action for compiling and returning email text
    */
    function getCompiledTextAction($args=array()){
      $templateid = intval($args["template_id"]);
      $this->get($templateid);
      
      $oname = underscoreToCamel($args["reference"]);
      $oRef = new $oname(intval($args["reference_id"]));
      $oRef->get();
      $text = htmlentities( $this->parseText( $this->aFields["body"]->toString(), $oRef ) );
      $this->aFields["body"]->set( $text );
      return $this->toJson();
    }
    
    function parseText( $text, $oRef = false ){
      $aSpecials = array(
        "current_user" => SessionUser::getFullName(),
        "date" => date( SITE_DATEFORMAT ),
        "datetime" => date( SITE_DATETIMEFORMAT ),
        "site_url" => SITE_BASE,
        "id" => $oRef->id
      );
      
      // Get list of all fields
      if( preg_match_all( "/\[([^]]+)\]/", $text, $m ) ){
        foreach( $m[1] as $field ){
          $value = "";
          
          // Special values
          if( preg_match( "/^@(.+)/", $field, $n ) ){
            if( isset( $aSpecials[$n[1]] ) ) $value = $aSpecials[$n[1]];
          }
          
          // Model field values
          else{
            if( isset( $oRef->aFields[$field] ) ) $value = $oRef->aFields[$field]->toString();
          }
          $value = trim( $value );
          if( $value != "" ) $text = str_replace( "[".$field."]", $value, $text );
        }
      }
      return $text;
    }
    
    function getAvailableTemplatesAction($args=array()){
      $modelname = urldecode($args["for"]);
      $db = new DB();
      $sql = "SELECT id, name FROM email_template WHERE for_models RLIKE '[[:<:]]".$db->escape($modelname)."[[:>:]]'";
      $db->query( $sql );
      $aTemplates = array();
      while( $row = $db->fetchRow() ){
        $aTemplates[] = $row;
      }
      return json_encode( $aTemplates );
    }
  }


?>