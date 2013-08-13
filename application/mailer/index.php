<?php
  require_once( "../core/settings.php" );

  require_once( "core/field.class.php" );
  require_once( "core/functions.php" );

  if (!SessionUser::isAdmin()){
    $return_url = "Location: /";
    header( $return_url );
    exit;
  }

  require_once( "core/header.php" );

  echo '<form method="post" id="mailer" action="_action.php">'."\n";
  echo "<fieldset><legend>Select Users or Groups</legend>\n"; 

  $field = Field::create( "lstUserId","listby=first_name,last_name;multiselect=1");
  $field->appendHTML="<input type=\"submit\" class=\"addImgBtn\" name=\"addRcpBtn\" value=\"\"/>\n";
  echo($field->render());

  $field = Field::create( "lstUserGroupId","multiselect=1");
  $field->appendHTML="<input type=\"submit\" class=\"addImgBtn\" name=\"addGrpBtn\" value=\"\"/>\n";
  echo($field->render());
  echo "</fieldset>";

  echo "<fieldset><legend>Add all users between dates</legend>\n"; 
  echo "<div class='help'> This field lets you automatically add users that have logged in between the specified dates </div>";
  $field = Field::create( "dteStartDate","displayname=Start date");
  echo($field->render());

  $field = Field::create( "dteEndDate","displayname=End date;default=now");
  $field->appendHTML="<input type=\"submit\" class=\"addImgBtn\" name=\"srchRcpBtn\" value=\"\"/>\n";
  echo($field->render());

  echo "</fieldset>";

  if (count((array)$_SESSION["mods"]["mailer"]["user"])!=0 || count((array)$_SESSION["mods"]["mailer"]["group"]) != 0){

    echo "<fieldset><legend>Selected recipients:</legend>\n";

    if (count((array)$_SESSION["mods"]["mailer"]["user"])>0){
      $f = Field::create( "lstSelectedUsers");
      foreach((array)$_SESSION["mods"]["mailer"]["user"] as $one){
        // $user = new User($one);
        $user = Cache::getModel( "User" );
        $user->get( $one );
        $f->listitems[$one]=$user->getField("first_name")->toString()." ".$user->getField("last_name")->toString();
      }
      $f->multiselect=true;
      $f->appendHTML="<input type=\"submit\" class=\"removeBtn\" name=\"delRcpBtn\" value=\"\"/>"; 
      echo ($f->render());
    }
    if (count((array)$_SESSION["mods"]["mailer"]["group"])>0){
      $f = Field::create( "lstSelectedGroups");
      foreach((array)$_SESSION["mods"]["mailer"]["group"] as $one){
        $group = new UserGroup($one);
        $group->get();
        $f->listitems[$one]=$group->getField("name")->toString();
      }
      $f->multiselect=true;
      $f->appendHTML="<input type=\"submit\" class=\"removeBtn\" name=\"delGrpBtn\" value=\"\"/>"; 
      echo ($f->render());
    }
    echo "
      </fieldset>
      ";
  }

  echo "
    <fieldset><legend>E-Mail content:</legend>
    <div class=\"field str\"><label>Subject:</label><input type=\"text\" name=\"emailSubject\" size=\"100\"/></div>
    <div class=\"field\"><label>Message:</label><textarea name=\"emailText\"></textarea></div>";
  echo "</fieldset>";

  echo "    <div class=\"controls\">\n";
  echo "      <ul class=\"options\">\n";
  echo "        <li><input type=\"submit\" class=\"button\" name=\"sendMailBtn\" value=\"Send email\"/></li>\n";
  echo "        <li><input type=\"submit\" class=\"button\" name=\"resetBtn\" value=\"Reset form\"/></li>\n";
  echo "      </ul>\n";
  echo "      <input type=\"hidden\" name=\"sessidhash\" value=\"".SessionUser::getProperty("sessidhash")."\" />\n";
  echo "    </div>\n";

  echo"
    </form>
    ";   


  require_once( "core/footer.php" );
?>