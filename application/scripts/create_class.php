<?php
  
  /*
    Interactive mode class creator
  */
  
  require_once( "../core/settings.php" );
  require_once( "core/model.class.php" );

  require_once( "core/field.class.php" );
  require_once( "cli_compat.php" );
  echo "Interactive mode class creator (ctrl-c to exit)\n";
  
  main_menu();
  
  function main_menu(){
    global $aClasses;
    $aClasses = array();
    while( true ){
      echo "\n1.  Create a new class\n";
      echo "2.  Remove a class\n";
      echo "3.  Save and exit\n";
      echo "4.  Save, sync with DB and exit\n";
      $aLookup = array();
      if( sizeof( $aClasses ) > 0 ){
        echo "Edit one of the following:\n";
        $i=5;
        foreach( $aClasses as $key => $class ){
          echo "  ".$i.". ".$class->name."\n";
          $aLookup[$i] = $key;
          $i++;
        }
      }
      $opt = prompt( "Enter an option" );
      switch( $opt ){
        case 1:
          create_class();
          break;
          
        case 2:
          remove_class();
          break;
          
        case 3:
          save();
          exit;
          break;
          
        case 4:
          save_sync();
          exit;
          break;
        
        default:
          $i = intval( $opt );
          if( $opt > 4 ) edit_class( $aLookup[$opt] );
          break;
      }
    }
  }
  
  function save_sync(){
    global $aClasses;
    save();
    require_once( "sync_db.php" );
  }
  
  function save(){
    global $aClasses;
    foreach( $aClasses as $class ){
      $class->createClassFile();
    }
  }
  
  function remove_class(){
    global $aClasses;
    if( sizeof( $aClasses ) > 0 ){
      for( $i=0; $i<sizeof( $aClasses ); $i++ ){
        echo ( $i+1 ).".  ".$aClasses[$i]->name."\n";
      }
      $opt = intval( prompt( "Choose a class to remove" ) );
      if( $opt < sizeof( $aClasses ) + 1 ) unset( $aClasses[$opt-1] );
    }
  }
  
  function edit_class( $classindex ){
    global $aClasses;
    $class = $aClasses[$classindex];
    $opt = 1;
    while( $opt != "" ){
      echo "\n".$class->describe();
      echo "\n1.  Rename class\n";
      echo "2.  Add a field\n";
      $aLookup = array();
      $i=3;
      if( sizeof( $class->aFields ) > 0 ){
        echo "Rename a field:\n";
        foreach( $class->aFields as $field ){
          echo "  ".$i.".  ".$field->name."\n";
          $aLookup[$i] = $field->columnname;
          $i++;
        }
        echo "Remove a field:\n";
        $start = sizeof( $class->aFields ) + 2;
        foreach( $class->aFields as $field ){
          echo "  ".$i.".  ".$field->name."\n";
          $aLookup[$i] = $field->columnname;
          $i++;
        }
      }
      $opt = intval( prompt( "Enter an option" ) );
      
      // Rename class
      if( $opt == 1 ){
        $name = prompt( "Rename \"".$class->name."\" to" );
        if( $name != "" ) $newclass = new Model( $name );
        $newclass->aFields = $class->aFields;
        $class = $newclass;
      }
      
      if( $opt == 2 ){
        $name = prompt( "Add a field" );
        if( $name != "" ) $class->addField( Field::create( $name ) );
      }
      
      // Rename field
      elseif( $opt > 2 && $opt < sizeof( $class->aFields ) + 3 ){
        $name = prompt( "Rename ".$class->aFields[$aLookup[$opt]]->name );
        $class->aFields[$aLookup[$opt]] = Field::create( $name );
      }
      
      // Remove field
      elseif( $opt > ( sizeof( $class->aFields ) * 2 ) && $opt < $i ){
        /*
        print_r( $aLookup );
        print_r( $class->aFields );
        */
        echo "Removing ".$class->aFields[$aLookup[$opt]]->name."\n";
        unset( $class->aFields[$aLookup[$opt]] );
      }
    }
    $aClasses[$classindex] = $class;
  }
  
  function create_class(){
    global $aClasses;
    $name = prompt( "Class name" );
    $class = new Model( $name );
    
    // Create fields
    $opt = 1;
    while( $opt != "" ){
      $opt = prompt( "Add a field");
      if( $opt == "" ) continue; 
      $f = Field::create( $opt );
      if( $f === false ) continue;
      $class->addField( $f );
      echo "\"".$opt."\"\n";
    }
    $aClasses[] = $class;
    echo "\nClass created\n";
    return true;
  }
?>