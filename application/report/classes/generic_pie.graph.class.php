<?php
  class GenericPieGraph extends ReportGraph {
    function __construct() {
      parent::__construct();
      $this->name = "Generic Pie Chart";
      $this->title = "";
      $this->width = 650;
      $this->height = 400;
      $this->labeltype = PIE_VALUE_PER;
      $this->rendermode = "datauri";
      $this->id = uniqid( "generic_pie_graph_" );
    }
    
    function compile(){
      require_once( "lib/jpgraph/jpgraph.php" );
      require_once( "lib/jpgraph/jpgraph_pie.php" );
      $graph = new PieGraph($this->width,$this->height,"auto"); 
      $graph->title->set( $this->title );
      $graph->title->SetWordWrap( round( intval( $this->width ) / 7 ) );
      $graph->title->SetFont( FF_ARIAL, FS_NORMAL, 10 );
      
      // Create
      $p1 = new PiePlot($this->data);
      $p1->SetLabelType( $this->labeltype );
      $p1->SetLabels( $this->labels );
      $p1->value->SetFont(FF_ARIAL,FS_NORMAL,8);
      $p1->SetShadow();
      $p1->ExplodeAll();
      $graph->Add($p1);

      $p1->ShowBorder();
      $p1->SetColor('black');
      $p1->SetSliceColors(getRainbow( sizeof( $this->data ), true ) );
      $this->graph = $graph;
    }
  }
?>