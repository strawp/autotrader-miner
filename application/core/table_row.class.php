<?php
  /**
  * Helper class for storing / rendering html table row data
  */
  class TableRow{
    function __construct($name=""){
      $this->aCells = array();
      $this->classname = $name; // HTML classname
      $this->name = $name;
    }
    
    /**
    * Test if the row has the given class in the classname property
    */
    function hasClass($class){
      return preg_match( "/\b$class\b/", $this->classname );
    }
    function getCellKeys(){
      return array_keys( $this->aCells );
    }
    function addCell( $cell, $key="" ){
      if( !( $cell instanceof TableCell ) ) return false;
      if( !$key ) $key = $cell->columnname;
      if( $key != "" ) $this->aCells[$key] = $cell;
      else $this->aCells[] = $cell;
    }
    function getCell( $key ){
      if( !isset( $this->aCells[$key] ) ) return false;
      return $this->aCells[$key];
    }
    function hasCell( $key ){
      return isset( $this->aCells[$key] );
    }
    function addBlankCell($col=""){
      $cell = new TableCell("",$col);
      $cell->classname .= " blank";
      $this->addCell( $cell );
    }
    function addBlankCells($count,$aCols=array()){
      for( $i=0;$i<$count;$i++ ){
        $col = isset( $aCols[$i] ) ? $aCols[$i] : "blank".$i;
        $this->addBlankCell($col);
      }
    }
    function getPlainDataArray(){
      $aData = array();
      foreach( $this->aCells as $cell ){
        $aData[] = $cell->getPlainData();
      }
      return $aData;
    }
    function getHtml(){
      $str = "";
      foreach( $this->aCells as $cell ){
        $str .= $cell->getHtml();
      }
      if( $str != "" ) $str = "  <tr class=\"".h($this->classname)."\">\n".$str."  </tr>\n";
      return $str;
    }
    function getTsv(){
      return join( "\t", $this->getPlainDataArray() )."\n";
    }
    function getPhp(){
      $str .= "// Row ".$this->name."\n";
      $str .= '$tr = new TableRow("'.$this->name.'");'."\n";
      if( $this->classname != $this->name ) $str .= "\$tr->classname = \"".$this->classname."\";\n";
      foreach( $this->aCells as $cell ){
        $str .= $cell->getPhp();
      }
      $str .= "\$tbl->addRow( \$tr );\n\n";
      return $str;
    }
  }

?>