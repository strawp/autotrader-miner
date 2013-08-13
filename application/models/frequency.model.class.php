<?php
  /*
    AUTO-GENERATED CLASS
    Generated 18 Nov 2011 15:14
  */
  class Frequency extends Model{
    
    function Frequency(){
      $this->Model( "Frequency" );
      $this->addField( Field::create( "strName" ) );
      $this->addField( Field::create( "strCode" ) );
      $this->addField( Field::create( "strRelativeDate" ) );
      $this->addField( Field::create( "intSize", "afterAddColumnMethod=setSizes" ) );
    }
    
    /**
    * Overwrite the size column with relative size of each row to the others
    */
    function setSizes(){
      $db = Cache::getModel( "DB" );
      $sql = "SELECT * FROM frequency";
      $db->query( $sql );
      $aFreq = array();
      $now = time();
      while( $row = $db->fetchRow() ){
        $row["gap"] = $now - strtotime( $row["relative_date"] );
        $aFreq[] = $row;
      }
        
      usort( $aFreq, array( $this, "freqSort" ) );
      
      $size = 1;
      foreach( $aFreq as $freq ){
        $sql = "UPDATE frequency SET size = $size WHERE id = ".$freq["id"];
        $db->query( $sql );
        $size++;
      }
    }
    function freqSort($a, $b){
      if( $a["gap"] == $b["gap"] ) return 0;
      return $a["gap"] > $b["gap"] ? 1 : -1;
    }
    
    function afterCreateTable(){
      $aData = array(
        array(
          "name" => "Daily",
          "code" => "DAIL",
          "relative_date" => "yesterday",
        ),
        array(
          "name" => "Weekly",
          "code" => "WEEK",
          "relative_date" => "-1 week",
        ),
        array(
          "name" => "Monthly",
          "code" => "MONT",
          "relative_date" => "-1 month",
        ),
        array(
          "name" => "Quarterly",
          "code" => "QUAR",
          "relative_date" => "-3 months",
        ),
        array(
          "name" => "Annually",
          "code" => "ANNU",
          "relative_date" => "-1 year",
        ),
      );
      $db = new DB();

      foreach( $aData as $row ){
        $this->id = 0;
        $this->initFromRow( $row );
        $this->save();
      }    
    }
  }
?>
