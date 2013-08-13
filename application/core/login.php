<?php
  // Login info handler
  if( SessionUser::isLoggedIn() ){
    echo "    <p id=\"login_info\">You are logged in as <a href=\"".SITE_ROOT."wizard/my_profile\">".h(SessionUser::getFullName())." (".h(SessionUser::getProperty("username"))
      .")</a>. <a href=\"".SITE_ROOT."logout\">Log out</a></p>";
  }
?>