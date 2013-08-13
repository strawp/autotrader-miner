<?php
  /*
    Be very careful with this field. 
    It is not an HTML edit area - it is used to insert unescaped HTML into the form arbitrarily, 
    as such letting user data into this unchecked would open a massive HTML / script injection problem. 
    Only sanitised / pre-defined HTML should go in this field value.
  */
  require_once( "core/settings.php" );
  require_once( "core/db.class.php" );
  require_once( "core/flash.class.php" );
  require_once( "core/field.class.php" );
  class HtmField extends Field{
    function HtmField( $fieldname, $options="" ){
      $this->Field( $fieldname, $options );
      $this->editable = false;
      $this->hascolumn = false;
      $this->length = 500;
      if( $this->type == "htm" ) $this->init();
    }
        
    /**
    * Return the name of the field type
    * @return string Name of the field type
    */
    function getTypeName(){
      return "HTML output";
    }
   
    /**
    * Not escaped HTML in results
    */
    function toResultString( $aData=array() ){
      return $this->toString($aData);
    }

  }
?>
