<?php
  /**
  * Helper class extending PHPMailer with many commonly used settings for this application
  */
  require_once("ext/class.phpmailer.php");
  require_once("../core/settings.php");
  require_once( "core/db.class.php" );
  require_once( "core/flash.class.php" );
  class Mailer extends PHPMailer {

    function Mailer(){
      $this->IsMail();
      $this->From = SITE_FROMADDRESS;
      $this->FromName = SITE_NAME." automated email - DO NOT REPLY";
      $this->IsHTML(false);
      $this->Body    = "";
    }
    
    /**
    * Send using parent class's method but also add a message to the Flash
    */
    function Send(){
      $result = parent::Send();
      if( $result ){
        Flash::addInfo( "An email, \"".$this->getSubject()."\" has been sent to ".$this->getRecipientCount()." recipients" );
      }else{
        Flash::addError( "There was a problem attempting to send the email" );
      }
      return $result;
    }
    
    /**
    * Set the subject, prepend the site name
    * @param string str
    */
    function setSubject( $str ){
      $this->Subject = SITE_NAME.": ".$str;
    }
    
    /**
    * Get the subject
    */
    function getSubject(){
      return $this->Subject;
    }

    /**
    * Use the current logged in user's name and email address as the "From" address
    */
    function setCurrentUserAsSender(){
      if( !SessionUser::isLoggedIn() ) return false;
      $this->From = SessionUser::getProperty("username")."@".SITE_EMAILDOMAIN;
      $this->FromName = SessionUser::getProperty("firstname")." ".SessionUser::getProperty("lastname")." (".SessionUser::getProperty("username").")";
    }
    
    /**
    * Add current logged in user as a recipient
    */
    function addCurrentUserAsRecipient(){
      // $this->AddRecipient( SessionUser::getProperty("firstname")." ".SessionUser::getProperty("lastname")." <".SessionUser::getProperty("username")."@".SITE_EMAILDOMAIN.">" );
      $this->AddRecipient( SessionUser::getProperty("username")."@".SITE_EMAILDOMAIN );
    }
    
    /**
    * Set all users marked as admin on the site as recipients
    */
    function addAdminsAsRecipients(){
      $sql = "SELECT name FROM user WHERE is_admin = 1";
      $db = new DB();
      $db->query( $sql );
      while( $row = $db->fetchRow()){
        $this->AddRecipient($row["name"]);
      }
    }

    /**
    * Extension of PHPMail::AddAddress to pass addresses, usernames or user IDs
    * @param mixed $addr A user ID (int), short username (string) or an email address (string)
    */
    function AddRecipient( $addr ){
      
      // User ID
      if( is_int( $addr ) ){
        $sql = "SELECT name, first_name, last_name, has_left FROM user WHERE id = ".intval($addr);
        $db = new DB();
        $db->query( $sql );
        if( $db->numrows == 0 ) return false;
        $row = $db->fetchRow();
        if( $row["has_left"] == 1 ){ 
          $msg = $row["first_name"]." ".$row["last_name"]." has left, no email will be sent to them";
          Flash::addWarning($msg);
          return false;
        }
        if( $row["name"] == "" ){ 
          $msg = $row["first_name"]." ".$row["last_name"]." does not have a known email address";
          Flash::addWarning($msg);
          return false;
        }
        $this->AddAddress( $row["name"]."@".SITE_EMAILDOMAIN ); // , $row["first_name"]." ".$row["last_name"] );
      }
      
      // User name
      elseif( preg_match( "/^[-a-z0-9\.]+$/", $addr ) ){
        $this->AddAddress( $addr."@".SITE_EMAILDOMAIN );
      }
      
      // Probably an email address? Leave it up to the class/mail server to work out
      else{
        $this->AddAddress( $addr );
      }
    }
    
    /**
    * Get the number of recipients of this mail
    * @return int
    */
    function getRecipientCount(){
      return sizeof( $this->to );
    }
    
    /**
    * Get the sender name/address
    */
    function getSender(){
      $return = "";
      if( $this->FromName != "" ) $return .= $this->FromName." (";
      $return .= $this->From;
      if( $this->FromName != "" ) $return .= ")";
      return $return;
    }
    
    /**
    * Set the sender address
    */
    function setSender($from){
      $this->From = $from;
    }
    
    /**
    * Set up SMTP server login details if needed
    */
    function initSmtpAuth(){
      $this->SMTPAuth = true;
      $this->Username = DOMAIN_USER;
      $this->Password = DOMAIN_PASS;
    }
     
    /**
    * Wrap the message body in SITE_EMAILHEADER and SITE_EMAILFOOTER. Option to also set the body using this method
    * @param string $body Optional text to set the body to. Wraps existing body if left out.
    */
    function wrapBody($body=""){
      if( $body != "" ){
        $this->Body = $body;
      }
      $this->Body = SITE_EMAILHEADER.$this->Body."\n\n".SITE_EMAILFOOTER;
    }

  }
?>
