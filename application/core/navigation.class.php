<?php

  class Navigation{
    
    function Navigation(){
      $this->aItems = array();
      $this->timecreated = time(); // The time this model was instantiated (for caching purposes)
    }
    
    function addItem( $item ){
      if( $item->link != "" ) $this->aItems[$item->link] = $item;
      else $this->aItems[] = $item;
    }
    
    function renderBreadCrumbTrail(){
      $return = "";
      foreach( $this->aItems as $item ){
        if( $item->iscurrent ) $return .= $item->renderBreadCrumbTrail();
      }
      return "    <div class=\"breadcrumb\">You are here: <a href=\"".SITE_ROOT."\">Home</a>".$return."</div>\n";
    }
    
    /**
    * First thing to run when restoring from cache
    */
    function restore(){
      $this->autoSetIsCurrentFlags();
    }
    
    /**
    * Set all the iscurrent flags for all the nav items in this navigation
    */
    function autoSetIsCurrentFlags(){
      foreach( $this->aItems as $k => $item ){
        if( is_object( $item ) ) $this->aItems[$k]->autoSetIsCurrent();
      }
    }
    
    function render($item="", $class="navigation", $render_desc=false ){
      
      $html = "";
      if( sizeof( $this->aItems ) > 0 ){
        $html .= "    <div class=\"".$class."\">\n";
        if( $class == "navigation" ) $html .= "      <a name=\"navigation\" class=\"anchor\"></a>\n";
        $html .= "      <ul>\n";
        
        $itemcount = 0;
        if( $item == "" ){
          foreach( $this->aItems as $item ){
            $class = "";
            if( $item->iscurrent ) $class .= "current ";
            if( $itemcount == 0 ) $class .= "first ";
            if( $itemcount == sizeof( $this->aItems ) - 1) $class .= "last ";
            if( sizeof( $item->aChildren ) > 0 ) $class .= "children ";
            $html .= "        <li class=\"$class\">".$item->renderLink()."</li>\n";
            $itemcount++;
          }
        }else{
          if( array_key_exists( $item, $this->aItems ) ){ 
            $item = $this->aItems[$item];
            if( sizeof( $item->aChildren ) > 0 ) $class .= "children ";
            $html .= "        <li class=\"$class\">".$item->renderLink( $render_desc )."</li>\n";
          }
        }
        
        $html .= "      </ul>\n";
        $html .= "      <br/>\n";
        $html .= "    </div>\n";
      }
      return $html;
    }
    
  }

?>