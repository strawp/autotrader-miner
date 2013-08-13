<?php
 
  /**
  * Format a figure for the Y axis label (1000s)
  */
  function yLabelFormat( $label ){
    return number_format( $label / 100000 )."k"; 
  }
  /**
  * Format a figure for graph y axis labels
  */
  function yLabelNumberFormat( $label ){
    return number_format( $label ); 
  }


?>