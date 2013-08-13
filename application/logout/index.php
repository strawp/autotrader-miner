<?php
  require( "../core/settings.php" );
  require( "core/header.php" );
?>  
    <h2>Logout</h2>
    <form action="_logout.php" id="frmLogout" method="post">
      <p>Are you sure you want to log out?</p>
      <input type="submit" value="Yes" name="btnSubmit" class="button" />
    </form>
<?php
  require( "core/footer.php" );
?>