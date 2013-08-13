<?php
  /**
  * Render a generic page displaying some HTML as a wizard step
  */
  class GenericPageWizardStep extends WizardStep {
    function __construct(){
      parent::__construct();
      $this->name = "Generic Page";
      $this->editable = true;
      $this->html = "";
    }
    
    function init( $wiz ){
      parent::init($wiz);
    }
    
    /**
    * Render the mail a PI form 
    */
    function render(){
      $this->complete = true;
      $html = parent::render();
      return $html . $this->html;
    }
   
    /**
    * Always flag complete
    */
    function save(){     
      $this->complete = true;
    }
  }
?>