<?php
  
  require_once( "core/model.class.php" );

  class Test extends Model{
    
    function getFeatureDescription(){
      return "A hidden table used to test data read/write, form rendering etc";
    }
    
    function Test( $id=0 ){
      $this->Model( "Test", $id );
      // $this->addAuth( "role", "Staff" );
      $this->addAuthGroup( "REVI", "r" );
      $this->addAuthGroup( "EDIT", "r" );
      $this->addField( Field::create( "strName" ) );
      $this->addField( Field::create( "bleBoolean" ) );
      $this->addField( Field::create( "cnfConfirm" ) );
      $this->addField( Field::create( "cshCash" ) );
      $this->addField( Field::create( "dteDate" ) );
      $this->addField( Field::create( "dtmDateTime" ) );
      $this->addField( Field::create( "emaEmail" ) );
      $this->addField( Field::create( "fleFileUpload" ) );
      $this->addField( Field::create( "htmHtml" ) );
      $this->addField( Field::create( "intIntField" ) );
      $this->addField( Field::create( "lstYearId" ) );
      $this->addField( Field::create( "lstYearId", "displayname=List" ) );
      $this->addField( Field::create( "pasPassword" ) );
      $this->addField( Field::create( "pasPassword" ) );
      $this->addField( Field::create( "pctPercentage" ) );
      $this->addField( Field::create( "tmeTime" ) );
      $this->addField( Field::create( "tmrTimer" ) );
      $this->addField( Field::create( "txtTextarea", "editable=0" ) );
      /*
        chd.field.class.php
        rpt.field.class.php
        grd.field.class.php
        mem.field.class.php
        chk.field.class.php
        rdo.field.class.php
      */
    }
  }


?>