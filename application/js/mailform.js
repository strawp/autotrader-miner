// Make the "Other CC" list an autocomplete list of users 
function initOtherCC(){
  if( $( "form.mail div.field.occ input" ).length == 0 ) return; 
  function split( val ) {
    return val.split( /[,;]\s*/ );
  }
  function extractLast( term ) {
    return split( term ).pop();
  }
  $("form.mail div.field.occ input.ema").each(function(){
    $( this )
			// don't navigate away from the field on tab when selecting an item
			.bind( "keydown", function( event ) {
				if ( event.keyCode === $.ui.keyCode.TAB &&
						$( this ).data( "autocomplete" ).menu.active ) {
					event.preventDefault();
				}
			})
			.autocomplete({
				source: function( request, response ) {
          $.ajax( { 
            type: "POST", 
            dataType: 'json',
            url: globalSettings.site_root + "user/_ajax_search",
            data: "term="+extractLast( request.term ),
            success: response
          });
				},
				search: function() {
					// custom minLength
					var term = extractLast( this.value );
					if ( term.length < 2 ) {
						return false;
					}
				},
				focus: function() {
					// prevent value inserted on focus
					return false;
				},
				select: function( event, ui ) {
					var terms = split( this.value );
					// remove the current input
					terms.pop();
					// add the selected item
					terms.push( ui.item.value );
					// add placeholder to get the comma-and-space at the end
					terms.push( "" );
					this.value = terms.join( ", " );
					return false;
				}
			});    
  });
  resizeFields();
}
function initAttachments(){
  createAttachmentField("form.mail div.field.subject");
}

function resizeFields(){
  // Make everything max width
  $("form.mail div.field").each(function(){
    var fieldwidth = parseInt($(this).css("width"));
    fieldwidth -= parseInt($(this).css("padding-left")) 
    fieldwidth -= parseInt($(this).css("padding-right")) 
    fieldwidth -= parseInt($(this).css("margin-left")) 
    fieldwidth -= parseInt($(this).css("margin-right"));
    
    var labelwidth = parseInt($(this).children("label").css("width"));
    labelwidth += parseInt($(this).css("padding-right"));
    labelwidth += parseInt($(this).css("padding-left"));
    labelwidth += parseInt($(this).css("margin-right"));
    labelwidth += parseInt($(this).css("margin-left"));
    
    $(this).children("input, textarea, select").each(function(){
        if( this.type != "checkbox" && !$(this).hasClass("expand") ) $(this).css("width", fieldwidth - labelwidth - 100 );
    });
  });
  $("form.mail div.txt textarea").css("height", "20em" );
}
$(initAttachments);
$(initOtherCC);
$(window).resize(resizeFields);
