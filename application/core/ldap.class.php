<?php
  
  // LDAP data access class
  
  class Ldap{
    function Ldap(){
      $this->hastable = false;
      $this->currentrow = 0;
      $this->numrows = 0;
      $this->attribs = array(
        "samaccountname",
        "givenname",
        "sn",
        "title",
        "mail",
        "mailNickname",
        "description",
      );
    }
    
    /**
    * Bind to the server using given credentials
    */
    function bind( $username, $password, $role, $firstname, $lastname ){
      $this->ds = ldap_connect( LDAP_URL );  
      if( $this->ds ){
        if( $role == "Staff" || $role == "Student" ){
          $cn = $this->escape( $firstname )." ".$this->escape( $lastname )." (".$this->escape( $username ).")";
        }else{
          $cn = $this->escape( $username );
        }
        $r=@ldap_bind( 
          $this->ds, 
          "CN=".$cn.",OU=".$this->escape( $role ).",".LDAP_BASE, 
          $password
        );
      }else{die("connect failed");}
      if( !$r ) $this->ds = false;
    }
    
    /**
    * Bind to the server using application's domain credentials
    */
    function bindWithApplicationCredentials(){
      return $this->bind( DOMAIN_USER, DOMAIN_PASS, DOMAIN_ROLE, DOMAIN_FIRSTNAME, DOMAIN_LASTNAME );
    }
    
    function escape( $str ){
      $aChars = array( "+", '<', '>', ';', '/', ',', '"', '=' );
      $return = $str;
      $return = str_replace( '\\', '\\\\', $return );
      $return = preg_replace( "/^#/", "\\#", $return );
      $return = preg_replace( "/^ | $/", "\\ ", $return );
      foreach( $aChars as $char ){
        $return = str_replace( $char, "\\".$char, $return );
      }
      // echo "Was: $str => $return<br>\n";
      return $return;
    }
    function search( $base, $str, $subtree=false ){
      addLogMessage( "Start", "Ldap->search()" );
      $this->currentrow = 0;
      if( !$this->ds ){ 
        addLogMessage( "End", "Ldap->search()" );
        return false;
      }
      if( $base != "" ) $base .= ",";
      if( $subtree ) $sr=@ldap_search( $this->ds, $base.LDAP_BASE, $str );
      else $sr=@ldap_list( $this->ds, $base.LDAP_BASE, $str );
      $this->rlt = @ldap_get_entries( $this->ds, $sr );
      $this->numrows = isset( $this->rlt["count"] ) ? $this->rlt["count"] : 0;
      // print_r( $this->rlt );
      addLogMessage( "End", "Ldap->search()" );
      return $this->rlt;
    }
    function close(){
      ldap_close( $this->ds );
    }
    
    function fetchRow(){
      if( !isset( $this->rlt[$this->currentrow] ) ) return false;
      $rlt = $this->rlt[$this->currentrow];
      $row = array(
        "id" => $this->currentrow
      );
      $row["name"] = isset( $rlt["samaccountname"][0] ) ? $rlt["samaccountname"][0] : "";
      $row["first_name"] = isset( $rlt["givenname"][0] ) ? $rlt["givenname"][0] : "";
      $row["last_name"] = isset( $rlt["sn"][0] ) ? $rlt["sn"][0] : "";
      $row["title"] = isset( $rlt["title"][0] ) ? $rlt["title"][0] : "";
      $this->currentrow++;
      return $row;
    }
    
    function dataSeek( $rownum ){
      if( isset( $this->rlt[$rownum] ) ) $this->currentrow = $rownum;
    }
    
    function getByMail( $mail ){
      return $this->search( "", "mail=".$this->escape( $mail ) );
    }
    function getByNick( $nick ){
      return $this->search( "", "mailNickname=".$this->escape( $nick ) );
    }
  }
  

?>
