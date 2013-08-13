<?php
  /*
  * Unit test for the oracle database model
  */
  require_once( "settings.php" );
  class TestOfRecacheDependency extends UnitTestCase {
    
    function __construct(){
      $this->modelname = "TestModel";
      $this->tablename = "test_model";
    }
    
    function testNoModelHasLoopedCacheDependency(){
      
      // Get list of models
      $sql = "SELECT name FROM model";
      $db = new DB();
      $db->query( $sql );
      while( $row = $db->fetchRow() ){
        $modelname = underscoreToCamel( $row["name"] );
        $rlt = self::modelHasLoopedCacheDependency($modelname);
        if( !is_array($rlt) ){
          $this->assertFalse($rlt);
        }else{
          $this->assertFalse($rlt[0],"Whilst looking at $modelname, found looped reference in ".$rlt[1]);
        }
      }
    }
    
    /**
    * Function for testing if a specific model has a looping cache dependency
    */
    static function modelHasLoopedCacheDependency( $modelname ){
      $m = new $modelname();
      $oTree = $m->getCacheDependencyTree();
      
      // List of all models referenced in tree
      $aPaths = array();
      $aModels = self::walkBranch( $oTree, array(), $aPaths );
      // echo "\n\n".$modelname."\n";
      
      // Check there isn't two of something
      foreach( $aPaths as $aModels ){
        $lastmodel = "";
        sort( $aModels );
        foreach( $aModels as $model ){
          if( $model == $lastmodel ) return array( true, $model );
          $lastmodel = $model;
        }
      }
      return false;
    }
    
    /**
    * Function for recursively walking a branch
    */
    static function walkBranch( $branch, $aPath=array(), &$aPaths ){
      $aModels = array();
      $aPath[] = $branch->name;
      $aRtn = array();
      if( sizeof( $branch->dependants ) == 0 ){
        $aPaths[] = $aPath;
      }else{
        foreach( $branch->dependants as $dep ){
          $aRtn = self::walkBranch( $dep, $aPath, $aPaths );
        }
      }
      return $aRtn;
    }
  }
?>