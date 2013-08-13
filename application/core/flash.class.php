<?php
@session_start();
class Flash{

  public static function setup(){
    if (!isset($_SESSION["flash"]["error"]))    $_SESSION["flash"]["error"]=array();
    if (!isset($_SESSION["flash"]["info"]))     $_SESSION["flash"]["info"]=array();
    if (!isset($_SESSION["flash"]["warning"]))  $_SESSION["flash"]["warning"]=array();
    if (!isset($_SESSION["flash"]["notice"]))   $_SESSION["flash"]["notice"]="";
    if (!isset($_SESSION["flash"]["ok"]))       $_SESSION["flash"]["ok"]=array();
    if (!isset($_SESSION["flash"]["positive"])) $_SESSION["flash"]["positive"]= true;
    if (!isset($_SESSION["flash"]["htmlallowed"])) $_SESSION["flash"]["htmlallowed"] = false;
  }
  
  public static function addError($msg="",$field=null){
    $_SESSION["flash"]["positive"] = false;
    $_SESSION["flash"]["error"][] = array("msg"=>$msg,"field"=>$field);
  }
  
  public static function addErrors( $aErrors, $notice="" ){
    if( $notice != "" ) self::setNotice($notice);
    foreach( $aErrors as $error ){ 
      Flash::addError($error["message"],$error["fieldname"]); 
    }
  }
  
  public static function addWarning($msg="",$field=null){
    $_SESSION["flash"]["warning"][] = array("msg"=>$msg,"field"=>$field);
  }

  public static function addInfo($msg="",$field=null){
    $_SESSION["flash"]["info"][] = array("msg"=>$msg,"field"=>$field);
  }
  public static function addOk($msg="",$field=null){
    $_SESSION["flash"]["ok"][] = array("msg"=>$msg,"field"=>$field);
  }

  public static function getOk(){
    return $_SESSION["flash"]["ok"];
  }
  public static function getInfo(){
    return $_SESSION["flash"]["info"];
  }
  public static function getError(){
    return $_SESSION["flash"]["error"];
  }
  public static function getWarning(){
    return $_SESSION["flash"]["warning"];
  }
  static public function getNotice(){
    return $_SESSION["flash"]["notice"];
  }

  static public function setNotice($str=""){
    $_SESSION["flash"]["notice"] =$str;
  }
  
  static public function setByKey($key="",$val=""){
    $_SESSION["flash"][$key] =$val;
  }

  static public function hasMessages(){
    if(
      count(Flash::getOk())>0 ||
      count(Flash::getInfo())>0 ||
      count(Flash::getError())>0 ||
      count(Flash::getWarning())>0 ||
      strlen(Flash::getNotice())>0
    ) return true;
    return false;
  }
  
  public static function setHtmlAllowed($status = true){
    $_SESSION["flash"]["htmlallowed"] = $status;
  }
  
  public static function isHtmlAllowed(){
    return $_SESSION["flash"]["htmlallowed"];
  }

  public static function isOk(){
    if (count($_SESSION["flash"]["error"])<1 && $_SESSION["flash"]["positive"]) return true;
    return false;
  }
  
  public static function clear(){
    unset($_SESSION["flash"]);
    Flash::setup();
  }

  public static function getHtml(){
    $html = "";
    if( Flash::hasMessages()) {
      $class = (Flash::isOk()) ? "good" : "bad";
      $html .= "      <div id=\"flash\" class=\"$class\">";
      if( Flash::isHtmlAllowed() ){
        $html .= Flash::getNotice();
      }else{
        $html .= htmlentities( Flash::getNotice() );
      }
      $arr = array('Error','Warning','Info','Ok');
      foreach (array('ok','error','warning','info') as $one){
        $func = "get".ucfirst($one);
        if( count( Flash::$func() ) == 1 ){
          $messages = Flash::$func();
          $html .= "\n        <div class='$one'>";
          if( Flash::isHtmlAllowed() ){
            $html .= $messages[0]["msg"];
          }else{
            $html .= htmlentities( $messages[0]["msg"] );
          }
          $html .= "</div>\n";
        }elseif( count(Flash::$func())>0 ){
          $one .= (count(Flash::$func())==1) ? " nolist" : "";
          $html .= "\n      <ul class='$one'>\n";
          foreach( Flash::$func() as $one){ 
            $html .= "          <li>";
            if( Flash::isHtmlAllowed() ){
              $html .= $one['msg'];
            }else{
              $html .= htmlentities($one['msg']);
            }
            $html .= "</li>\n";  
          }
          $html .= "        </ul>\n      ";
        }
      }
      $html .= "      </div>\n";
    }
    return $html;
  }
}

Flash::setup();

