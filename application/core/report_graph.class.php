<?php
  /**
  * Class to create graphs that can be put in web pages, emails, PDFs
  */
  class ReportGraph{
  
    function __construct(){
      $this->title = "";
      $this->name = "";
      $this->id = uniqid( "graph_" );
      $this->aOptions = array();
      $this->baseurl = SITE_ROOT."report/graphs";
      $this->graph = null;
      $this->data = null;
      $this->rendermode = ""; // Set to override rendering method (datauri/temp/web)
      $this->tempfile = "";   // If this is compiled into a temporary file, the directory location of the file
      $this->width = 450;
      $this->height = 250;
    }
    
    function setOptions( $aOptions=array() ){
      $this->aOptions = $aOptions;
    }
    
    /**
    * Override with code which builds data into the graph
    */
    function compile(){}
    
    /**
    * Construct an accumulative bar chart from a table class
    * One row = one section of bar
    * row header = section label
    * column header = x axis label
    */
    function createBarChartFromTable($table){
      require_once( "lib/jpgraph/jpgraph.php" );
      require_once( "lib/jpgraph/jpgraph_bar.php" );
      require_once ('lib/jpgraph/jpgraph_line.php');
      
      $aColours = getRainbow( sizeof( $table->aRows ), false, 150, 0 );

      $graph = new Graph($this->width,$this->height,"auto"); 
      $graph->title->set( $table->title );
      $graph->SetScale("textlin");
      
      $graph->img->SetMargin(40,30,20,40);
      $graph->SetBackgroundGradient( "white", "white", 2, BGRAD_MARGIN );
      
      // Optional collection of bar groups, if rows have the class "_grouped"
      $aGroups = array();
      
      // Create the bar plots
      $aBars = array();
      foreach( $table->aRows as $tr ){
        if( $tr->hasClass( "_grouped" ) ){
          $aGroups[] = $aBars;
          $aBars = array();
        }
        $ydata = array();
        $xdata = array();
        $aData = array();
        foreach( $tr->aCells as $cell ){
          if( $cell->isheader ){ 
            $name = $cell->getPlainData();
            continue;
          }
          $aData[] = preg_replace( "/[^-0-9]/", "", $cell->getPlainData() );
        }
        if( sizeof( $aData ) == 0 ){ 
          continue;
        }
        $plot = new BarPlot($aData);
        $plot->SetFillColor(array_pop($aColours));
        $plot->SetLegend($name);
        $aBars[] = $plot;
        if( $tr->hasClass( "_grouped" ) ){
          $aGroups[] = $aBars;
          $aBars = array();
        }
      }
      if( sizeof( $aBars ) == 0 && sizeof( $aGroups ) == 0 ) return;
      
      // Get table headers 
      $aXlabels = $table->getHeaders()->getPlainDataArray();
      array_shift( $aXlabels );
      
      // Create the grouped bar plot
      if( sizeof( $aGroups ) > 0 ){
        $a = array();
        foreach( $aGroups as $group ){
          $acplot = new AccBarPlot($group);
          $a[] = $acplot;
        }
        $gbplot = new GroupBarPlot($a);
        $graph->Add($gbplot);
      }elseif( sizeof( $aBars ) > 0 ){ 
        $acplot = new AccBarPlot($aBars);
        $acplot->SetWidth( 0.8 );
        $graph->Add($acplot);
      }
      
      $graph->title->SetFont(FF_ARIAL,FS_BOLD);
      $graph->yaxis->title->SetFont(FF_ARIAL,FS_BOLD);
      $graph->yaxis->SetLabelFormatCallback('yLabelFormat');
      $graph->xaxis->title->SetFont(FF_ARIAL,FS_BOLD);
      
      $graph->xaxis->SetTickLabels($aXlabels);
      $graph->legend->SetPos(0.01,0.5,"right","center");
      $graph->legend->SetShadow(false);
      $graph->legend->SetFillColor("white");
      $graph->SetMargin(60,250,20,30);
      $this->graph = $graph;   
      return true;
    }
    
    /**
    * Construct a line graph from a Table class
    * One row = one line
    * header cell of a row is a line label
    * header cell of column is x axis labels
    */
    function createLineGraphFromTable($table){
      require_once( "lib/jpgraph/jpgraph.php" );
      require_once( "lib/jpgraph/jpgraph_line.php" );
      
      // First header is top left of table, second is first data column, third is second data column. 2 columns required
      if( sizeof( $table->headers->aCells ) < 3 ){ 
        return false;
      }
      
      // Line graphs need at least 2 points of data
      if( sizeof( $table->aRows ) < 1 ){ 
        return false;
      }

      // Create the graph. These two calls are always required 
      $graph  = new Graph($this->width, $this->height,"auto");     
      $graph->title->set( $table->title );
      $graph->SetScale( "textlin"); 
      $aColours = getRainbow( sizeof( $table->aRows ), true, 50, 200 );

      foreach( $table->aRows as $tr ){
        $ydata = array();
        $xdata = array();
        foreach( $tr->aCells as $cell ){
          if( $cell->isheader ){ 
            $name = $cell->getPlainData();
            continue;
          }
          $ydata[] = preg_replace( "/[^0-9]/", "", $cell->getPlainData() );
          $xdata[] = $cell->columnname;
        }
        $lineplot = new LinePlot($ydata);
        $lineplot->SetLegend($name);
        $lineplot->SetColor(array_pop($aColours)); 
        $lineplot->SetWeight(2);
        
        // Add the plot to the graph 
        $graph->Add( $lineplot); 
        $graph->xaxis->SetTickLabels($xdata);
      }
      $graph->yaxis->SetLabelFormatCallback('yLabelNumberFormat');
      $graph->img->SetMargin( 80, 40, 30, 30 );
      $graph->SetBackgroundGradient( "white", "white", 2, BGRAD_MARGIN );
      $graph->legend->SetShadow(false);
      $graph->legend->SetFillColor("white");
      $this->graph = $graph;
      return true;
    }

    
    /**
    * Get standard web img url element
    */
    function getWebImgElement(){
      return "<img class=\"graph\" width=\"".$this->width."\" height=\"".$this->height."\" src=\"".$this->getUrl()."\" alt=\"".$this->title."\" />";
    }
    
    /**
    * Get local temp img url element
    */
    function getLocalTempImgElement(){
      $this->saveToTempFile();
      $rtn = "<img class=\"graph\" width=\"".$this->width."\" height=\"".$this->height."\" src=\"".$this->tempfile."\" alt=\"".$this->title."\" ";
      if( isset( $this->height ) ) $rtn .= "height=\"".$this->height."\" ";
      if( isset( $this->width ) ) $rtn .=  "width=\"".$this->width."\" ";
      $rtn .= "/>";
      return $rtn;
    }
    
    /**
    * Get base 64 img element test
    * @return string
    */
    function getDataUriImgElement(){
      return "<img class=\"graph\" width=\"".$this->width."\" height=\"".$this->height."\" src=\"".$this->getDataUri()."\" alt=\"".$this->title."\" />";
    }
    
    /**
    * The URL of this image if it was on the web
    */
    function getUrl(){
      $url = $this->baseurl;
      if( $this->aOptions ){
        $url .= "?";
        $amp = "";
        foreach( $this->aOptions as $name => $value ){
          $url .= $amp.urlencode( $name )."=".urlencode( $value );
          $amp = "&";
        }
      }
      return $url;
    }
    
    /**
    * Return base64 encoded URI of the image data
    * @return string
    */
    function getDataUri(){
      return "data:image/png;base64,".base64_encode( $this->getImageData() );
    }
    
    /**
    * Get raw binary image data
    * @return bin image data
    */
    function getImageData(){
      if( !isset( $this->graph ) ) $this->compile();
      ob_start();
      imagepng( $this->getImageHandle() );
      $rtn = ob_get_contents();
      ob_end_clean();
      return $rtn;
    }
    
    /**
    * Send graph image data directly to browser, headers and all
    */
    function writeToBrowser(){
      $this->graph->Stroke();
    }
    
    /**
    * Save to a file
    * @param string $file
    * @return bool success
    */
    function save($file){
      // if( !isset( $this->graph ) ) 
      $this->compile();
      $this->graph->Stroke($file);
      if( file_exists( $file ) ) return true;
      return false;
    }
    
    /**
    * Save to temporary location
    * @return bool success
    */
    function saveToTempFile(){
      if( $this->tempfile == "" ) $this->generateTempFileName();
      return $this->save($this->tempfile);
    }
    
    /**
    * Delete the temp file
    * @return bool success
    */
    function deleteTempFile(){
      if( $this->tempfile == "" ) return false;
      unlink( $this->tempfile );
      $rtn = !file_exists( $this->tempfile );
      $this->tempfile = "";
      return $rtn;
    }
    
    /**
    * Create a full (local) temp file name and assign to $this->tempfile;
    */
    function generateTempFileName(){
      $this->tempfile = SITE_TEMPDIR.$this->id."_".date( "Y-m-d_His" )."_".uniqid().".png";
      if( preg_match( "/^[a-z]:/", $this->tempfile ) ){
        // Has real problems with windows drive names in a path, take that out and hope for the best
        $this->tempfile = preg_replace( "/^[a-z]:/", "", $this->tempfile );
      }
    }
    
    /**
    * Get the GD library image handle for the graph image
    * @return bin GD library image handle
    */
    function getImageHandle(){
      return $this->graph->Stroke(_IMG_HANDLER);
    }
  }
?>