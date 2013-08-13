<?php
  /*
    AUTO-GENERATED CLASS
    Generated 6 Apr 2012 02:42
  */
  class TaxBand extends Model{
    
    function TaxBand(){
      $this->Model( "TaxBand" );
      $this->addField( Field::create( "strName" ) );
      $this->addField( Field::create( "cshAnnualCost" ) );
    }

    /**
    * Grab tax info from gov.uk assuming TC48 and TC49 types
    */
    function afterCreateTable(){
      $url = "https://www.gov.uk/vehicle-tax-rate-tables";
      echo "Getting tax bands from $url... ";
      $txt = file_get_contents( $url );
      $pattern = "/<h3>Petrol car \(TC48\) and diesel car \(TC49\)<\/h3>\s*<table>(.*?)<\/table>/ms";
      if( !preg_match( $pattern, $txt, $m ) ) return false;
      $tbody = $m[1];
      if( !preg_match_all( "/<tr>(.*?)<\/tr>/s", $tbody, $m ) ) return false;
      foreach( $m[0] as $tr ){
        if( !preg_match_all( "/<td>([^<]+)<\/td>/", $tr, $n ) ) continue;
        $t = new TaxBand();
        $t->Fields->Name = substr( $n[1][0], 0, 1 );
        $t->Fields->AnnualCost = $n[1][2];
        $t->save();
      }
      echo "done\n";
    }
  }
?>
