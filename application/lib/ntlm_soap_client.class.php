<?php
  class NTLMSoapClient extends SoapClient {
    function __doRequest( $request, $location, $action, $version ){
      
      /*
      $args = array( "request", "location", "action", "version" );
      foreach( $args as $arg ){
        echo $arg.": ".$$arg."\n";
      }
      */
      
      $headers = array(
        'Method: POST',
        'Connection: Keep-Alive',
        'User-Agent: PHP-SOAP-CURL',
        'Content-Type: text/xml; charset=utf-8',
        'SOAPAction: '.$action,
      );
      /*
      echo $request."\n";
      $request = trim( preg_replace( "/&#\d\d;/", "", str_replace( "&gt;", ">", str_replace( "&lt;", "<", $request ) ) ) );
      echo $request."\n";
      */

      $this->__last_request_headers = $headers;
      $ch = curl_init($location);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($ch, CURLOPT_POST, true );
      curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
      curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
      curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_NTLM);
      curl_setopt($ch, CURLOPT_USERPWD, $this->user.':'.$this->password);
      $response = curl_exec($ch);
      return trim( $response );
    }
    function __getLastRequestHeaders() {
      return implode("\n", $this->__last_request_headers)."\n";
    }
  }
?>