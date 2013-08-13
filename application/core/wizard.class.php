<?php
  /**
  * Class used to render a wizard
  */
  class Wizard {
    function __construct(){
      $this->name = "Wizard";
      $this->classname = preg_replace("/_wizard/","",camelToUnderscore(get_class($this)));
      $this->aSteps = array();  // Base 1 array of steps
      $this->currentstep = 1;
      $this->actionpage = "";
      $this->interfacebase = SITE_ROOT."wizard/".$this->classname;  // URL of the wizard
      $this->id = 0;
      $this->editable = true;
      $this->timecreated = time();
      $this->aArgs = array();
      $this->formid = "frm".get_class($this);
      $this->showsummary = true;
    }
    
    /**
    * Any pre-render options passed here
    */
    function init($args){
      $this->aArgs = $args;
      foreach( $this->aSteps as $k => $step ){
        $this->aSteps[$k]->init($this);
      }
      $this->currentstep = $this->getFirstIncompleteStep();
      // echo "Setting first incomplete step: ".$this->currentstep;
    }
    
    /**
    * Add a wizard step
    */
    function addStep( $step ){
      $i = sizeof( $this->aSteps ) + 1;
      $step->index = $i;
      if( $step->name == "" ) $step->name = "Step ".$step->index;
      $this->aSteps[$i] = $step;
    }
    
    /**
    * Render the wizard in the current step
    */
    function render(){
      $html = "";
      
      $class = $this->CurrentStep()->getFormClasses();
      $formid = $this->CurrentStep()->getFormId() == "" ? $this->formid : $this->CurrentStep()->getFormId();
      
      // Title
      $html .= "      <h2>".htmlentities( $this->name )."</h2>\n";
      $html .= "      <form class=\"edit wizard ".$class."\" action=\"".$this->actionpage."\" method=\"post\" id=\"".$formid."\">\n";
      
      // Workflow stage indicator
      $html .= $this->renderWorkflowStepIndicator();
      
      // Step content
      $html .= $this->renderCurrentStep();
      
      // Controls
      $html .= $this->renderControls();
      $html .= "      </form>\n";
      
      return $html;
    }
    
    /**
    * Get auth for this wizard, returns default auth
    */
    function getAuth(){
      if( SessionUser::isAdmin() ) return "crud";
      return "r";
    }    
    function isAuth(){
      return true;
    }
  
    /**
    * Set the current step
    */
    function setCurrentStep($i){
      if( !isset( $this->aSteps[$i] ) ) $i = 1;
      // Make sure the user is allowed onto this step yet
      $allowedstep = $this->getLastAllowedStep();
      if( $i > $allowedstep ) $i = $allowedstep;
      $this->currentstep = $i;
    }
    
    /**
    * Get the value of the current step
    */
    function getCurrentStep(){
      return isset( $this->aSteps[$this->currentstep] ) ? $this->currentstep : 1;
    }
    
    /**
    * Get the index of the first incomplete step
    */
    function getFirstIncompleteStep(){
      $i = false;
      
      // This also runs isComplete on everything at least once so that complete is initialised. Don't break the loop!
      foreach( $this->aSteps as $k => $step ){
        if( !$this->aSteps[$k]->isComplete() && !$i ) $i = $step->index;
      }
      return $i;
    }
    
    /**
    * Get the highest step number that the user is allowed to get to, based on which steps are skippable or complete
    */
    function getLastAllowedStep(){
      foreach( $this->aSteps as $i => $step ){
        if( $step->isComplete() || $step->skipable ) continue;
        return $i;
      }
      return $i;
    }
    
    /**
    * Has this wizard been entirely completed?
    */
    function isComplete(){
      foreach( $this->aSteps as $step ){
        if( !$step->isComplete() ) return false;
      }
      return true;
    }
    
    /**
    * Get total number of steps 
    */
    function getTotalSteps(){
      return sizeof( $this->aSteps );
    }
    
    /**
    * Get number of completed steps
    */
    function getNumberOfCompletedSteps(){
      $count = 0;
      foreach( $this->aSteps as $step ){
        if( $step->isComplete() ) $count++;
      }
      return $count;
    }
    
    /**
    * Get percentage complete
    */
    function getPercentageComplete(){
      $total = $this->getTotalSteps();
      if( $total == 0 ) return 0;
      $complete = $this->getNumberOfCompletedSteps();
      if( $complete == 0 ) return 0;
      return round( 100 * $complete / $total );
    }
    
    /**
    * Reference the actual current step object
    */
    function CurrentStep(){
      if( !$this->aSteps[$this->getCurrentStep()]->inited ) $this->aSteps[$this->getCurrentStep()]->init( $this );
      return $this->aSteps[$this->getCurrentStep()];
    }
    
    /**
    * Render current step
    */
    function renderCurrentStep(){
      return $this->CurrentStep()->render();
    }
    
    /**
    * Render a step
    */
    function renderStep( $i ){
      if( !isset( $this->aSteps[$i] ) ) return false;
      if( !$this->aSteps[$i]->inited ) $this->aSteps[$i]->init( $this );
      return $this->aSteps[$i]->render();
    }
    
    /**
    * Create a wizard from a model, making each fieldset a step
    */
    function initFromModel( $model ){
      $fieldset = "_";
      $this->name = $model->name." Wizard";
      $this->interfacebase = SITE_ROOT.$model->tablename."/wizard/".$model->id;
      // $step = new WizardStep();
      foreach( $model->aFields as $k => $field ){
        if( !$field->display ) continue;
        if( $field->fieldset != $fieldset ){ 
          if( isset( $step ) ) $this->addStep( $step );
          $step = new WizardStep();
          $step->name = $field->fieldset;
          $fieldset = $field->fieldset;
        }
        $step->addField( $field );
      }
      $this->addStep( $step );
      $this->actionpage = SITE_ROOT.$model->tablename."/_action";
      $this->classname = $model->tablename;
      $this->id = $model->id;
    }
    
    /**
    * Renders the current stage within the stages of the workflow
    */
    function renderWorkflowStepIndicator(){
      $html = "";
      $html .= "      <div class=\"step_indicator\">\n";
      $html .= "        <ul class=\"steps\">\n";
      $z = sizeof( $this->aSteps );
      $allowed = $this->getLastAllowedStep();
      foreach( $this->aSteps as $i => $step ){
        $class = "";
        if( $step->index < $this->currentstep ) $class .= "past ";
        if( $step->index > $this->currentstep ) $class .= "future ";
        if( $step->index == $this->currentstep ) $class .= "current ";
        if( $step->index == $this->currentstep-1 ) $class .= "previous ";
        if( $step->index == $this->currentstep+1 ) $class .= "next ";
        if( $step->index == 1 ) $class .= "first ";
        if( $step->index == sizeof( $this->aSteps ) ) $class .= "last ";
        if( $step->isComplete() ) $class .= "complete ";
        
        $label = $step->index.". ".$step->name;
        $label = h($label);
        if( $step->index <= $allowed ) $label = "<a href=\"".$this->interfacebase."/step/".$step->index."\">".$label."</a>";
        $html .= "<li class=\"$class\" >".$label."</li>";
        $z--;
      }
      $html .= "        </ul>\n";
      $html .= "      </div>\n";
      return $html;
    }
    
   /**
    * Render the wizard in the current step
    */
    function renderSummary(){
      $html = "";
      
      // Title
      $html .= "      <h2>".htmlentities( $this->name )."</h2>\n";
      
      // Workflow stage indicator
      // $html .= $this->renderWorkflowStepIndicator();
      
      $complete = $this->isComplete();
      $status = $complete ? "Complete" : "Incomplete";
      $html .= "      <h3>".htmlentities( $status )."</h3>\n";
      
      if( $complete ){
        $html .= "      <p>Thank you. This workflow has been completed.</p>\n";
      }else{
        $html .= "      <p>This workflow ".$this->getPercentageComplete()."% complete. See below for a summary of steps.</p>\n";
      }
      
      $html .= $this->renderCheckList();
      
      return $html;
    }
    
    /**
    * Renders a checklist of all steps
    */
    function renderCheckList(){
      $html = "";
      $html .= "      <div class=\"step_checklist\">\n";
      $html .= "        <ol class=\"steps\">\n";
      foreach( $this->aSteps as $i => $step ){
        $class = "";
        if( $step->index == 1 ) $class .= "first ";
        if( $step->index == sizeof( $this->aSteps ) ) $class .= "last ";
        if( $step->isComplete() ){ 
          $class .= "complete ";
          $desc = "Completed";
        }else{
          $class .= "incomplete ";
          $desc = $step->getDescription();
        }
        
        $label = $step->name;
        $label = "<a href=\"".$this->interfacebase."/step/".$step->index."\">".h($label)."</a>";
        $html .= "<li class=\"$class\">".$label." <p class=\"description\">".h($desc)."</p></li>";
      }
      $html .= "        </ol>\n";
      $html .= "      </div>\n";
      return $html;
    }
    
    /**
    * Render the buttons
    *  - Previous
    *  - Next
    *  - Save
    *  - Skip
    *  - Finish
    */
    function renderControls(){
      $html = "";
      $html .= "      <div class=\"control_container\">\n";
      $html .= "        <input type=\"hidden\" name=\"step\" value=\"".$this->getCurrentStep()."\" />\n";
      $html .= "        <input type=\"hidden\" name=\"action\" value=\"wizard\" />\n";
      $html .= "        <input type=\"hidden\" name=\"model\" value=\"".$this->classname."\" />\n";
      $html .= "        <input type=\"hidden\" name=\"id\" value=\"".intval( $this->id )."\" />\n";
      $html .= "        <input type=\"hidden\" name=\"sessidhash\" value=\"".SessionUser::getProperty("sessidhash")."\" />\n";
      $html .= "        <ul class=\"controls\">\n";
      $skiplabel = $this->CurrentStep()->skiplabel;
      $nextlabel = $this->CurrentStep()->nextlabel;
      $previouslabel = $this->CurrentStep()->previouslabel;
      $savelabel = $this->CurrentStep()->savelabel;
      $finishlabel = $this->CurrentStep()->finishlabel;
      if( $this->currentstep > 1 ) $html .= "          <li class=\"previous\"><input value=\"".$previouslabel."\" type=\"submit\" class=\"button\" name=\"btnSubmit\" id=\"btnPrevious\" /></li>\n";
      if( $this->CurrentStep()->editable && $this->getTotalSteps() > $this->currentstep ) $html .= "          <li class=\"save\"><input value=\"".$savelabel."\" type=\"submit\" class=\"button\" name=\"btnSubmit\" id=\"btnSave\" /></li>\n";
      if( $this->CurrentStep()->skipable && $this->getTotalSteps() > $this->currentstep ) $html .= "          <li class=\"skip\"><input value=\"".$skiplabel."\" type=\"submit\" class=\"button\" name=\"btnSubmit\" id=\"btnSkip\" /></li>\n";
      if( $this->getTotalSteps() > $this->currentstep ) $html .= "          <li class=\"next\"><input value=\"".$nextlabel."\" type=\"submit\" class=\"button\" name=\"btnSubmit\" id=\"btnNext\" /></li>\n";
      if( $this->getTotalSteps() == $this->currentstep ) $html .= "          <li class=\"finish\"><input value=\"".$finishlabel."\" type=\"submit\" class=\"button\" name=\"btnSubmit\" id=\"btnFinish\" /></li>\n";
      $html .= "        </ul>\n";
      $html .= "      </div>\n";
      return $html;
    }    
    
    /**
    * Get JS file location for custom page JS
    */
    function getPageJs(){
      return $this->CurrentStep()->getPageJs();
    }
    
    /**
    * Get array of CSS file locations for custom page CSS
    */
    function getPageCss(){
      return $this->CurrentStep()->getPageCss();
    }
    
    /**
    * Get inline JS for the page
    */
    function getInlineJs(){
      return $this->CurrentStep()->getInlineJs();
    }
    
    /**
    * Get inline CSS for the page
    */
    function getInlineCss(){
      return $this->CurrentStep()->getInlineCss();
    }
  }
?>