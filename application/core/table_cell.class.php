<?php
  /**
  * Helper class for HTML table cells
  */
  class TableCell{
    private $htmldata;
    private $plaindata;
    function __construct($data,$columnname="",$header=false){
      $this->classname="";
      $this->isheader = $header;
      $this->setHtmlData( $data );
      $this->columnname = "";
      if( $columnname != "" ) $this->setColumnName( $columnname );
      else $this->setColumnName( $data );
      $this->colspan = 1;
      $this->id = "";
    }
    function setColumnName( $name ){
      // Don't know what the logic of this commented out section is for
      /* if( $name != "" && $this->isheader ){ 
        $this->columnname = str_replace( " ", "_", strtolower( preg_replace( "/[^a-zA-Z_0-9 ]/", "", $this->getPlainData() ) ) ); 
      }else */
      if($name != ""){
        $this->columnname = str_replace( " ", "_", strtolower( preg_replace( "/[^a-zA-Z_0-9 ]/", "", $name ) ) ); 
      }
    }
    function setHtmlData( $data, $setplaindata=true ){
      $this->htmldata = $data;
      $this->plaindata = html_entity_decode( strip_tags( $data ) );
    }
    function setPlainData( $data ){
      $this->plaindata = $data;
    }
    function getHtmlData(){
      return $this->htmldata;
    }
    function appendHtmlData( $html ){
      $h = $this->getHtmlData();
      $h .= $html;
      $this->setHtmlData( $h );
    }
    function getHtml(){
      if( $this->isheader ){
        $el = "th";
      }else{
        $el = "td";
      }
      $str = "    <$el class=\"".trim( $this->classname." ".$this->columnname )."\"";
      if( $this->colspan > 1 ) $str .= " colspan=\"".$this->colspan."\" ";
      if( $this->id != "" ) $str .= " id=\"".htmlentities( $this->id )."\" ";
      $str .= ">".$this->getHtmlData()."</$el>\n";
      return $str;
    }
    function getPlainData(){
      return $this->plaindata;
    }
    function getPhp(){
      $str = "\$cell = new TableCell( \"".str_replace( '"', '\"', $this->getHtmlData() )."\", \"".$this->columnname."\"";
      $str .= $this->isheader ? ", true" : "";
      $str .= " );\n";
      if( $this->classname != "" ) $str .= "\$cell->classname = \"".$this->classname."\";\n";
      if( $this->colspan > 1 ) $str .= "\$cell->colspan = ".$this->colspan.";\n";
      if( $this->id != "" ) $str .= "\$cell->id = \"".$this->id."\";\n";
      if( isset( $this->formula ) ) $str .= "// FORMULA: ".$this->formula."\n";
      $str .= "\$tr->addCell( \$cell );\n";
      return $str;
    }
  }
?>