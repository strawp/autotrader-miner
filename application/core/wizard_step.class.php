<?php
  /**
  * Class used to represent one step of a wizard
  */
  class WizardStep {
    function __construct(){
      $this->name = "Wizard Step";
      $this->description = "";
      $this->aFields = array();
      $this->complete = null;
      $this->editable = true;
      $this->skipable = true;
      $this->index = 0;
      $this->inited = false;
      $this->aError = array();
      $this->aWarnings = array();
      $this->skiplabel = "skip";
      $this->nextlabel = "next";
      $this->previouslabel = "previous";
      $this->savelabel = "save";
      $this->finishlabel = "finish";
      $this->custominterfacebase = "";      // For overriding the interfacebase property when finding the URL for the next step
    }
    
    /**
    * Method to call just before a step is rendered
    * @arg object $wizard Parent wizard
    */
    function init( $wizard ){
      $this->inited = true;
      $this->auth = $wizard->getAuth();
      $this->initFieldAccess();
    }
    
    /**
    * Set access to fields
    */
    function initFieldAccess(){
      if( strpos( $this->auth, "u" ) === false ){
        $this->disableFields();
      }
    }
    
    /**
    * Set all fields uneditable
    */
    function disableFields(){
      foreach( $this->aFields as $k => $f ){
        $this->aFields[$k]->editable = false;
        $this->aFields[$k]->enabled = false;
      }
    }
    
    /**
    * Add a field to a step
    */
    function addField( $field ){
      $this->aFields[$field->columnname] = $field;
    }
    
    /**
    * Save all data for this step to DB
    * Go through each field and save it to the model it belongs to
    */
    function save(){
      $this->complete = true;
    }
    
    /**
    * Keep form information in cache, do not commit to DB
    */
    function remember(){
    }
    
    /**
    * Get description
    */
    function getDescription(){
      return $this->description;
    }
    
    /**
    * Run validation against the fields in this step
    */
    function validate(){
      $valid = true;
      $this->aErrors = array();
      if( strstr( $this->getAuth(), "u" ) == "" ){ 
        $this->aErrors[] = array(
          "message" => "You do not have permission to save this step"
        );
        return false;
      }
      
      foreach( $this->aFields as $f ){
        $err = $f->validate();
        if( is_array( $err ) ){ 
          if( $err["type"] == "error" ){
            $this->aErrors[] = $err;
          }else{
            $this->aWarnings[] = $err;
          }
        }
      }
      if( sizeof( $this->aErrors ) > 0 ){ 
        return false;
      }
      return true;
    }
   
    /**
    * Get all the submitted form values from $_POST and set them as field values
    */
    function getForm( $aFields = array() ){
      if( empty( $_POST ) ) $_POST = $_GET;
      if( sizeof( $aFields ) == 0 ){
        $aFields = array_keys( $this->aFields );
      }
      foreach( $aFields as $key ){
        $this->aFields[$key]->getSubmittedValue($this->aFields[$key]->display);
      }
    }
    
    /**
    * Render this step HTML
    */
    function render(){
      $html = "";
      $html .= "      <h3>Step ".$this->index.": ".htmlentities( $this->name )."</h3>\n";
      if( $this->getDescription() != "" ) $html .= "      <p class=\"description\">".htmlentities( $this->getDescription() )."</p>\n";
      if( !$this->skipable ) $html .= "      <p class=\"unskipable\">This step must be completed before progressing</p>\n";
      $html .= $this->renderFields();
      return $html;
    }    
    function renderFields(){
      $html = "";
      $lastfieldset = "";
      foreach( $this->aFields as $k => $f ){
        if( !$f->display ) continue;
        // End
        if( ( $f->fieldset == "" || $f->fieldset != $lastfieldset ) && $lastfieldset != "" ){
          $html .= "        </fieldset>\n";
          $lastfieldset = "";
        }
        // Start
        if( $f->fieldset != "" ){
          if( $lastfieldset == "" ){
            $fieldset_class = strtolower( str_replace( "__", "_", preg_replace( "/[^a-z0-9]/i", "_", $f->fieldset ) ) );
            $html .= "        <fieldset class=\"".$fieldset_class."\">\n";
            $html .= "          <legend>".$f->fieldset."</legend>\n";
          }
        }
        $lastfieldset = $f->fieldset;
        $html .= $this->aFields[$k]->render();
      }
      if( $lastfieldset != "" ){
        $html .= "        </fieldset>\n";
      }
      return $html;
    }
    
    function isComplete(){
      if( isset( $this->complete ) ){ 
        return $this->complete;
      }
      return null;
    }


    /**
    * Get JS file location for custom page JS
    */
    function getPageJs(){
      return "";
    }
    
    /**
    * Get array of CSS file locations for custom page CSS
    */
    function getPageCss(){
      return array();
    }
    
    /**
    * Get inline JS
    */
    function getInlineJs(){
      return "";
    }
    
    /**
    * Get inline CSS
    */ 
    function getInlineCss(){
      return "";
    }
    
    /**
    * Authorisation for this step
    */
    function getAuth(){
      return $this->auth;
    }
    
    /**
    * Get space-separated list of classes to be added to the form class
    */
    function getFormClasses(){
      return "";
    }
    
    /**
    * Get form ID
    */
    function getFormId(){
      return "";
    }
  }
?>