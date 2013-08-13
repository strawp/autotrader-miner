<?php
  /*
    AUTO-GENERATED CLASS
    Generated 18 Nov 2011 09:54
  */
  require_once( "core/model.class.php" );
  require_once( "core/db.class.php" );

  class UserWidget extends Model implements iFeature {
    function getFeatureDescription(){
      return "Records which users have which widgets installed on their homescreen dashboards";
    }
    function UserWidget(){
      $this->Model( "UserWidget" );
      $this->displayname = "User's Widget";
      $this->hasinterface = false;
      $this->addField( Field::create( "strName" ) );
      $this->addField( Field::create( "lstUserId" ) );
      $this->addField( Field::create( "strWidget" ) );
      $this->addField( Field::create( "strOptions" ) );
      $this->addField( Field::create( "intPosition" ) );
      $this->addField( Field::create( "intWidth" ) );
    }

    /**
    * Get this instance's widget
    */
    function getWidget(){
      $name = $this->Fields->Widget."Widget";
      $w = Cache::getModel( $name );
      if( !$w ) return false;
      if( !($w instanceof Widget) ) return false;
      $w->setOptionsFromString( $this->Fields->Options->toString() );
      $w->index = intval( $this->Fields->Position->value );
      $w->user_id = intval( $this->Fields->UserId->value );
      // if( (int)$this->Fields->Width->value > 0 ) $w->width = (int)$this->Fields->Width->value;
      $w->user_widget_id = intval( $this->id );
      return $w;
    }
    static function getWidgetsForUser( $userid ){
      $db = Cache::getModel( "DB" );
      $sql = "
        SELECT *
        FROM user_widget
        WHERE user_id = ".intval( $userid )."
        ORDER BY Position asc
      ";
      $db->query( $sql );
      $aWidgets = array();
      $aInvalid = array();
      while( $row = $db->fetchRow() ){
        $uw = Cache::getModel( "Userwidget" );
        $uw->initFromRow( $row );
        $w = $uw->getWidget();
        if( !($w instanceof Widget) ){ 
          $aInvalid[]=$uw;
          continue;
        }
        $aWidgets[] = $w;
      }
      if( sizeof( $aInvalid ) > 0 ){ 
        foreach( $aInvalid as $uw ){
          $sql = "UPDATE user_widget SET position = position -1 WHERE position > ".intval( $uw->Fields->Position->toString() )." AND user_id = ".intval( $userid );
          $db->query( $sql );
          $db->query( "DELETE FROM user_widget WHERE id = ".$uw->id );
        }
      }
      return $aWidgets;
    }
    
    /**
    * Unless a user is an admin, they're only allowed to do anything with widgets assigned to themselves
    */
    function user_widgetValidate(){
      if( SessionUser::isAdmin() ) return true;
      $uid = intval( SessionUser::getId() );
      if( (int)$this->Fields->UserId->value != $uid ){
        $this->aErrors[] = array( "message" => "You can only edit your own dashboard" );
        return false;
      }
      return true;
    }
  }
?>