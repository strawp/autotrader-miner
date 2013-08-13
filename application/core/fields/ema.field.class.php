<?php
  require_once( "core/settings.php" );
  require_once( "core/db.class.php" );
  require_once( "core/flash.class.php" );
  require_once( "core/fields/str.field.class.php" );
  class EmaField extends StrField{
    function EmaField( $fieldname, $options="" ){
      $this->StrField( $fieldname, $options );
      $this->regexp="^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$";
      if( $this->type == "ema" ) $this->init();
    }
        
    /**
    * Return the name of the field type
    * @return string Name of the field type
    */
    function getTypeName(){
      return "Email address";
    }

  }
?>
