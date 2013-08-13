<?php
  /**
  * Class for reading from Excel documents
  */
  class ExcelConnector implements iFeature {
    private $contentsfile = "xl/workbook.xml";
    
    function getFeatureDescription(){
      return "Provides and interface for reading from Excel files";
    }
    
    function __construct( $file="" ){
      if( !file_exists( $file ) ) return false;
      $this->open( $file );
    }
    function init(){
      $this->sharedstrings = null;
      $this->initWorksheets();
    }
    
    function initWorksheets(){
      $xml = $this->getFileObject( $this->contentsfile );      
      $this->aWorksheets = array();
      foreach( $xml->sheets->sheet as $sheet ){
        $name = (string)$sheet->attributes()->name;
        $this->aWorksheets[$name] = new ExcelWorksheet( $name );
      }
    }
    
    function getWorksheetNames(){
      return array_keys( $this->aWorksheets );
    }
    
    function open($filename=""){
      $this->zip = new ZipArchive();
      if( $filename != "" ){ 
        if( !file_exists( $filename ) ){
          return false;
        }
        $this->filename = $filename;
      }
      try{
        $this->zip->open($this->filename);
      }
      catch( Exception $e ){
        return false;
      }
      $this->init();
    }
    function close(){
      $this->zip->close();
    }
    
    /**
    * Get string from cell in a worksheet
    */
    function getStringFromCell( $cell, $worksheet ){
      $xml = $this->getWorksheetXml( $worksheet );
      $c = $xml->xpath( "//c[@r='$cell']" );
      if( sizeof( $c ) == 0 ) return false;
      
      // Literal string
      if( empty($c[0]->attributes()->t) || $c[0]->attributes()->t == "str" ){
        return (string)$c[0]->v;
      }
      
      // Shared string
      elseif( $c[0]->attributes()->t == "s" ){
        return $this->getSharedString( $c[0]->v );
      }
      return false;
    }
    
    function getWorksheetNumrows( $worksheet ){
      $xml = $this->getWorksheetXml( $worksheet );
      $rlt = $xml->xpath( "//row" );
      return sizeof( $rlt );
    }
    
    
    /**
    * Get shared string by index
    */
    function getSharedString( $index ){
      if( !$this->sharedstrings ) $this->initSharedStrings();
      $index+=1;
      $rlt = $this->sharedstrings->xpath( "//si[".intval($index)."]/t" );
      if( sizeof( $rlt ) == 0 ) return false;
      return (string)$rlt[0][0];
    }
    
    /**
    * Get the shared strings data
    */
    function initSharedStrings(){
      $s = $this->getFileContents( "xl/sharedStrings.xml" );
      $xml = self::getXmlFromString( $s );
      $this->sharedstrings = $xml;
    }
    
    /**
    * Get contents of a worksheet by name regexp
    */
    function getWorksheetContents( $name ){
      if( !empty( $this->aWorksheets[$name]->content ) ) return $this->aWorksheets[$name]->content;
      // $name = htmlentities( $name );
      $tables_file = $this->contentsfile;
      $content = $this->getFileContents( $tables_file );      
      if( !preg_match( "/<sheet name=\"".htmlentities($name)."\" sheetId=\"(?P<sheetId>\d+)\" r:id=\"rId(?P<id>\d+)\"\/>/", $content, $m ) ){
        echo "Couldn't find $name worksheet\n";
        return false;
      }
        
      // Get worksheet
      $sheetfile = "xl/worksheets/sheet".$m["id"].".xml";
      $content = $this->getFileContents( $sheetfile );    
      $this->aWorksheets[$name]->setContent($content);
      return $content;
    }
    
    function getWorksheet( $name ){
      return $this->aWorksheets[$name];
    }
    
    function getWorksheetXml( $name ){
      // if( !empty( $this->aWorksheets[$name]->xml ) ) return $this->aWorksheets[$name]->xml;
      $this->getWorksheetContents( $name );
      return $this->aWorksheets[$name]->xml;
    }
    
    /**
    * Get the content of a file in the archive
    */
    function getFileContents( $path ){
      $content = "";
      $fp = $this->zip->getStream($path);
      if(!$fp) return false;
      while (!feof($fp)) {
        $content .= fread($fp, 2);
      }
      fclose($fp);
      return $content;
    }
    
    function getFileObject( $path ){
      return self::getXmlFromString($this->getFileContents( $path ));
    }
    
    static function getXmlFromString( $str ){
      return simplexml_load_string( str_replace('xmlns=', 'ns=', $str ) );
    }
  }
  
  class ExcelWorksheet {
    function __construct($name){
      $this->name = $name;
      $this->numrows = null;
      $this->content = "";
      $this->xml = null;
    }
    function setContent( $content ){
      $this->content = $content;
      $this->xml = ExcelConnector::getXmlFromString( $content );
      $this->numrows = sizeof($this->xml->xpath( "//row" ));
    }
    
    // e.g. C4
    function getCell( $cellname ){
      return $this->xml->xpath( "//c[@r='$cell']" );
    }
  }
?>