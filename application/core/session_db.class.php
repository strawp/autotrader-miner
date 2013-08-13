<?php

class SessionDb{
  
  public static function init(){
    if (!isset($_SESSION["DB"]["queries"]["lastSearch"])) $_SESSION["DB"]["queries"]["lastSearch"] = "";
  }

  public static function getLastSearchSql(){
    return $_SESSION["DB"]["queries"]["lastSearch"];
  }

  public static function setLastSearchSql($value = ""){
    $_SESSION["DB"]["queries"]["lastSearch"] = $value;
  }

}
