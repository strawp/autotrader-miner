<?php

  class NavigationItem{
  
    function NavigationItem( $name, $link="", $desc="" ){
      
      $this->description = $desc;
      $this->name = $name;
      $this->link = $link;
      $this->aChildren = array();
      
      // Fully qualified URL
      if( preg_match( "/^".SITE_PROTOCOL.":\/\//", $link ) ){
        $this->url = $link;
      }
      
      // Link to a file
      elseif( preg_match( "/\.[a-z]{3,4}$/", $link ) ){
        $this->url = str_replace( "//", "", SITE_ROOT.$link );
      }
      
      // Some other kind of link
      elseif( $link != "" ){
        $this->url = str_replace( "//", "", SITE_ROOT.$link."/" );
      }
      
      // No URL, no link
      else{
        // No link
        $this->url = $link;
      }
      $this->baseurl = $this->url;  // URL to use when flagging iscurrent
      $this->autoSetIsCurrent();
    }
    
    /**
    * Automatically set the iscurrent flag based on the currently requested URL
    */
    function autoSetIsCurrent(){
      $iscurrent = preg_match( "/^".str_replace( "/", "\/", $this->baseurl ).".*$/", $_SERVER["REQUEST_URI"] );
      if( $_SERVER["REQUEST_URI"] != SITE_ROOT && $this->baseurl == SITE_ROOT ){
        $iscurrent = false;
      }
      if( $this->url == "" ) $iscurrent = false;
      $this->iscurrent = $iscurrent;
      
      // Set flag for all children of this element. If any children are current, so is this
      for( $i=0; $i<sizeof( $this->aChildren ); $i++ ){
        if( $this->aChildren[$i]->autoSetIsCurrent() ) $this->iscurrent = true;
      }
      return $this->iscurrent;
    }
    
    function renderBreadCrumbTrail(){
      $return = " &gt; ";
      $return .= $this->url != "" && $this->link != "/" ? "<a href=\"".$this->url."\">".h($this->name)."</a>" : "<span>".h($this->name)."</span>";
      foreach( $this->aChildren as $item ){
        if( $item->iscurrent ) $return .= $item->renderBreadCrumbTrail();
      }
      return $return;
    }
    
    function addChild( $menuitem ){
      $this->aChildren[] = $menuitem;
      if( $menuitem->iscurrent ) $this->iscurrent = true;
    }
    
    function renderLink( $render_desc=false){
      $html = $this->url != "" ? "<a href=\"".$this->url."\">".h($this->name)."</a>" : "<span>".h($this->name)."</span>";
      if( $render_desc && $this->description != "" ){
        $html .= "\n        <p class=\"description\">".$this->description."</p>\n";
      }
      if( sizeof( $this->aChildren ) > 0 ){
        $html .= "\n        <ul class=\"children\">\n";
        $itemcount=0;
        foreach( $this->aChildren as $child ){
          $class = "";
          if( $child->iscurrent ) $class .= "current ";
          if( $itemcount == 0 ) $class .= "first ";
          if( $itemcount == sizeof( $this->aChildren ) - 1) $class .= "last ";
          if( sizeof( $child->aChildren ) > 0 ) $class .= "children ";
          $html .= "          <li class=\"$class\">".$child->renderLink( $render_desc )."</li>\n";
          $itemcount++;
        }
        $html .= "        </ul>\n      ";
      }
      return $html;
    }
    
  }

?>