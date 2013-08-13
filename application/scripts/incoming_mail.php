#!/usr/bin/php
<?php

  /**
  * Script for postfix to pipe incoming emails to.
  * STDIN should be mime encoded mail
  * in /etc/aliases: "| /var/www/test/scripts/incoming_mail.php"
  */
  $data = file_get_contents("php://stdin");
  chdir( dirname( $_SERVER["PHP_SELF"] ) );
  // file_put_contents( "lastmail.txt", $data );
  require_once( "../core/settings.php" );
  require_once( "lib/ext/MimeMailParser.class.php" );
  $parser = new MimeMailParser();
  $parser->setText( $data );
  $db = new DB();

  // print_r( $parser );
  $alloweddomains = "(".SITE_EMAILDOMAIN.)";

  // Check it's from someone I know about
  $from = $parser->getHeader("from");
  if( !preg_match( "/([^< @]+)@".$alloweddomains."/", $from, $m ) ){
    // Couldn't parse the "from" address
    die("Doesn't appear to be from valid domain");
  }
  $username = $m[1];
  $user = new User();
  if( $m[2] == SITE_EMAILDOMAIN ){
    $user->getByName( $username );
  }else{
    // This isn't a native email domain, look up against external account name
    echo "Looking up external alias ".$username."\n";
    $user->retrieveByClause( "WHERE external_account = '".$db->escape($username)."'" );
  }
  if( $user->id == 0 ) die("Couldn't recognise user \"".$username."\"");
  echo "Found user: ".$user->getName()."\n";

  $subject = $parser->getHeader( "subject" );
  // Check the subject is recognisable
  /*
  if( !preg_match( "/\[([^:]+):([^:]+):(\d+)\]/", $subject, $m ) ) die( "Couldn't recognise the context in subject header" );
  $objectname = $m[1];
  $keyfield = $m[2];
  $parentid = $m[3];
  */
  
  // Get recognisable subject to reply to
  $to = $parser->getHeader( "to" );
  if( !preg_match( "/\+([^\.]+)\.([^\.]+)\.(\d+)(\.replyto\.(\d+))?@/", $to, $m ) ) die( "Couldn't recognise the context in to header" );
  $objectname = $m[1];
  $keyfield = $m[2];
  $parentid = $m[3];
  $replyid = isset( $m[5] ) ? $m[5] : 0;

  // Check the model exists and allows email responses
  $object = new $objectname();
  if( !$object ) die( "Couldn't create $objectname" );
  if( !$object->allowemailcreate ) die( "Object doesn't allow creation by email" );
  
  if( isset( $object->aFields[$object->authorfield] ) ){
    $object->aFields[$object->authorfield]->set( $user->id );
  }

  // Check the target item exists
  if( !isset( $object->aFields[$keyfield] ) ) die( "$keyfield isn't a valid field in $objectname" );
  $object->aFields[$keyfield]->set($parentid);

  // Extract the body text
  $text = $parser->getMessageBody('text');

  // Take everything before "-----Original Message-----"
  $text = preg_replace( "/(.*?)-----Original Message-----.*/sm", "$1", $text);
  
  // Check first bit of text has content
  if( trim( $text ) == "" ) die( "Text has no length" );

  // Init a temporary session for the stupid method to use
  SessionUser::setByUser($user);
  SessionUser::setAuthenticationScheme("email");
    
  // Replying to a specific message
  if( $replyid > 0 ){
    $parentobjectname = preg_replace( "/Log$/", "", $objectname );
    $parentobject = new $parentobjectname();
    $parentobject->retrieve( $parentid );
    $object->retrieve( $replyid );
    
    // Get list of users sent the previous mail, match out <user>@domain, convert to userIDs, others add as CC
    $rcp = $object->aFields["recipients"]->toString();
    $aTo = array();
    $aCC = array();

    if( preg_match_all( "/([^@ <,]+)@([^@ >,]+)/", $rcp, $m ) ){
      for( $i=0; $i<sizeof($m[0]); $i++ ){
        if( preg_match( "/([a-z]+\d+)@".SITE_EMAILDOMAIN."/", $m[0][$i], $mLocal ) ){
          // Look this user up
          $id = DB::fetchOne( "SELECT id FROM user WHERE name = '".$db->escape($mLocal[1])."'" );
          if( $id ) $aTo[] = $id;
          else $aCC[] = $m[0][$i];
        }else $aCC[] = $m[0][$i];
      }
    }
    
    // Add in the person who sent the message you're replying to
    $aTo[] = $object->aFields[$object->authorfield]->value;
    
    // Add in anyone who's CC'd
    $aCC[] = $parser->getHeader("cc");

    $aTo = array_unique( $aTo );
    $aCC = array_unique( $aCC );
   
    $parentobject->emailOwner( 
      $aTo,                       // Array of user IDs
      "",                         // CC An email address string
      join( ", ", $aCC ),         // Multiple email address strings to CC
      $text,                      // The message content
      $subject,                   // Message subject
      true 
    );
  }else{
    echo "Setting ".$object->bodytextfield." as : \"".$text."\"\n";
    $object->aFields[$object->bodytextfield]->set( $text );

    // Write to target
    $object->save();
  }
?>
