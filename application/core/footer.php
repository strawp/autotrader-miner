    </div>
<?php if( !isset( $_GET["_contentonly"] ) ){ ?>
  <hr class="pin"/>
    </div>
    <div id="footer">
      <a name="footer" class="anchor"></a>
      <p>Last updated <?php echo date( SITE_DATETIMEFORMAT, SITE_LASTUPDATE ); ?></p><?php if( SessionUser::isLoggedIn() ){ ?>
      <p><a target="_blank" href="<?php 
        echo SITE_ROOT."issue/new/url/".urlencode( urlencode( SITE_PROTOCOL."://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]));
      ?>">I have an issue with this page!</a></p><?php } ?>
      <p class="render_time"><?php echo $query_count; ?> queries, page generated in <?php echo round( microtime(true) - $page_starttime, 3 ); ?>s</p>
      <?php if( $page_enablelogging ) echo renderLog(); ?>
    </div>
  </div><?php } ?>
</body>
</html><?php
  Flash::clear();
?>