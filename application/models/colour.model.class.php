<?php
  /*
    AUTO-GENERATED CLASS
    Generated 9 Apr 2012 17:22
  */
  class Colour extends Model{
    
    function Colour(){
      $this->Model( "Colour" );
      $this->addField( Field::create( "strName" ) );
    }
    
    function afterCreateTable(){
      $aColours = null;
      while( !$aColours ){
        echo "Getting colours from autotrader... ";
        $aColours = AutotraderConnector::getColours();
        if( !$aColours ){ 
          echo "failed\n";
        }
      }
      foreach( $aColours as $name ){
        $c = new Colour();
        $c->Fields->Name = trim( $name );
        $c->save();
      }
      echo "done\n";
    }
  }
?>
