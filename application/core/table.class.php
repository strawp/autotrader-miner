<?php
  /**
  * Generic table data class for storing/rending table data
  */
  class Table{
    function __construct($tablename="",$title=""){
      $this->name = $tablename;
      $this->title = $title;
      $this->headers = new TableRow("header"); // Type of TableRow
      $this->aRows = array();
      $this->classname = "";
      $this->numrows = 0;
      $this->debug = false;
    }
    function getHeaders(){
      return $this->headers;
    }
    function setHeaders( $row ){
      $this->headers = $row;
    }
    function setHeader( $key, $cell ){
      $this->headers->addCell( $cell, $key  );
    }
    function addHeader( $cell ){
      $cell->isheader = true;
      $this->headers->addCell( $cell, $cell->columnname );
    }
    function addHeaderNames( $aHeaders ){
      foreach( $aHeaders as $col => $name ){
        if( is_int( $col ) ) $this->addHeaderName( $name );
        else $this->addHeaderName( $name, $col );
      }
    }
    function addHeaderName( $name, $columnname = "" ){
      $cell = new TableCell($name,$columnname,true);
      $this->addHeader($cell);
    }
    
    /**
    * Test if a table has a column in the headers
    */
    function hasHeader( $columnname ){
      return $this->headers->hasCell( $columnname );
    }
    function addRow( $row, $key="" ){
      if( $key != "" ) $this->aRows[$key] = $row;
      elseif( $row->name != "" ) $this->aRows[$row->name] = $row;
      else $this->aRows[] = $row;
      $this->numrows++;
    }
    function addBlankRow( $cellcount ){
      $tr = new TableRow("blank");
      $tr->addBlankCells($cellcount);
      $this->addRow( $tr );
    }
    function getRows(){
      return $this->aRows;
    }
    function getRow( $key ){
      if( !isset( $this->aRows[$key] ) ){ 
        return false;
      }
      return $this->aRows[$key];
    }
    function deleteRow( $index ){
      if( isset( $this->aRows[$index] ) ) unset( $this->aRows[$index] );
    }
    function addColumn( $name, $key="" ){
      $this->addHeaderName( $name, $key );
      foreach( $this->aRows as $k => $row ){
        $this->aRows[$k]->addCell( new TableCell( "", $key ) );
      }
    }
    
    /**
    * Build up this table using an associative array
    * @param array $aData 2D array keyed by row IDs/names and column names
    */
    function buildFromArray( $aData ){
      foreach( $aData as $rowname => $row ){
        // First row, init headers
        if( $this->numrows == 0 ){
          $this->addHeaderName( "key" );
          foreach( $row as $columnname => $column ){
            $this->addHeaderName( $columnname, $columnname );
          }
        }
        $tr = new TableRow( $rowname );
        $tr->addCell( new TableCell( $rowname, "key" ) );
        foreach( $row as $columnname => $column ){
          $tr->addCell( new TableCell( $column, $columnname ) );
        }
        $this->addRow( $tr );
      }
    }
    
    /**
    * Automatically add a totals row using the given columns
    * @param array $aColumns list of columns to sum
    * @param array $aFill pre-supplied column info to put in columns, keyed by column name
    */
    function addTotalsRow( $aColumns, $aFill = array() ){
      if( !$aColumns ) return false;
      $aTotals = array();
      foreach( $aColumns as $col ){
        $aTotals[$col] = 0;
      }
      if( $this->debug ) print_r( $aTotals );
      foreach( $this->aRows as $rowid => $row ){
        if( $this->debug ) echo "Row $rowid\n";
        foreach( $aColumns as $col ){
          $cell = $row->getCell($col);
          if( !$cell ){ 
            if( $this->debug ) echo "Didn't get cell $col in: ".join( ", ", array_keys( $row->aCells ) )."\n";
            continue;
          }
          $data = $cell->getPlainData();
          $data = preg_replace( "/[^-0-9\.]/", "", $data );
          $aTotals[$col]+=$data;
        }
      }
      $tr = new TableRow("totals");
      foreach( $this->headers->aCells as $col => $h ){
        if( array_search( $col, $aColumns ) !== false ){
          $cell = new TableCell( number_format( $aTotals[$col] ), $col );
        }else{
          $cell = new TableCell( "", $col );
        }
        $tr->addCell( $cell );
      }
      foreach( $aFill as $k => $v ){
        $tr->getCell($k)->setHtmlData( $v );
      }
      $this->addRow( $tr );
    }
    
    /** 
    * Automatically add a totals column in each row using the given columns
    * @param array $aColumns list of columns to sum
    */
    function addTotalsColumn( $aColumns, $classname ){
      $aTotals = array();
      $this->addHeaderName( "Total" );
      foreach( $this->aRows as $row ){
        foreach( $aColumns as $col ){
          $aTotals[$col] = 0;
        }
        foreach( $aColumns as $col ){
          $cell = $row->getCell($col);
          $data = $cell->getPlainData();
          $data = preg_replace( "/[^-0-9\.]/", "", $data );
          $aTotals[$col]+=$data;
        }
        $cell = new TableCell( $aTotals[$col], "total" );
        $cell->classname .= " ".$classname;
        $row->addCell( $cell );
      }
    }
    
    /**
    * Sort the table by a given column key name
    * @param string column 
    * @param string direction (defaults to asc)
    */
    function sortByColumn( $column, $direction="asc" ){
      // Create lookup of row keys
      $aLookup = array();
      foreach( $this->getRows()as $key => $tr ){
        $aLookup[$key] = $tr->getCell($column)->getPlainData();
      }
      
      // Sort the lookup
      asort($aLookup);
      if( strtolower( $direction ) == "desc" ) $aLookup = array_reverse( $aLookup, true );
      
      // Put the rows back in order
      $aRows = $tbl->getRows();
      foreach( array_keys( $aLookup ) as $key ){
        $tbl->addRow( $aRows[$key] );
      }
    }
    
    /**
    * Get a quick HTML marked up table from an array, e.g. for debugging
    * @param array $aData
    * @param string HTML
    */
    static function arrayToHtmlTable( $aData ){
      $tbl = new Table();
      $tbl->buildFromArray( $aData );
      return $tbl->getHtml();
    }
    
    /**
    * Return a fully marked-up HTML table string
    */
    function getHtml(){
      $str = "<table class=\"".h($this->classname)."\" cellspacing=\"0\" id=\"tbl".h($this->name)."\" title=\"".htmlentities( $this->title )."\">\n";
      
      // Headers
      $str .= "  <thead>\n";
      $str .= $this->headers->getHtml();
      $str .= "  </thead>\n";
      
      
      // Body
      $str .= "  <tbody>\n";
      foreach( $this->aRows as $row ){
        $str .= $row->getHtml();
      }
      $str .= "  </tbody>\n";
      
      $str .= "</table>\n";
      return $str;
    }
    
    /**
    * Get the table as a tab separated string
    */
    function getTsv(){
      $str = "";
      $str .= $this->headers->getTsv();
      // Body
      foreach( $this->aRows as $row ){
        $str .= $row->getTsv();
      }
      return $str;
    }
    
    /**
    * Get PHP string which could re-build this table
    */
    function getPhp(){
      $str = "\$tbl = new Table(\"".$this->name."\",\"".$this->title."\");\n";
      $str .= "\$tbl->classname = \"".$this->classname."\";\n";
      
      // Headers
      $str .= $this->headers->getPhp();
      
      // Body
      foreach( $this->aRows as $row ){
        $str .= $row->getPhp();
      }
      
      return $str;
    }
    
  }

?>