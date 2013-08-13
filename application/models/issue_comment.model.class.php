<?php
  /*
    AUTO-GENERATED CLASS
    Generated 30 Jun 2009 13:05
  */
  require_once( "core/model.class.php" );
  require_once( "core/db.class.php" );

  class IssueComment extends Model implements iFeature {
    
    function getFeatureDescription(){
      return "Records comments against issues logged";
    }
    
    function IssueComment(){
      $this->Model( "IssueComment" );
      $this->addAuth( "role", "Staff", "cr" );
      $this->addField( Field::create( "dtmDate", "default=now;editable=0" ) );
      $this->addField( Field::create( "lstIssueId", "listby=summary;required=1" ) );
      $this->addField( Field::create( "lstUserId", "listby=first_name,last_name" ) );
      $this->addField( Field::create( "txtComment", "preservewhitespace=1" ) );
      $this->inits[] = "setPrivileges";
      $this->listby = "date";
      $this->allowemailcreate = false;
      $this->bodytextfield = "comment";
      $this->authorfield = "user_id";
    }
    function setPrivileges(){
      if( $this->id == 0 && $this->action != "search" ){ 
        $this->aFields["user_id"]->set( SessionUser::getId() ); 
      }
      if( $this->id == 0 ){
        
        // Allow all access if it's the user's own reminder
        $this->access = "cr";
        if( $this->action != "search" ){
          $this->aFields["user_id"]->set( SessionUser::getId() ); 
          $this->aFields["user_id"]->editable = false;
        }
      }else{
        
        // If this reminder isn't set to the person in question, make it read-only      
        $this->access = "r";
        
        // Set uneditable on all fields because it will have already been set when the field was initialised
        foreach( $this->aFields as $key => $f ){
          $this->aFields[$key]->editable = false;
        }
      }
    }
    
    /**
    * Final thing to do before saving comment
    * - Set the date to right now
    */
    function issue_commentFinally(){
      $this->aFields["date"]->value = time();
    }
    
    /**
    * Mail a notification to either the admin or the user, depending on which added this comment
    */
    function issue_commentAfterInsert(){
      $body = "The issue \"".trim( $this->aFields["issue_id"]->toString() )."\" ("
        .SITE_BASE."issue/edit/".$this->aFields["issue_id"]->value.") has been updated by "
        .trim( $this->aFields["user_id"]->toString() ).":\n\n\n---\n\n\n";
      
      $body .= $this->aFields["comment"]->toString()."\n\n\n---\n\n";
      $body .= "To respond to this comment, leave another comment at ".SITE_BASE."issue/edit/".$this->aFields["issue_id"]->value."#fragment-1\n\n";
      if( $this->allowemailcreate ) $body .= "Experimental feature: You can also respond directly to the issue comments thread by replying to this email (you may want to remove your signature)";
      require_once( "../lib/mailer.class.php" );
      
      $issue = Cache::getModel( "Issue" );
      $issue->get( $this->aFields["issue_id"]->value );
      
      // Change status to "Report Acknowledged" (ACK) if the status is still "New (NEW)" and user is an admin
      $user = new User();
      $user->get($this->aFields["user_id"]->value);
      if( $user->aFields["is_admin"]->value == 1 ){
        $sql = "SELECT * FROM issue_status WHERE code IN ('ACK','NEW')";
        $db = new DB();
        $db->query( $sql );
        $aStatuses = array();
        while( $row = $db->fetchRow() ){
          $aStatuses[$row["code"]] = $row;
        }
        if( $issue->aFields["issue_status_id"]->value == $aStatuses["NEW"]["id"] ){
          $issue->aFields["issue_status_id"]->set( intval( $aStatuses["ACK"]["id"] ) );
          $issue->save();
        }
      }
      
      $mail = new Mailer();
      $mail->wrapBody( $body );
      $mail->setSubject( "Issue tracker - \"".$this->aFields["issue_id"]->toString()."\" updated" );
      if( SessionUser::getId() != $issue->aFields["user_id"]->value ){
        
        // Send to user
        $mail->AddRecipient( intval( $issue->aFields["user_id"]->value ) );
      }else{
        
        // Send to admin
        $mail->AddRecipient( SITE_ADMINEMAIL );
      }
      
      // Email person that sent the mail if it was mailed in
      if( SessionUser::getAuthenticationScheme() == "email" ){
        $mail->AddRecipient( SessionUser::getId() );
      }
      $mail->setSender( SITE_FROMADDRESS );
      $mail->FromName = SITE_NAME." issue update";
      if( $this->allowemailcreate ) $mail->AddReplyTo( createReplyToAddress( $this->name, "issue_id", $this->aFields["issue_id"]->value ) );
      else $mail->FromName .= " - DO NOT REPLY";
      $rlt = $mail->Send();
    }
  }
?>
