<?php
  require_once( "../core/settings.php" );
  require_once( "lib/ntlm_stream.class.php" );
  require_once( "lib/ntlm_soap_client.class.php" );

  // Authentification parameters for NTLM
  class SharepointNTLMSoapClient extends NTLMSoapClient {
  }
  
  // Authenticated stream wrapper for NTLM
  class SharepointNTLMStream extends NTLMStream {
    function SharepointNTLMStream(){
      $this->user = DOMAIN_USER;
      $this->password = DOMAIN_PASS;
      $this->url = "";
    }
  }
  
  // Abstraction class for specific Sharepoint instance
  class SharepointWsdlClient{
    function SharepointWsdlClient(){
      $this->client = false;
    }
    
    /**
    * Register the NTLM HTTP wrapper, connect the client to a specific Sharepoint object
    */
    function prepareClient( $object ){
    
      $url = SHAREPOINT_SUBSITESOAPURL.$object.".asmx?wsdl";
      $this->url = $url;

      // we unregister the current HTTP wrapper
      stream_wrapper_unregister('http');

      // we register the new HTTP wrapper
      stream_wrapper_register('http', 'SharepointNTLMStream') or die("Failed to register protocol");

      // so now all request to a http page will be done by MyServiceProviderNTLMStream.
      // ok now, let's request the wsdl file
      // if everything works fine, you should see the content of the wsdl file
      $options = array();
			$this->client = new SharepointNTLMSoapClient( $url, array( 'features' => SOAP_SINGLE_ELEMENT_ARRAYS ) );
      $this->client->user = DOMAIN_USER;
      $this->client->password = DOMAIN_PASS;
      if( !$this->client ) die( "Client failed to initialise" );
    }
    
    /**
    * Unregister the NTLM HTTP wrapper
    */
    function restoreWrapper(){
			// restore the original http protocole
      stream_wrapper_restore('http');
    }
    
    /**
    * Prepare and return result
    */
    function returnResult( $result, $method ){
      $struct = $method."Result";
      // echo "\n\n$method\n";
      if( isset( $result->$struct->any ) ){ 
        $xml = $result->$struct->any;
        $xml = $this->sanitiseXml( $xml );
        return simplexml_load_string( $xml );
      }
      return $result;
    }
    
    /**
    * Clean up the XML
    */
    function sanitiseXml( $xml ){
    
      // Open tag
      $xml = preg_replace( "/<\w+:([^>]+)>/", "<$1>", $xml );
      
      // Close tag
      $xml = preg_replace( "/<\/\w+:([^>]+)>/", "</$1>", $xml );
      return $xml;
    }
    
    /**
    * Rename attributes
    */
    function renameAttributes( $data ){
      $aReturn = array();
      if( !isset( $data->row ) ){
        return false;
      }
      foreach( $data->row as $row ){
        $rtn = array();
        foreach( $row->attributes() as $key => $attrib ){
          $key = preg_replace( "/^ows_/", "", $key );
          if( strstr( $attrib, ";#" ) !== false ) $attrib = preg_split( "/;#/", $attrib ); // OK
          else $attrib = trim( $attrib );
          $rtn[$key] = $attrib;
        }
        $aReturn[] = $rtn;
      }
      return $aReturn;
    }
    
    /**
    * Format a link for Sharepoint
    */
    function formatSharepointLink( $url, $text ){
      $url_len = 254 - strlen( ", ".$text );
      return substr( $url, 0, $url_len ).", ".$text;
    }
    
    /**
    * Run an update query on the list
    */
    function UpdateListItems( $list, $query ){
      $this->prepareClient( "Lists" );
      // $result = $this->client->UpdateListItems( $list, $query );
      
      // Build request manually
      $request = '<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <UpdateListItems xmlns="http://schemas.microsoft.com/sharepoint/soap/">
      <listName>'.$list.'</listName>
      <updates>
        '.$query.'
      </updates>
    </UpdateListItems> 
  </soap:Body>
</soap:Envelope>';
      
      $result = $this->client->__doRequest( $request, preg_replace( "/\?.+$/", "", $this->url ), "http://schemas.microsoft.com/sharepoint/soap/UpdateListItems", "1" );
      $xml = simplexml_load_string( $this->sanitiseXml( $result ) );
      $this->restoreWrapper();
      $attr = $xml->Body->UpdateListItemsResponse->UpdateListItemsResult->Results->Result;
      if( !$attr ){
        echo "No usable attributes found in:\n";
        print_r( $xml );
        exit;
      }
      if( !isset( $attr->ErrorCode ) ) return false;
      if( hexdec( $attr->ErrorCode ) > 0 ){ 
        echo "ERROR: ".$attr->ErrorText."\n";
        return array( "ErrorCode" => $attr->ErrorCode, "ErrorText" => $attr->ErrorText );
      }
      return $this->renameAttributes( $attr );
    }
    
    /**
    * Seperate specific method for querying list
    */
    function GetListItemsByQuery( $list, $query ){
      $this->prepareClient( "Lists" );
      
      // Build request manually
      $request = '<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <GetListItems xmlns="http://schemas.microsoft.com/sharepoint/soap/">
      <listName>'.$list.'</listName>
      <query>
        '.$query.'
      </query>
      <viewFields>
        <ViewFields xmlns="" />
      </viewFields>
      <queryOptions>
        <QueryOptions xmlns="" />
      </queryOptions>
    </GetListItems> 
  </soap:Body>
</soap:Envelope>';
      
      $result = $this->client->__doRequest( $request, preg_replace( "/\?.+$/", "", $this->url ), "http://schemas.microsoft.com/sharepoint/soap/GetListItems", "1" );
      $xml = simplexml_load_string( $this->sanitiseXml( $result ) );
      $this->restoreWrapper();
      $rlt = $xml->Body->GetListItemsResponse->GetListItemsResult->listitems->data;
      if( !$rlt ) return false;
      $rlt = $this->renameAttributes( $rlt );
      return $rlt;
    }
    
    /**
    * Get info on a user by login name
    */
    function GetUserInfo( $name ){
      $this->prepareClient( "UserGroup" );
      $result = $this->client->GetUserInfo( array( "userLoginName" => $name ) );
      $this->restoreWrapper();
      $rlt = $this->returnResult( $result, "GetUserInfo" );
      return $rlt;
    }
    
    /**
    * Get info on a user by login name
    */
    function ResolveUser( $name ){
      $this->prepareClient( "People" );
      $result = $this->client->ResolvePrincipals( 
        array( 
          "principalKeys" => array( "string" => $name ),
          "principalType" => "User",
          "addToUserInfoList" => true
        )
      );
      $this->restoreWrapper();
      $rlt = $this->returnResult( $result, "ResolvePrincipals" );
      
      // Save this user's ID
      if( isset( $rlt->ResolvePrincipalsResult->PrincipalInfo[0]->UserInfoID ) ){
        $id = intval( $rlt->ResolvePrincipalsResult->PrincipalInfo[0]->UserInfoID );
        $db = new DB();
        $sql = "UPDATE user SET sharepoint_idx = ".$id." WHERE name = '".$db->escape( $name )."'";
        $db->query( $sql );
        if( $id < 0 ) return false;
        return $rlt->ResolvePrincipalsResult->PrincipalInfo;
      }
      return false;
    }


    /**
    * Get all lists on site
    */
    function GetListCollection(){
      $this->prepareClient( "Lists" );
      $result = $this->client->GetListCollection();
      $this->restoreWrapper();
      return $this->returnResult( $result, "GetListCollection" );
    }
    
    /**
    * Get a list
    */
    function GetList( $name ){
      $this->prepareClient( "Lists" );
      $result = $this->client->GetList( array( "listName" => $name ) );
      $this->restoreWrapper();
      $rlt = $this->returnResult( $result, "GetList" );
      return $rlt;
    }

    /**
    * Get list items
    */
    function GetListItems( $name ){
      $this->prepareClient( "Lists" );
      $result = $this->client->GetListItems( 
        array( 
          "listName" => $name, 
          "viewName" => "",  
          "query" => null,
          "viewFields" => null,
          "rowLimit" => "",
          "queryOptions" => null,
          "webID" => ""
        ) 
      );
      $data = $this->returnResult( $result, "GetListItems" );
      $this->restoreWrapper();
      $rlt = $data->data;
      if( !$rlt ) return false;
      return $this->renameAttributes( $rlt );
    }
  }
?>
