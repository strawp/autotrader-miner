<?php
  /*
  * Unit test for common javascript errors (to the extent that you can in PHP)
  */
  require_once( "settings.php" );
  require_once( "lib/wkpdf.class.php" );
  class TestOfWkhtmltopdf extends UnitTestCase {
    
    function __construct(){
    }
    /**
    * Test that there is a file where WKPDF_PATH says there is
    */
    function testBinaryPresent(){
      $this->assertTrue( file_exists( WKPDF_PATH ) );
    }
    
    /**
    * Test that WKPDF can render a pdf
    */
    function testRenderPdf(){
      $pdf = new WKPDF();
      $pdf->set_html( "<html><body><p>Hello world</p></body></html>" );
      $pdf->render();
      $this->assertFalse( $pdf->output(WKPDF::$PDF_ASSTRING,'') == "" );
    }
    
    /**
    * Test that WKPDF can write to the 
    */
    function testWritePdf(){
      $pdf = new WKPDF();
      $pdf->set_html( "<html><body><p>Hello world</p></body></html>" );
      $pdf->render();
      $filename = SITE_TEMPDIR."testRenderPdf_".date("YmdHis").".pdf";
      $pdf->output( WKPDF::$PDF_SAVEFILE, $filename );
      $this->assertTrue( file_exists( $filename ) );
      $this->assertNotEqual( filesize( $filename ), 0 );
      $this->assertTrue( unlink( $filename ) );
    }
  }
?>
