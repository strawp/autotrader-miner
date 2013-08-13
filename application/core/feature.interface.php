<?php
  /**
  * Interface for describing site features
  */
  interface iFeature {
  
    /**
    * Return description of what this feature does
    * @return string
    */
    public function getFeatureDescription();
    
  }
?>