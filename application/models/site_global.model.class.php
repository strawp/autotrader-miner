<?php
  /*
    AUTO-GENERATED CLASS
    Generated 8 Nov 2012 09:30
  */
  class SiteGlobal extends Model{
    
    function __construct(){
      $this->Model( get_class($this) );
      $this->addField( Field::create( "strName" ) );
      $this->addField( Field::create( "strValue" ) );
    }
    
    function afterCreateTable(){
      $sg = new SiteGlobal();
      $sg->Fields->Name = "LASTUPDATE";
      $sg->Fields->Value = SITE_LASTUPDATE;
      $sg->save();
    }
    
    static function getValueFromName( $name ){
      $sg = new SiteGLobal();
      $sg->getByName($name);
      if( !$sg->id ) return false;
      return $sg->Fields->Value->toString();
    }
  }
?>