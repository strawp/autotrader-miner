<?php

  require_once( "core/model.class.php" );
  require_once( "core/db.class.php" );

  class Issue extends Model implements iFeature {

    function getFeatureDescription(){
      return "Provides an issue tracker interface for users to request features or report system issues";
    }
    
    function Issue(){
      $this->Model( "Issue" );
      $this->addAuth( "role", "Staff", "cru" );
      $this->addField( Field::create( "strSummary", "helphtml=<p>A brief summary of the problem</p>;required=1" ) );
      $this->addField( Field::create( "strUrl", "displayname=URL;required=1" ) );
      $url = Field::create( "htmUrlLink", "displayname=URL;display=0" );
      $url->aUsesFields = array( "url" );
      $this->addField( $url );
      $note = Field::create( "htmNote", "" );
      $note->aUsesFields = array( "url", "summary" );
      $this->addField( $note );
      $this->addField( Field::create( "txtDescription", "helphtml=<p>A more detailed description of the issue</p>;required=1;preservewhitespace=1" ) );
      $this->addField( Field::create( "lstUserId", "listby=first_name,last_name" ) );
      $this->addField( Field::create( "lstRelatedIssueId", "belongsto=Issue;listby=summary" ) );
      $this->addField( Field::create( "lstIssueStatusId" ) );
      $this->addField( Field::create( "lstIssueTypeId" ) );
      $this->addField( Field::create( "lstIssueSystemId", "displayname=System" ) );
      $lst = Field::create( "lstPriority" );
      $lst->listitems = array(
        2 => "Medium",
        3 => "High",
        1 => "Low"
      );
      if( SessionUser::isAdmin() ) $lst->display = true;
      else $lst->display = false;
      $this->addField( $lst );
      $this->addField( Field::create( "dtmDate", "default=now;editable=0" ) );
      $this->addField( Field::create( "dtmDeferredDate" ) );
      $this->addField( Field::create( "strUserAgent", "editable=0;helphtml=This tells me exactly what web browser you are using" ) );
      $this->addField( Field::create( "strRemoteIp", "displayname=Remote IP;editable=0;helphtml=This is where you are logged in from" ) );
      $this->addField( Field::create( "bleActive", "default=1;display=0" ) );
      $this->addField( Field::create( "rptIssueComment" ) );
      $this->addField( Field::create( "chdIssue", "rptlinkobject=issue;linkkey=related_issue_id;displayname=Related issues" ) );
      $this->addField( Field::create( "chdAttachment","displayname=Attachments;linkkey=model_id" ) );
      $this->Fields->Attachment->rptextraclause="AND attachment.model_name = '".get_class($this)."'";
      $this->inits[] = "setPrivileges";
      $this->calculations[] = "setUrlLink";
      $this->calculations[] = "setNoteText";
      $this->calculations[] = "setAttachmentForm";
      $this->listby = "summary";
      $this->allowfieldselect = true;
      $this->allowattachments = true;
      $this->aSearchFields = array( "summary", "url", "user_id", "issue_status_id", "date", "is_active" );
      $this->aResultsFields = $this->aSearchFields;
    }
    
    /**
    * Set Help text based on a guess at what the user is reporting
    */
    function setNoteText(){
      $html = "";
      
      $this->aFields["note"]->editable = true;
      $this->aFields["note"]->value = $html;
    }
    
    /**
    * Set the URL value into a clickable link and hide the URL field if it isn't editable
    */
    function setUrlLink(){
      if( $this->id == 0 ) return;
      $url = $this->aFields["url"]->toString();
      if( $url == "" ) return;
      $url = strip_tags( preg_replace( "/[\"']/", "", $url ) );
      if( !$this->aFields["url"]->editable ) $this->aFields["url"]->display = false;
      $this->aFields["url_link"]->display = true;
      $this->aFields["url_link"]->value = "<a href=\"".$url."\">".$url."</a>";
    }
    
    function renderForm( $action="_action", $method="post", $button="Save", $aColumns=array() ){
      if( $this->action == "new" ) $button = "Send";
      return parent::renderForm( $action, $method, $button, $aColumns );
    }
    
    /**
    * If the status is set to "closed", set inactive
    */
    function issueFinally(){
      $is = Cache::getModel( "IssueStatus" );
      
      // If it's new, set status as such
      if( $this->id == 0 ){
        $is->retrieveByClause( "WHERE code = 'NEW'" );
        $this->aFields["issue_status_id"]->value = $is->id;
      }
      $is->get( $this->aFields["issue_status_id"]->value );
      $this->aFields["active"]->value = $is->aFields["is_archive"]->value == 1 ? 0 : 1;
    }
    
    function setPrivileges(){
      if( $this->id == 0 && $this->action != "search" ){ 
        $this->aFields["user_id"]->set( SessionUser::getId() ); 
        
        // Set the user's browser string
        $this->aFields["user_agent"]->editable = true;
        $this->aFields["user_agent"]->set( $_SERVER["HTTP_USER_AGENT"] ); 
        $this->aFields["user_agent"]->editable = false;
        
        // Where they are accessing this from
        $this->aFields["remote_ip"]->editable = true;
        $this->aFields["remote_ip"]->set( $_SERVER["REMOTE_ADDR"] ); 
        $this->aFields["remote_ip"]->editable = false;
      }
      if( $this->id == 0 ){
        
        if( !SessionUser::isAdmin() ){ 
          $this->access = "cr";
          $this->Fields->RemoteIp->display = false;
          $this->Fields->UserAgent->display = false;
          if( $this->action == "new" ){
            $this->Fields->RelatedIssueId->display = false;
            $this->Fields->IssueStatusId->display = false;
            $this->Fields->DeferredDate->display = false;
          }
        }
        if( $this->action != "search" && !SessionUser::isAdmin() ){
          $this->aFields["user_id"]->set( SessionUser::getId() ); 
          $this->aFields["user_id"]->editable = false;
        }
      }elseif( !SessionUser::isAdmin() ){
        
        // If this reminder isn't set to the person in question, make it read-only      
        $this->access = "r";
        
        // Set uneditable on all fields because it will have already been set when the field was initialised
        foreach( $this->aFields as $key => $f ){
          $this->aFields[$key]->editable = false;
        }
      }
      
      // Status status
      if( $this->action != "search" && !SessionUser::isAdmin() ){
        $this->aFields["issue_status_id"]->editable = false;
      }    
      
      // Related issue
      if( $this->action != "search" && !SessionUser::isAdmin() ){
        $this->aFields["related_issue_id"]->editable = false;
      }    
    }
    
    function issueValidate(){
      $aFields = array( "summary", "description" );
      foreach( $aFields as $k ){
        if( !array_key_exists( $k, $this->aFields ) ) continue;
        $f = $this->aFields[$k];
        
        // Do not allow multiple exclamation marks in issue logs
        if( hasMultiExclamation( $f->toString() ) ){
          $this->aErrors[] = array( 
            "message" => "Finishing sentences with multiple consecutive exclamation marks is very bad English and completely unnecessary. Take a deep breath and proof read your submissions.", 
            "fieldname" => $f->name
          );
        }
        
        // Do not allow all-caps
        if( isAllCaps( $f->toString() ) ){
          $this->aErrors[] = array( 
            "message" => "It's not necessary to write in all-caps. The default computer fonts are perfectly legible as they are.", 
            "fieldname" => $f->name 
          );
        }
      }
    }
    
    
    function issueAfterUpdate(){
      $this->writeIssueCommentItems();
    }
    
    /**
    * After an issue is inserted, send a notification to the site admin
    */
    function issueAfterInsert(){
      $this->emailReport();
    }
    
    /**
    * For any fields that have changed, write a corresponding comment
    */
    function writeIssueCommentItems(){
      if( $this->id == 0 ) return;
      
      $aTrackFields = array( 
        "issue_status_id",
        "issue_type_id",
        "related_issue_id",
        "user_id",
        "summary",
      );
      
      $str = "";
      foreach( $aTrackFields as $f ){
        if( !isset( $this->aFields[$f] ) ) die( "Don't know field ".$f );
        if( $this->aFields[$f]->haschanged ){
          $field = $this->aFields[$f];
          $val = $field->value;
          $field->value = $field->originalvalue;
          $oldstr = $field->toString();
          $field->value = $val;
          if( $field->toString() == $oldstr ) continue;
          $str .= trim( $field->displayname.": ".$oldstr." -> ".$field->toString() ).".\n\n";
        }
      }
      if( $str != "" ){
        require_once( "models/issue_comment.model.class.php" );

        $ic = Cache::getModel( "IssueComment" );
        $ic->aFields["comment"]->set( $str );
        $ic->aFields["issue_id"]->editable = true;
        $ic->aFields["issue_id"]->set( $this->id );
        if( SessionUser::isLoggedIn() ){
          $ic->aFields["user_id"]->set( SessionUser::getId() );
        }
        $ic->save();
      }
      return true;
    }

    function emailReport(){
      $subject = "Site issue: ".str_replace( "\n", "", stfu( $this->aFields["summary"]->toString() ) );
      $body = SessionUser::getFullName()." logged this issue on ".SITE_NAME
        ." at ".date( SITE_DATETIMEFORMAT )."\n";
      $body .= "Details: ".SITE_BASE."issue/edit/".$this->id."\n";
      $body .= "URL: ".$this->aFields["url"]->toString()."\n";
      $body .= "\n---\n\n";
      $body .= $this->aFields["description"]->toString();
      
      require_once( "../lib/mailer.class.php" );
      $mail = new Mailer();
      $mail->wrapBody( $body );
      $mail->setSubject( $subject );
      // $mail->AddRecipient( SITE_ADMINEMAIL );
      $mail->addAdminsAsRecipients();
      $mail->setSender( SITE_FROMADDRESS );
      $mail->FromName = SITE_NAME." issue update";
      $ic = new IssueComment();
      if( $ic->allowemailcreate ) $mail->AddReplyTo( createReplyToAddress( "IssueComment", "issue_id", $this->id ) );
      else $mail->FromName .= " - DO NOT REPLY";
      $rlt = $mail->Send();
    }

    function setAttachmentForm(){
      if( $this->id == 0 ) return;
      // Embed attachment form into attachment child element
      $html = "<form id=\"frmRptAttachments\"><input type=\"hidden\" value=\"\" name=\"attachments\" class=\"attachments\" /></form>";
      $this->Fields->Attachment->customsubheadhtml = $html;
    }

  }

