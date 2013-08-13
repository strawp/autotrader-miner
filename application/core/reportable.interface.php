<?php
  /**
  * Interface for automated email reports framework
  */
  interface iReportable {
  
    /**
    * Return list of formats that this class supports
    * @return array
    */
    public function getAvailableReportFormats();
    
    /**
    * Return list of subscription types supported by this class
    */
    public function getAvailableSubscriptionTypes();
    
    /**
    * Send a report using the currently instanciated model in the format specified to the user specified
    * @param string $format
    * @param object $user
    * @param string suggested report name (optional)
    */
    public function sendEmailReport($format,$user,$name="");
    
    /**
     * Send an instance of this report to each member of the supplied group code
     * 
     * @param string $group
     * @param string $format
     * @return void
     */
    function sendToUsersInGroup( $group="", $format="" );
    
    /**
    * Get the name for the object being reported on
    */
    public function getDisplayName();
    
    /**
    * Determine if the passed user is authorised to read this report
    */
    public function userHasReadAccess( $user=null );
  }
?>