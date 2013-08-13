<?php
  require_once( "core/navigation.class.php" );
  require_once( "core/navigation_item.class.php" );
  
  // Render menus
  if( SessionUser::isLoggedIn()){
  
    // Check cache now for expired navigation
    if( Cache::hasModel( "Navigation" ) ){
      addLogMessage( "navigation cached, retrieving" );
      $menu = Cache::getModel("Navigation");
      addLogMessage( sizeof( $menu->aItems )." items" );
    }
    
    // If it's expired and created new, there will be no items
    if( !Cache::hasModel( "Navigation" ) || sizeof( $menu->aItems ) == 0 ){
      $menu = new Navigation();
      $menu->addItem( new NavigationItem( "Cars", "car" ) ); 
      $menu->addItem( new NavigationItem( "Searches", "search" ) ); 
      $menu->addItem( new NavigationItem( "Home", "/" ) );
      $menu->addItem( new NavigationItem( "Site map", "map" ) );
      // $menu->addItem( new NavigationItem( "Log out", "logout" ) );
      Cache::storeModel( $menu, "Navigation" );
    }else{
      $menu = Cache::getModel( "Navigation" );
    }
    
    echo $menu->renderBreadCrumbTrail();
    echo $menu->render();
  }
?>
