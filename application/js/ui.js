/*
  User Interface prettification and helpfulness
*/

function init(){
  
  // Date fields
  initDateFields( "dte" );
  initDateFields( "dtm" );
  initNumericFields();
  initTimeFields();
  initUrlFields();
  
  // Text search fields
  initAutoComplete();

  // Field select checkboxes
  initFieldSelectCheckboxes();
  
  // Mail form
  initMailForm();
  
  // Search page
  initSearchPage();
  
  // Primary navigation
  $("div.navigation > ul > li.children").each(
    function(){
      
      // Collapse child lists
      $(this).children( "ul" ).hide();
      
      // Toggle event
      eTog = function(){
        if( window.ActiveXObject ) $(this).parent().children( "ul" ).toggle();
        else $(this).parent().children( "ul" ).slideToggle();
      }

      $(this).children("span").unbind("click");
      $(this).children("span").each(function(){
        this.onclick=eTog; // Ensures only one click method is bound 
      });
    }
  );
  
  // Open "current"
  $( "div.navigation > ul > li.current" ).each(
    function(){
      $(this).children("ul").show();
    }
  );


  
  // Reportable objects options menu
  $("div.optionscontainer").mouseenter(function(){
    ul = $(this).find("ul");
    bottom = ul.offset().top + ul.outerHeight();
    fold = $(window).scrollTop() + $(window).height();
    if( bottom > fold ){ 
      overlap = bottom - fold;
      newtop = ul.offset().top - overlap - 20;
      ul.offset( { top: newtop, left: ul.offset().left + 20 } );
    }
  });
  
  // Members interface list
  initMemberInterface();
  
  // Expanded search lists interface
  initSearchLists();

  // Grid inputs - init totals calculations
  $("table.grd td input.text").change( changeGridTotals );
  changeGridTotals();
  
  // Give focus to the first field of the first form
  if( !window.location.href.match( "#" ) && $("#TB_ajaxContent").length == 0 ){
    if( $("#content form input").length > 0 ){
      if( $("#content form input")[0].type != "hidden" ){ 
        $("#content form input:first").focus();
      }
    }
	}
  
  // Page flash
  if( $("#flash").length > 0 ){
    if( $("#flash input.ok").length == 0 ){ 
      $("#flash").prepend("<div class=\"controls\"><input type=\"button\" class=\"button ok\" value=\"OK\" /></div>");
      $("#flash input.ok").click(function(){
        $("#flash").hide("blind", 500 );
      });
    }
    // Hide if something else is clicked
    $('body').click(function(event) {
        if (!$(event.target).closest('#flash').length) {
          $('#flash').not(":hidden").hide("blind",500);
        }
    });
    
  }
  
  // Do clever things with repeaters
  $("form.rpt").each( initRepeater );
  
  // Sortable repeater tables
  $("table.rpt").tableSorter();
  $("table.rpt th").addClass("clickable");
  
  // Sortable anything else tables
  $("table.sortable").tableSorter();
  $("table.sortable th").addClass("clickable");
  
  // Expandable text areas
  $("form div.txt textarea").TextAreaExpander(50);
  // $("form div.txt textarea").resizeable();
  
  // Tabbed repeaters
  if( $("#fragment-1").length > 0 && $("ul.fragment_tab").length == 0 ){
    i = 1;
    while( $("#fragment-"+i).length > 0 ){
      i++;
    }
    lasttab = i;
    // console.log( "last tab: " + i );
    list = $("<ul class=\"fragment_tab tab\"></ul>");
    if( $("form.edit").length > 0 ){
      // console.log( document.getElementsByTagName('form')[0] );
      if( $("#fragment-0").length == 0 ) $( document.getElementsByTagName('form')[0] ).wrap( "<div id=\"fragment-0\" class=\"fragment\"></div>" );
      if( $("#fragment-0 > h3").length > 0 ) firsttab = $("#fragment-0 > h3:first").text();
      else firsttab = "Main details";
      list.append( "<li><a href=\"#fragment-0\"><span>" + firsttab + "</span></a></li>" );
    }
    for( i=1; i<lasttab; i++ ){
      list.append( "<li><a href=\"#fragment-" + i + "\"><span>" + $("#fragment-" + i + " h3:first").text() + "</span></a></li>" );
    }
    if( $("#fragment-0").length > 0 ) list.insertBefore( "#fragment-0" );
    else list.insertBefore( "#fragment-1" );
    $("#fragments").tabs();
    
    // Click events for AJAX tabs
    $("div.field.ajx").each(function(){
      var url = $(this).find("a").attr( "href" );
      var id = $(this).parent().get(0).id;
      $("ul.fragment_tab li a").each(function(){
        m = '#' + id + "$";
        if( $(this).attr("href").match( m ) ){
          $(this).click(function(){
            if( $("div#" + id + " a.unloaded").length == 0 ) return;
            url = $("div#"+id+" a.unloaded").attr("href");
            ajxcon = $("div#"+id);
            ajxcon.empty();
            ajxcon.height("100px");
            ajxcon.addClass("busy");
            ajxcon.load(url,function(){
              $(this).removeClass("busy");
            });
          });
        }
      });
    });
  }
  
  // Delete links in lists
  $("table.list td.controls a.delete").each( initAjaxLink );
  $("ul.list a.delete").each( initAjaxLink );
  
  // Inline edit in table
  // $("table.list tr td.controls").each( initInlineEdit );
  if( $("table.list tr").length > 0 ){
    if( $("#content ul.searchoptions button.edit").length == 0 ){
      $("#content ul.searchoptions").append( "<li><button type=\"button\" class=\"button edit\" >Edit these results inline</button></li>" );
      $("#content ul.searchoptions li button.edit").toggle( 
        function(){
          $("table.list tr .collapsed").each(
            function(){
              $(this).removeClass("collapsed");
            }
          );
          $("table.list tr td.controls").each( initInlineEdit );
        },
        function(){
          $("table.list tr td.controls").each( 
            function(){
              $(this).addClass( "collapsed" );
              $(this).empty();
            }
          );
        }
      );
    }
  }
  
  // Search summary button
  if( $( "ul.searchoptions.allowsearchsummary" ).length > 0 && $("ul.searchoptions.allowsearchsummary button.summary").length == 0 ){
    $("#content ul.searchoptions").append( "<li><button type=\"button\" class=\"button summary\">See a statistical summary of this search</button></li>" );
    $("#content ul.searchoptions li button.summary").click( 
      function(){
        if( $("#summary").length == 0 ){
          getSearchSummaryStats();
          $(this).addClass("busy");
        }else{
          $("#summary").slideToggle();
        }
      }
    );
  }
  
  // Members lists
  $("form div.mem a.edit").each( initAjaxLink );
  
  // Dymo label printer
  /*
  if( $("div.edit form.project").length > 0 ){ 
    if( createDymoAddIn() && createDymoLabel() ){
      initDymo();
    }
  }
  */
  
  // "View" links
  $("form div.lst select").change( changeViewLinks );
  
  // Calculation fields
  if( $("div.edit form div.field").length > 0 
    || $("div.new form div.field").length > 0 
    || $("div.wizard form div.field").length > 0 
  ){
  
    // Get repeaters 
    $("div.rpt form").each( function(){
      inf = new Object()
      inf["model"] = camelToUnderscore( this.id.substr( 3 ) );
      initAjaxCalculationForm(inf);
    });
    
    // Main form
    inf = getModelInfo();
    initAjaxCalculationForm(inf);
  }
  
  // Events log
  if( $("#footer ol.event_log").length > 0 ){
    $("#footer ol.event_log li.group_name span").addClass( "clickable" );
    $("#footer ol.event_log li.group_name span").click(
      function(){
        var id = $(this.parentNode).attr( "class" ).match( /\d+/ );
        // console.log( "click " + id );
        $("#footer ol.event_log ol." + id ).toggle();
      }
    );
  }

  initRefreshTimeout();
}

function toggleAdvancedSearch(){
  frm = $("form.search");
  if( frm.hasClass("simple") ){
    
    // Show all hidden fields
    frm.find("div.field").show("blind");
    frm.find("fieldset").show("blind");
    frm.removeClass("simple");
    
    // Toggle text
    $("form.search div.advanced_toggle").text("Show only searched fields");
    
  }else{
    // Simple / advanced view
    frm.find("div.field:not(.searched_on)").hide("blind", function(){
      // Make sure at least one field is visible
      if( frm.find("div.field:visible").length == 0 ){
        frm.find("div.field:first").show("fast",function(){
          // Hide empty field sets
          frm.find("fieldset").each(function(){
            if( frm.find( "div.field.searched_on" ).length == 0 ) $(this).hide();
          });
          $(this).parents("fieldset").show();
        });
      }else{
        if( $(this).parents("fieldset").find("div.field:visible").length == 0 ){
          $(this).parents("fieldset").hide();
        }
      }
    });
    
    frm.addClass("simple");
    
    $("form.search div.advanced_toggle").text("Show all selected fields");
  }
}

function initSearchPage(){
  if( $( "#content form.search" ).length == 0 ) return;
  if( $( "#content form.edit" ).length > 0 ) return;
  if( $( "#content form.new" ).length > 0 ) return;
  // buildExpandedList.prototype.customMethod = ;
  
  toggleAdvancedSearch();
  
  // Show/hide bar
  if( $("form.search div.advanced").length == 0 ){
    $("form.search div.controls").before( "<div class=\"advanced_toggle\">Show all selected fields</div>" );
    $("form.search div.advanced_toggle").click( toggleAdvancedSearch );
  }

  // Confirm cells
  $("table.list tr td.cnf").css("text-indent", "-999em").css("background-image", "url("+globalSettings.site_root+"img/icons/tick.png)").css("background-repeat", "no-repeat").css("width", "20px;").css("background-position", "center");
  $("table.list tr td.cnf:contains('Not confirmed')").css("background-image", "url( "+ globalSettings.site_root+"img/icons/cross.png)");
}

// Get a statistical summary of the search on this page
function getSearchSummaryStats(){
  inf = getSearchInfo();
  args = "model=" + inf["model"];
  if( inf["args"] ){
    $.each( inf["args"], function(i,n){
        if( i.match( /_id$/ ) ) n = n.split( "," );
        args += "&" + i + "=" + n;
      }
    )
  }
  $("h4#results").before("<div id=\"summary\">Crunching numbers...</div>");
  $("#summary")
    .before( "<h4 class=\"clickable summary\">Statistical Summary</h4>\n" )
    .fadeIn()
    .addClass("busy");
  
  $.ajax( { 
    type: "POST", 
    url: globalSettings.site_root + inf["model"] + "/_ajax_get_search_summary",
    data: args,
    success: function(data){
      obj = eval('(' + data + ')');
      writeSearchSummaryStats( obj );
    }
  });
}

// Write the search summary to the page
function writeSearchSummaryStats(){
  html = "";
  var typecount = 0;
  $.each( obj, function(type,field){ 
    typecount++;
    html += "        <h5 class=\""+type.replace( / /, '_' ).toLowerCase()+" clickable\">"+type+"</h5>\n";
    html += "        <ul class=\"type " + type.replace( / /, '_' ).toLowerCase() + "\">\n";
    $.each( field, function( name, stats ){
      html += "          <li>\n";
      html += "            <h6>" + name + "</h6>\n";
      html += "            <table class=\"stats\">\n";
      $.each( stats, function( stat, value ){
        if( value.figure != null ) figure = value.figure
        else figure = value;
        html += "              <tr>\n";
        html += "                <th>" + stat + "</th>\n";
        if( value.filter != null ){
          var arg = "";
          var sl = "";
          var pieces = value.filter.split(/\//);
          for( var i=1; i<pieces.length; i++ ){
            arg += sl + pieces[i];
            sl = "/";
          }
          arg = pieces[0] + "/" + encodeURIComponent( encodeURIComponent( arg ) );
          filter = window.location.href.replace(/#.*$/,'').replace( /\/?$/, '' ) + "/" + arg;
          html += "                <td><a href=\""+filter+"\">" + figure + "</a></td>\n";
        }else{
          html += "                <td>" + figure + "</td>\n";
        }
        html += "              </tr>\n";
      });
      html += "            </table>\n";
      html += "          </li>\n";
    });
    html += "        </ul>\n";
  });
  if( typecount == 0 ){
    html += "       <p>There are no summable fields in this search. Summable fields include information such as cash values.</p>\n";
  }
  $("#summary")
    .removeClass("busy")
    .html(html);
  $("h4.summary.clickable").click( function(){
    $("#summary").slideToggle();
    toggleMinimise(this);
  });
  $("h4.summary.clickable").addClass("maximised");
  
  $("h5.clickable").each( function(){
    type = $(this).attr("class").replace(/^([a-z_]+) .*/,'$1');
    $(this).click(function(){
      type = $(this).attr("class").replace(/^([a-z_]+) .*/,'$1');
      $("ul.type."+type).slideToggle();
      toggleMinimise(this);
    });
    $("ul.type."+type).slideToggle();
    $(this).addClass("minimised");
  });
  
  if( typecount > 0 ){
    $("#summary").prepend("       <p>The following is a statistical summary for all summable fields in the current search. Click the title bars (above) to minimise these stats:</p>\n");
  }
  $("#content ul.searchoptions li button.summary").removeClass("busy");
}

// Toggle class name of an element used to show it has minimise / maximise capability on click
function toggleMinimise(el){
  if( $(el).attr("class").match( /minimised/ ) ){
    $(el).attr("class", $(el).attr("class").replace( /minimised/, 'maximised' ));
  }else{
    $(el).attr("class", $(el).attr("class").replace( /maximised/, 'minimised' ));
  }
}

function toggleEvents(){
  // Get button name
  cls = this.name.substring( 3 ).toLowerCase();
  switch( cls ){
    case "all":
      $("#footer table.event_log tr").show();
      break;
      
    case "header":
      $("#footer table.event_log tr.header").toggle();
      break;
    
    case "db_error":
      $("#footer table.event_log tr.ok").hide();
      $("#footer table.event_log tr.slow").hide();
      $("#footer table.event_log tr.db_query").hide();
      $("#footer table.event_log tr." + cls ).show();
      break;
      
    case "db_query":
      $("#footer table.event_log tr.ok").hide();
      $("#footer table.event_log tr.slow").hide();
      $("#footer table.event_log tr.db_error").show();
      $("#footer table.event_log tr." + cls ).show();
      break;
      
    case "slow":
      $("#footer table.event_log tr.db_query").hide();
      $("#footer table.event_log tr.ok").hide();
      $("#footer table.event_log tr.db_error").hide();
      $("#footer table.event_log tr." + cls ).show();
      break;
  }
}

// Bind class change events to field select checkboxes
function initFieldSelectCheckboxes(){
  if( $("form.field_select div.field").length == 0 ) return;
  
  // Select/deselect all
  if( $("form.field_select div.field.all").length > 1 ) return;
  
  html = "<div class=\"field all\">\
      <label for=\"\">Select / deselect all</label>\
      <span class=\"field_select\">\
        <span class=\"search\">\
          <label title=\"Select all fields to appear in the search form\" for=\"chkSelectAll_search\" class=\"search\">Search</label>\
          <input title=\"Select all fields to appear in the search form\" type=\"checkbox\" id=\"chkSelectAll_search\" name=\"chkSelectAll_search\" class=\"check field_select\">\
        </span>\
        <span class=\"results\">\
          <label title=\"Select all fields to appear in the search results\" for=\"chkSelectAll_results\" class=\"results\">Results</label>\
          <input title=\"Select all fields to appear in the search results\" type=\"checkbox\" id=\"chkSelectAll_results\" name=\"chkSelectAll_results\" class=\"check field_select\">\
        </span>\
      </span>\
    </div>";
  
  $("form.field_select input[type=submit].first").after( html );
  
  $("form.field_select div.field.all input.check").click(function(){
    var checked = this.checked; // ? "checked" : "";
    if( $(this).parent().hasClass("results") ) filter = "results";
    else filter = "search";
    $("form.field_select div.field input." + filter).each(function(){
      this.checked = checked;
      lbl = $(this).siblings("label");
      if( this.checked ) lbl.addClass("selected");
      else lbl.removeClass("selected");
    });
  });
  
  $("form.field_select").addClass("fancy");
  $("form.field_select div.field input").click(function(){
    lbl = $(this).siblings("label");
    if( this.checked ) lbl.addClass("selected");
    else lbl.removeClass("selected");
    // lbl.effect( "highlight", 500 );
  });
}

function initRefreshTimeout(){
  window.setTimeout( 'sessionRefresh();', 30000 );
}

function sessionRefresh(){
  if( $("#frmLogin").length == 0 ){ 
    $.get( 
      globalSettings.site_root + "session_refresh.php", 
      {}, 
      function(data){
        if( data != "" ){
          obj = eval('('+data+')');
          if( !obj.loggedin ){
            htmlAlert( "<strong>Warning:</strong> You have been logged out as your session has expired.", "Session expired", function(){location.reload()} );
          }else{
            initRefreshTimeout();
          }
        }
      }
    );
  }
}

function initAjaxCalculationForm( inf ){
  // Get list of fields that calculated fields use
  args = "model=" + inf["model"];
  if( inf["id"] > 0 ) args += "&id=" + inf["id"];
  $.ajax( { 
    type: "POST", 
    url: globalSettings.site_root + inf["model"] + "/_ajax_get_calculation_dependants",
    data: args,
    success: function(data){
      if( data == "" ) return;
      obj = eval('(' + data + ')');
      for( var i=0; i<obj.fields.length; i++ ){
        if( $( "form." + obj.model + " div.field." + obj.fields[i] ).hasClass( "grd" ) ){
        }else if( $("form." + obj.model + " div.field." + obj.fields[i] ).hasClass( "mem" ) ){
          $("form." + obj.model + " div.field." + obj.fields[i] ).addClass( "_hasdependants" );
        }else{
          $("form." + obj.model + " div.field." + obj.fields[i] + " input" ).change( doAjaxCalculation );
          $("form." + obj.model + " div.field." + obj.fields[i] + " select" ).change( doAjaxCalculation );
        }
      }
    }
  });
}

function doAjaxCalculation(E){
  // Send back form to work out if anything needs updating
  targ = getTarget(E);
  inf = new Object();
  // console.log( $(targ).parents().filter("form").eq(0).attr("id") );
  inf["model"] = camelToUnderscore( $(targ).parents().filter("form").eq(0).attr("id").substring( 3 ) );
  if( $("form." + inf["model"]).length > 0 ) args = $("form." + inf["model"]).formSerialize();
  // else if( $("div.new form." + inf["model"]).length > 0 ) args = $("div.new form." + inf["model"]).formSerialize();
  
  // Replace model with previously defined model (for wizard)
  args = args.replace( /&model=[a-z_]+/, "&model=" + inf["model"] );
  // console.log( args );
  $.ajax( { 
    type: "POST", 
    url: globalSettings.site_root + inf["model"] + "/_ajax_do_calculations",
    data: args,
    success: function(data){
      obj = eval( '(' + data + ')' );
      for( var i=0; i<obj.fields.length; i++ ){
        f = obj.fields[i];
        path = "#frm" + obj.name + " #" + f.name;
        if( $( path ).length == 0 ) continue;
        lst = $(path).get(0);
      
        if( $(path).length > 0 && lst.tagName == "SELECT" ){
          v = f.value;
          $(path).empty();
          $.each( f.list,
            function( id, val ){
              // console.log( id + ": " + val );
              opt = "<option value=\"" + id + "\"";
              if( v == id ){ 
                opt += " selected=\"selected\"";
              }
              opt += ">"+htmlEntities(val)+"</option>\n";
              $(path).append( opt );
            }
          );
          // Auto select if the field is required and there's only one item in it (first item is "Not Selected")
          if( lst.options.length == 2 ) lst.options[1].selected = "selected";
          if( $.browser.msie ) lst.style.width = "auto";
        }
        else if($(path).hasClass("htm")){
          $(path).html(f.string);
        }
        else if($(path).parent().hasClass("cnf")){
          if( f.value === true || f.value == 1 || f.value == "1" ) checked = true;
          else checked = false;
          $(path).attr("checked",checked);
        }
        else if( lst.tagName == "INPUT" ){
          $(lst).val(f.string); 
        }
        else{
          $(lst).text(f.string); 
        }
        // Help text
        $(path).siblings("div.help").children("div.body").html(f.helphtml);
        
        // Required
        if( f.required ){ 
          if( $(path).siblings("label").children( "span.required" ).length == 0 ){ 
            $(path).siblings("label").append("<span class=\"required\">(required)</span>");
          }
        }
        else $(path).siblings("label").children("span.required").remove();
        
        // Display: show / hide
        if( f.display ){
          $(path).parent().show();
        }else{
          $(path).parent().hide();
        }
        
        // Label
        lbl = $(path).parent().find("label");
        lbl.text( f.displayname );
      }
      
      // Allow assignment of a custom method to this function
      if( doAjaxCalculation.prototype.customMethod != null ) doAjaxCalculation.prototype.customMethod(data);
    }
  });
}

function initMailForm(){
  if( $("form.mail").length == 0 ) return;
  if( $("form.mail div.field.template").length > 0 ) return;
  
  $("form.mail div.field.cnf input").each(function(){
    checked = $(this).attr("checked");
    if( checked ){
      $(this).parent().addClass("checked");
    }else{
      $(this).parent().removeClass("checked");
    }
    $(this).change( function(){
      checked = $(this).attr("checked");
      if( checked ){
        $(this).parent().addClass("checked");
      }else{
        $(this).parent().removeClass("checked");
      }
    });
  });
  
  // See if there are any suitable templates for this 
  var inf = getModelInfo();
  $.get( globalSettings.site_root + "email_template/customAction/getAvailableTemplates?for=" + inf["model"] + "&sessidhash=" + globalSettings.sessidhash , function(data){
    aTemplates = eval( data );
    if( aTemplates.length > 0 ){
      
      // Create field
      $("form.mail div.occ").after("<div class=\"field template\"></div>");
      
      // Create label
      $("form.mail div.field.template").append( "<label for=\"lstTemplate\">Available templates:</label>" );
      
      // Create a dropdown list
      $("form.mail div.field.template").append( "<select id=\"lstTemplate\" name=\"lstTemplate\"></select>" );
      $(aTemplates).each(function(){
        $("#lstTemplate").append("<option value=\"" + this.id + "\">" + this.name + "</option>" );
      });
      
      // Load button
      $("#lstTemplate").after("<button id=\"btnLoadTemplate\" type=\"button\">Load</button>");
      $("#btnLoadTemplate").click( function(){
        url = globalSettings.site_root + "email_template/customAction/getCompiledText?template_id=";
        url += $("#lstTemplate").val() + "&reference=" + inf["model"] + "&reference_id=" + inf["id"];
        url += "&sessidhash=" + globalSettings.sessidhash;
        $.get( url, function(data){
          template = eval("(" + data + ")");
          // console.log( template );
          $(template.fields).each(function(){
            switch(this.column){
              case "body":
                $("#txtBody").html(this.string).text();
              
                // Fix for IE not recognising newlines
                $("#txtBody").get(0).value = $("#txtBody").get(0).value.replace( String.fromCharCode(13), String.fromCharCode(10) + String.fromCharCode(13) );
                break;
            }
          });
        });
      });
    }
    mailTemplatesInited();
  });
}
mailTemplatesInited = function(){}; // Declared to hook into later

function createDymoAddIn(){
  var DymoAddIn;
  
  if( navigator.userAgent.match( /MSIE/ ) ){
    // Attempt to instantiate ActiveX control
    try{
      DymoAddIn = new ActiveXObject('DYMO.DymoAddIn');
    }
    catch(err){
      return false;
    }
  }else{
    try{
      if( !DymoAddIn.FileName ){
        netscape.security.PrivilegeManager.enablePrivilege("UniversalXPConnect");
        // create and use the nsDymoAddIn object
        var DymoAddInCID = "@dymo.com/sdk/nsDymoAddIn;1";
        DymoAddIn = Components.classes[DymoAddInCID].createInstance();
        DymoAddIn = DymoAddIn.QueryInterface(Components.interfaces.nsIDymoAddIn2);
      }
    }
    catch (err)
    {
      return false;
    }
  }
  return DymoAddIn;
}

function createDymoLabel(){

  var DymoLabel;
  
  if( navigator.userAgent.match( /MSIE/ ) ){
    // Attempt to instantiate ActiveX control
    try{
      DymoLabel = new ActiveXObject('DYMO.DymoLabels');
    }
    catch(err){
      return false;
    }
  }else{
    // Attempt to instantiate Netscape / Mozilla object
    if( !DymoLabel ){
      try{
        netscape.security.PrivilegeManager.enablePrivilege("UniversalXPConnect");
    
        // create and use the nsDymoLabels object
        var DymoLabelsCID = "@dymo.com/sdk/nsDymoLabels;1";
        DymoLabel = Components.classes[DymoLabelsCID].createInstance();
        DymoLabel = DymoLabel.QueryInterface(Components.interfaces.nsIDymoLabels);
      }
      catch (err)
      {
        return false;
      }
    }
  }
  return DymoLabel;
}

function getSearchInfo(){
  arr = window.location.pathname.match( /([^\/]+)\/?(.+)?/ );
  inf = new Object();
  if( arr!=null ){ 
    if( arr[1] != undefined ) inf["model"] = arr[1];
  }
  if( arr!=null && arr[2] != undefined ){ 
    inf["args"] = arr[2];
    args = new Object();
    aArgs = inf["args"].split( "\/" );
    for( var i=0; i<aArgs.length; i+=2 ){
      args[aArgs[i]] = aArgs[i+1];
    }
    inf["args"] = args;
  }
  return inf;
}

function getModelInfo(){
  // console.log( "getModelInfo" );
  modelMatch = new RegExp( window.location.hostname + globalSettings.site_root + "([^\/]+)\/([^\/]+)\/?([0-9]*)" );
  arr = window.location.href.match( modelMatch );
  // console.log( window.location );
  inf = new Object();
  inf["action"] = arr[2];
  inf["model"] = arr[1];
  inf["id"] = arr[3];
  // console.log( inf );
  return inf;
}

function initDymo(){
  
  // Add a "print label" button
  btn = $("<input type=\"button\" class=\"button dymo print\" value=\"Print label\" />");
  $("form.edit div.controls ul.options").append( "<li class=\"print_label\"></li>" );
  $("form.edit div.controls ul.options li.print_label").append( btn );
  $("form.edit div.controls ul.options li.print_label input").click(
    function(){
      
      // Get the ID, object
      arr = getModelInfo();
      action = arr["action"];
      model = arr["model"];
      id = arr["id"];
      
      // Get the info
      args = "id=" + id + "&ajax_form=1&action=get&model=" + model + "&context=" + model + "&context_id=" + id;
      // args += "&fields=name,first_name,last_name";
      $.ajax( { 
        type: "POST", 
        url: globalSettings.site_root + model + "/_repeat",
        data: args,
        success: function(data){
        
          var DymoAddIn = createDymoAddIn();
          var DymoLabel = createDymoLabel();
          var label_path = 'o:\\blank_label.lwl';
        
          if( DymoAddIn.Open( label_path ) ){
          }else if( DymoAddIn.Open( 'c:\\blank_label.lwl' ) ){
          }else{
            htmlAlert( "<p>Label file not found!</p><p>You should have the file " + label_path + "</p>" );
            return;
          }
          obj = eval('(' + data + ')');
          
          // Work out which fields belong where
          if( obj.fields ){
            for( var i=0; i<obj.fields.length; i++ ){
              col = obj.fields[i].column;
              // Exists on the label?
              for( var j=0; j<12; j++ ){
                if( DymoLabel.GetText("TEXT_" + j ).match("{" + col + "}") ){
                  DymoLabel.SetField("TEXT_" + j, obj.fields[i].string.replace(/\n/,"") );
                  break;
                }
              }
            }
            DymoAddIn.Print(1, true);
          }
        }
      });
    }
  );
}

function inlineEdit(e){
  // console.log( e );
  // console.log( "inlineEdit()" );
  btn = this;
  cell = btn.parentNode;
  row = cell.parentNode;
  a = cell.id.split( "_" );
  id = a[a.length-2];
  model = "";
  underscore = "";
  for( var i=0; i<a.length-2; i++ ){
    model += underscore + a[i];
    underscore = "_";
  }
  startBusy( row );
  // console.log( "Getting ajax fields" );
  $.get( globalSettings.site_root + model + "/_ajax_fields/" + id, function(data){
    $("li", data ).each(
      function(){
        column = $(this).attr("id");
        cell = $("table." + model + " tr." + id + " td." + column );
        cell.empty();
        cell.append( $(this).html() );
        // Create list of fields in this row
        if( $("table." + model + " tr." + id + " td.controls input.fields").length == 0 ){
          // console.log( "Creating field list hidden field" );
          fields = $("<input type=\"hidden\" class=\"fields hidden\" value=\"" + column + "\" />");
          $("table." + model + " tr." + id + " td.controls" ).append(fields);
        }else{
          // console.log( "Adding " + column + " to fields list" );
          path = "table." + model + " tr." + id + " td.controls input.fields"
          if( !$(path).get(0).value.match( column ) ){
            $(path).get(0).value += "," + column;
          }
        }
      }
    );
    if( $("table." + model + " tr." + id + " td.controls input.undo").length == 0 ){
      revert = $("<input type=\"button\" class=\"undo button\" value=\"Revert\" />");
      revert.click( inlineSave );
      $("table." + model + " tr." + id + " td.controls" ).append(revert);
    }
    btn.value = "Save";
    $(btn).removeClass( "edit" );
    $(btn).addClass( "save" );
    $("table." + model + " tr." + id + " td.controls input.save").unbind( "click" );
    $("table." + model + " tr." + id + " td.controls input.save").click( inlineSave );
    endBusy( row );
  });
}

function inlineDelete(e){

  // console.log( e );
  // console.log( "inlineDelete()" );
  btn = this;
  cell = btn.parentNode;
  row = cell.parentNode;
  a = cell.id.split( "_" );
  id = a[a.length-2];
  model = "";
  underscore = "";
  for( var i=0; i<a.length-2; i++ ){
    model += underscore + a[i];
    underscore = "_";
  }
  // console.log( "Model: " + model );

  url = globalSettings.site_root + model + "/delete/" + id + "/_ajax?height=400&width=600";

  // remove click border
  this.blur();

  // get caption: either title or name attribute
  var caption = this.title || this.name || "";
  
  // get rel attribute for image groups
  var group = this.rel || false;
  
  // display the box for the elements href
  TB_show(caption, url, group);
}

function inlineSave(){
  // console.log( "inlineSave()" );
  // Serialise row data 
  btn = this;
  cell = btn.parentNode;
  row = cell.parentNode;
  startBusy( row );
  a = cell.id.split( "_" );
  id = a[a.length-2];
  model = "";
  underscore = "";
  if( this.className.match( /undo/ ) ){
    revert = true;
  }else{
    revert = false;
  }
  for( var i=0; i<a.length-2; i++ ){
    model += underscore + a[i];
    underscore = "_";
  }
  args = "";
  if( !revert ){
    $("table." + model + " tr." + id + " td" ).each( 
      function(){
        arg = $(this).children("input, select, textarea").serialize();
        if( arg != "" ){
          args += "&" + arg;
        }
      }
    );
  }else{
    args += "&1=1";
  }
  if( args != "" ){
    if( revert ) action = "get";
    else action = "edit";
    args = "sessidhash=" + globalSettings.sessidhash + "&id=" + id + "&ajax_form=1&action=" + action + "&model=" + model + "&context=" + model + "&context_id=" + id + "&" + args;
    args += "&fields=" + $("table." + model + " tr." + id + " td input.fields" ).get(0).value;
    // console.log( "Posting form" );
    $.ajax( { 
      type: "POST", 
      url: globalSettings.site_root + model + "/_repeat",
      data: args,
      success: function(data){
        obj = eval( '(' + data + ')' );
        if( obj.flash.positive == false ){
          // Error!
          err = "";
          for( var i=0; i<obj.flash.errors.length; i++ ){
            err += "  <li>" + obj.flash.errors[i].message + "</li>\n";
            $("table." + model + " tr." + id + " td." + obj.flash.errors[i].columnname).addClass("error");
          }
          if( err != "" ){
            err = "<p>There was one or more issue encountered when trying to save:</p>\n<ul>\n" + err + "</ul>" + "<p><a href=\"" + globalSettings.site_root + model + "/edit/" + id + "\">Edit in full</a></p>\n";
            htmlAlert( err );
          }
        }else{
        
          colcount = 0;
          for( var i=0; i<obj.fields.length; i++ ){
            td = $( "table." + model + " tr." + id + " td." + obj.fields[i].column );
            if( td ){
              if( colcount == 0 ){
                str = "<a href=\"" + globalSettings.site_root + model + "/edit/" + id + "\">" + obj.fields[i].string + "</a>";
              }else{
                str = obj.fields[i].string;
              }
              td.empty();
              td.removeClass( "error" );
              td.append( str );
              colcount++;
            }
          }
          $( "table." + model + " tr." + id + " td.controls input.undo" ).remove();
          btn = $( "table." + model + " tr." + id + " td.controls input.save" ).get(0);
          btn.value = "Edit";
          $(btn).removeClass( "save" );
          $(btn).addClass( "edit" );
          // console.log( "Binding inlineEdit to " + btn );
          btn.onclick = inlineEdit;
          $("table." + model + " tr." + id).css( "background", '#DAF4D9' );
          $("table." + model + " tr." + id).animate( { background: '#FFFFFF' }, 'slow' );
        }
        endBusy( row );
      }
    } );
  }
}

function initInlineEdit(){
  // console.log( "initInlineEdit()" );
  if( $(this).children("input.button").length > 0 ) return;

  // Get the ID
  a = this.id.split( "_" );
  id = a[a.length-2];
  btn = $(
    "<input type=\"button\" name=\"btnInlineEdit\" id=\"" + this.id + "_edit\" value=\"Edit\" class=\"button inline edit\" title=\"Edit this entry here\" />" +
    "<input type=\"button\" name=\"btnInlineDelete\" id=\"" + this.id + "_delete\" value=\"Delete\" class=\"button inline delete\" title=\"Delete this entry\" />"
  );
  $(this).append( btn );
  $(this).children( "input.button.edit" ).each( 
    function(){ 
      $(this).click( inlineEdit ) 
    } 
  );
  $(this).children( "input.button.delete" ).each( 
    function(){ 
      $(this).click( inlineDelete ) 
    } 
  );
  // btn.click( inlineEdit );
}

function initMemberInterface(){
  $("#frmMemberInterface ul.checklist li > input").each(
    function(){
      $(this).change( changeMemberInterfaceCheckbox );
    }
  );
  $("#frmMemberInterface ul.checklist li > div > input").each(
    function(){
      $(this).change( changeMemberInterfaceRadio );
    }
  );
  setMemberInterfaceFieldVisibility();
}

// Set which parts of a MemberInterface are visible
function setMemberInterfaceFieldVisibility(){
  
  // Hide additional fields unless they're selected
  $("#frmMemberInterface ul.checklist li div").hide();
  $("#frmMemberInterface ul.checklist li div.rdo").show();
  $("#frmMemberInterface ul.checklist li.checked div").show();
}


// Add option to expand list boxes in searches to a larger interface
function initSearchLists(){
  if( $("form.search select + input.expand").length > 0 ) return;
  if( $("form.mail select + input.expand").length > 0 ) return;
  addSearch = function(){
    if( !$(this).attr("multiple") ) return;
    btn = $("<input type=\"button\" class=\"button expand\" value=\"Expand\" />");
    $(this).after(btn);
  }
  $("form.search select").each(addSearch);
  $("form.mail select").each(addSearch);
  $("form.search input.expand").click( expandedListView );
  $("form.mail input.expand").click( expandedListView );
}

function createFlash( obj ){
  // console.log("createFlash()");
  flash = $("<div id=\"flash\"></div>");
  className = obj.flash.positive ? "good" : "bad";
  flash.addClass( className );
  flash.append( "<p>" + obj.flash.notice + "</p>" );
  if( obj.flash.errors && obj.flash.errors.length > 0 ){
    // console.log( "%i errors", obj.flash.errors.length );
    ul = $("<ul></ul>");
    for( var i=0; i<obj.flash.errors.length; i++ ){
      err = obj.flash.errors[i];
      ul.append( "<li>" + err.message + "</li>" );
    }
    flash.append( ul );
  }
  return flash;
}

function ajaxCallback( data ){
  obj = eval( '(' + data + ')' );
  
  // Remove item if deleted
  if( obj.action && obj.id && obj.id > 0 && obj.tablename && obj.tablename != "" ){
    switch( obj.action ){
      case "delete":
        $("table.list." + obj.tablename + " tr." + obj.id).slideUp("slow");
        $("ul.list." + obj.tablename + " li." + obj.id).slideUp("slow");
        
        // Decrement paging count
        $("div.paging var.end").each( function(){
          num = parseInt( $(this).text() ) - 1;
          $(this).text( num + '' );
        } );
        $("div.paging var.total").each( function(){
          num = parseInt( $(this).text() ) - 1;
          $(this).text( num + '' );
        } );
        break;
      
      case "edit":
        
        path = "form div." + obj.tablename;
        field = path;
        
        // Members field
        if( obj.flash.members ){
          
          // create list
          if( $( path + " ul.memberlist").length == 0 ){
            list = $("<ul class='memberlist'></ul>");
            $( path + " label" ).after(list);
          }
          path += " ul.memberlist";
          var previous = $(path).text();
          $( path ).empty();
          for( var i=0; i<obj.flash.members.length; i++ ){
            li = "<li class=\"" + obj.flash.members[i].classstr + "\">" + obj.flash.members[i].name;
            if( obj.flash.members[i].fields ){
              for( var j=0; j<obj.flash.members[i].fields.length; j++ ){
                f = obj.flash.members[i].fields[j];
                li += "<div class=\""+f.column+"\"><span class=\"name\">"+f.name+"</span> <span class=\"value\">"+f.string+"</span></div>\n";
              }
            }
            li += "</li>";
            $( path ).append( li );
          }
          if( $(field).hasClass("_hasdependants") && $(path).text() != previous ){
            // List has changed, run calculations
            $(path).click( doAjaxCalculation );
            $(path).click();
            $(path).unbind("click");
          }
        }
        break;
    }
  }
  
  $("#TB_ajaxContent").empty();
  
  // Flash message
  $("#TB_ajaxContent").append( createFlash( obj ) );
  
  // Write OK button
  btn = $("<input type=\"button\" class=\"button\" value=\"OK\" />");
  btn.click( TB_remove );
  $("#TB_ajaxContent").append(btn);
  
}

function ajaxSubmit(){
  frm = this.parentNode.parentNode.parentNode.parentNode;
  $(frm).ajaxSubmit( ajaxCallback )
  $(frm).addClass("busy");
  // frm.reset();
}

function initAjaxForm(){
  
  // console.log( "initAjaxForm" );
  
  // Replace submit button
  if( $(this).children("div.controls").length > 1 ){
    $(this).children("div.controls").eq(0).remove();
  }
  controls = $(this).children( "div.controls" ).get(0);
  if( $(controls).find( "input[name=ajax_form]" ).length > 0 ) return;
  
  $(controls).append( "<input type=\"hidden\" name=\"ajax_form\" value=\"1\" />" );
  oldBtn = $(this).find( "div.controls ul.options li input[type=submit]" );
  if( oldBtn.length > 0 ){
    // console.log( "Create button" );
    newBtn = $("<input class=\"button\" type=\"button\"/>");
    newBtn.attr("value", oldBtn.get(0).value );
    newBtn.appendTo( "#TB_ajaxContent div.controls ul.options li" );
    $("#TB_ajaxContent div.controls ul.options li input[type=button]").click( ajaxSubmit );
    oldBtn.hide();
    /*
    */
    
    initIncrementalAjaxSearch("#TB_window form#frmMemberInterface","#TB_ajaxContent div.controls","#TB_window form#frmMemberInterface ul.checklist",true);
  }
}

// Add incremental search of a checklist to something
function initIncrementalAjaxSearch(listcontainer,controlcontainer,scroller,addclose){
  // console.log("Initing incremental ajax window search");
  if( $(listcontainer).length > 0 ){
    if( $(controlcontainer).children("div.search").length == 0 ){
      field = "<div class=\"search field\"><label>Search:</label><input type=\"text\" name=\"search\" class=\"search\" /> <span class=\"matches\"></span>";
      if( $.browser.msie ) {
        field += "<span class=\"note\">Note: This search is case-sensitive in Internet Explorer</span>";
      }
      field += "</div>";
      search = $(field);
      search.prependTo( controlcontainer );
      fn = incrementalAjaxSearch;
      fn.prototype.listcontainer = listcontainer;
      fn.prototype.scroller = scroller;
      $( controlcontainer + " input.search" ).keyup( fn );
    }
    // $( controlcontainer + " input.search" ).
    if(addclose && $(controlcontainer).find("input.close").length == 0 ){
      $("<li><input class=\"button close\" type=\"button\" value=\"Cancel\" /></li>").appendTo( controlcontainer + " ul.options " );
      $( controlcontainer + " ul.options li input.close").click( TB_remove );
    }
  }
}

function incrementalAjaxSearch(){
  var listcontainer = incrementalAjaxSearch.prototype.listcontainer;
  var scroller = incrementalAjaxSearch.prototype.scroller;
  if( this.value.length == 0 ) return;
  var itemcount = $(listcontainer + " ul.checklist li" ).length;
  if( itemcount > 100 && this.value.length < 2 ) return; // Searching can be very slow on large lists in IE
  $(listcontainer + " ul.checklist li").removeClass( "match" );
  // Case insensitive contains function for non-IE
  var contains = "contains";
  if( !$.browser.msie ) contains = "iContains";
  $(listcontainer + " ul.checklist li label:" + contains + "('" + this.value + "')").parent().addClass("match");
  matchEl = $(this).siblings("span.matches");
  // console.log( matchEl );
  matchEl.text( $( listcontainer + " ul.checklist li.match").length + " matched" );
  if( $( listcontainer + " ul.checklist li.match").length > 0 ){
    first = $( listcontainer + " ul.checklist li.match").get(0);
    scroller = $(scroller).get(0);
    if( Number( scroller.scrollTop ) != Number( first.offsetTop ) ){
      offset = first.offsetTop - first.offsetHeight - first.offsetHeight;
      scroller.scrollTop = offset;
    }
  }
}

function initAjaxLink(){
  
  // console.log( "initAjaxLink" );

	$(this).click(function(event){
  
    if( !this.href.match( /\?/ ) ) this.href += "/_ajax?height=400&width=600";
  
		// stop default behaviour
		event.preventDefault();
		// remove click border
		this.blur();
	
		// get caption: either title or name attribute
		var caption = this.title || this.name || "";
		
		// get rel attribute for image groups
		var group = this.rel || false;
		
		// display the box for the elements href
		TB_show(caption, this.href, group);
	});
}

function initRepeater(){
  // console.log("initRepeater");
  col = $(this).find( "div.controls ul li input[type=button]" );
  if( col.length != 0 ) return;
  
  // Change the submit button to JS function
  controls = $(this).children( "div.controls" );
  controls.append( "<input type=\"hidden\" name=\"ajax_form\" value=\"1\" />" );
  oldBtn = $("form#" + this.id + " div.controls ul.options li input[type=submit]" );
  if( oldBtn.length > 0 ){
    oldBtn.hide();
    newBtn = $("<input class=\"button submit\" type=\"button\"/>");
    newBtn.get(0).value = oldBtn.get(0).value;
    newBtn.click( ajaxRepeaterSubmit );
    newBtn.insertAfter( oldBtn );
  }
}

function ajaxRepeaterSubmit(){
  frm = this.parentNode.parentNode.parentNode.parentNode;
  $(frm).ajaxSubmit( ajaxRepeaterCallback )
  $(frm).addClass("busy");
  $(frm).find("input.submit").attr('disabled', 'disabled');
  $(frm).children("#flash").remove();
}

function ajaxRepeaterCallback( data ){
  if( typeof data == "string" ) obj = eval( '(' + data + ')' );
  else obj = data;
  if( !obj.flash.positive ){
    if( typeof frm !== "undefined" ){
      $(frm).removeClass("busy");
      $(frm).find("input.submit").removeAttr('disabled');
      // console.log("Repeater stopped with form errors");
      $(frm).append( createFlash( obj ) );
    }
    return;
  }
  
  // Add this data as a new row to the table
  var hastable = $( "table." + obj.tablename ).length > 0;
  tr = $("<tr class=\"" + obj.id + "\"></tr>");
  for( var i=0; i<obj.fields.length; i++ ){
    // Skip a field if the table is there but the column isn't
    check = "table." + obj.tablename + " td." + obj.fields[i].column
    if( hastable && $( check ).length == 0 ) continue;
    var cls = obj.fields[i].column + " " + obj.fields[i].name.substr( 0, 3);
    if( obj.fields[i].name.substr( 0, 3) == "txt" ) cls += " preservewhitespace";
    td = "<td class=\"" + cls + "\">";
    if( i == 0 ){
      td += "<a href=\"" + globalSettings.site_root + obj.tablename + "/edit/" + obj.id + "\">";
    }
    if( obj.fields[i].string == "" ) td += "&nbsp;";
    else td += obj.fields[i].string;
    if( i == 0 ){
      td += "</a>";
    }
    td += "</td>";
    tr.append( td );
  }
  
  // Create the table
  if( !hastable ){
    var str = "";
    for( var i=0; i<obj.fields.length; i++ ){
      str += "<th class=\"" + obj.fields[i].column + "\">" + obj.fields[i].displayname + "</th>";
    }
    str = "<tr>" + str + "</tr>";
    str = "<table class=\"rpt list " + obj.tablename + "\" cellspacing=\"0\">"+str+"</table>";
    $("div." + obj.tablename + " h3").after(str);
    $( "table.rpt." + obj.tablename ).slideDown();
    
  }
  
  // Get table
  $( "table.rpt." + obj.tablename ).append( tr );
  tr.slideDown("slow");
  tr.effect("highlight");
  if( typeof frm !== "undefined" ){
    frm.reset();
    $(frm).removeClass("busy");
    $(frm).find("input.submit").removeAttr('disabled');
  }
}

function changeGridTotals(){
  $("table.grd").each(
    function(){
      
      str = "";
      
      // Totals array
      aTotals = new Array( $(this).find("tr.header th").length -1 );
      for( var i=0; i<aTotals.length; i++ ){
        aTotals[i] = 0;
      }
      
      for( var j=0; j<$(this).find("tr.values").length; j++ ){
        for( var i=0; i<aTotals.length; i++ ){
          val = parseFloat( $(this).find("tr.values").eq(j).children("td").eq(i).children("span").text().replace(/[^-0-9\.]/g,'') );
          if( val ) aTotals[i] += val;
          else{
            val = parseFloat( $(this).find("tr.values").eq(j).children("td").eq(i).children("input").val().replace(/[^-0-9\.]/g,'') );
            if( val ) aTotals[i] += val;
          }
        }
      }
      
      // Totals row, cash values only
      for( var i=0; i<aTotals.length; i++ ){
        if( $(this).find("tr.totals var").eq(i).parent().hasClass("csh") ) $(this).find("tr.totals var").eq(i).text(numberFormat(aTotals[i]));
      }
    }
  );
}

function changeTableTotals(tbl){
  
  // Zero totals columns with figures in
  $(tbl).find("tr.totals td").each(function(){
    if( !$(this).text().match( /^[-0-9\.,]+$/ ) ){
      return;
    }
    $(this).text(0);
  });
  
  // Loop each row except header and totals
  $(tbl).find("tr").each(function(){
    if( $(this).hasClass("totals") ) return;
    if( $(this).hasClass("header") ) return;
    
    // Totals column
    ctot = $(this).find("td.total");
    ctot.text("0");
    
    // Loop each column with a figure in it
    $(this).find("td").each(function(i,el){
      if( $(el).hasClass("nototal") ) return;
      num = $(el).text().replace(/^\s+|\s+$/,'');
      if( !num.match( /^[-0-9\.,]+$/ ) ){
        return;
      }
      num = parseFloat(num);
      tot = $($(tbl).find("tr.totals td")[i]);
      tot.text(parseFloat(tot.text())+num);
      
      // Row total
      if( ctot.length == 1 ){ 
        if( $(this).hasClass( "total" ) ) return;
        ctot.text(parseFloat(ctot.text())+num);
      }
    });
  });
  
  // Round off to 2 DP
  $(tbl).find("tr td.total").each(roundElementNumberText);
  $(tbl).find("tr.totals td").each(roundElementNumberText);
}

function roundElementNumberText(i,el){
  num = $(el).text().replace(/^\s+|\s+$/,'');
  if( !num.match( /^[-0-9\.,]+$/ ) ){
    return;
  }
  $(el).text(Math.round($(el).text()*100)/100);
}

function checkMemberInterfaceListItem(){
  
  // Checkboxes
  if( $(this).children("input.checkbox").get(0).checked ){
    $(this).addClass("checked");
  }else{
    $(this).removeClass("checked");
  }
  
  // Radios
  rdo = $(this).children("div").children("input").get(0);
  if( rdo ){
    if( rdo.checked ){
      $(this).addClass(rdo.name);
    }else{
      $(this).removeClass(rdo.name);
    }
  }
}

// Member interface radio button
function changeMemberInterfaceRadio(E){
  targ = getTarget(E);
  $(targ.parentNode.parentNode).children("input.checkbox").get(0).checked = true;
  $("#frmMemberInterface ul.checklist li").each( checkMemberInterfaceListItem );
  setMemberInterfaceFieldVisibility();
}

// Member interface checkboxes
function changeMemberInterfaceCheckbox( E ){
  
  targ = getTarget(E);
  
  // Uncheck radio
  rdo = $(targ.parentNode).children("div").children("input").get(0);
  if( !targ.checked ){
    if( rdo ) rdo.checked = false;
    $(targ.parentNode).removeClass("checked");
  }else{
    $(targ.parentNode).addClass("checked");
  }
  
  // Radio buttons
  if( rdo ){
    if( rdo.checked ){
      $(targ.parentNode).addClass(rdo.name);
    }else{
      $(targ.parentNode).removeClass(rdo.name);
    }
  }
  setMemberInterfaceFieldVisibility();
  // $("#frmMemberInterface ul.checklist li").each( checkMemberInterfaceListItem );
}


function closeHelp(E){
  targ = getTarget(E);
  if( targ.tagName != "BUTTON" ) return; 
  // $(targ).parent().parent().css( { visibility: "hidden" } );
  $(targ).parent().parent().hide();
}

function initNumericFields(){
  // Bind onclick to clear any numeric fields which contain only zero
  // Fields: csh, int
  var aNumeric = "csh,int".split(",");
  for( var i=0; i<aNumeric.length; i++ ){
    type = aNumeric[i];
    $("form div.field input.csh").each(
      function(){
        $(this).click(
          function(){
            if( this.value == "0" ) this.value = "";
          }
        );
      }
    );
  }
}

function initAutoComplete(e){
  if( $("form.search div.field.str input.str").length > 0 ){ 
    arr = getSearchInfo();
    if( arr["model"] == undefined ) return;
  }
  $("form.search div.field.str input.str").each(bindAutoComplete);
}
function bindAutoComplete(){
  column = camelToUnderscore( this.id );
  arr = getSearchInfo();
  if( bindAutoComplete.prototype.arr != null ) arr = bindAutoComplete.prototype.arr;
  $(this).autocomplete({
    source: function( request, response ){
      $.ajax( { 
        type: "POST", 
        dataType: 'json',
        url: globalSettings.site_root + arr["model"] + "/_ajax_autocomplete",
        data: "term=" + request.term + "&column=" + this.options.column,
        success: response
      });
    },
    minLength: 2,
    column: column
  });
}

function initDateFields( type ){
  // Date fields as date pickers
  // Add a button
  // Associate buttons with the date input
  $( "div.field." + type ).each(
  
    // Give the button an ID
    function(){
    
      if( $(this).find( "input.button" ).length == $(this).find( "input." + type ).length ) return;
      
      for( var i=0; i<$(this).find( "input." + type ).length; i++ ){
        inp = $(this).find( "input." + type )[i];
        if( inp && !inp.disabled && inp.type == "text" ){
          $( inp ).after("<input type=\"button\" class=\"button\" value=\"...\" title=\"Use date picker\" />");
          // inp = inp.id;
          btn = "btn" + inp.id.substr( 3 );
          $( this ).find( "input.button" )[i].id = btn;
          
          if( type == "dtm" ){
            showsTime = true;
            format = "%e%T %b %Y %H:%M";
          }else{
            showsTime = false;
            format = "%e%T %b %Y";
          }
          
          Calendar.setup({
            inputField  : inp.id,          // id of the input field
            ifFormat    : format,       // format of the input field
            showsTime   : showsTime,    // will display a time selector
            button      : btn,          // trigger for the calendar (button ID)
            singleClick : false,        // double-click mode
            step        : 1,            // show all years in drop-down boxes (instead of every other year as default)
            onSelect    : Calendar.onSelect  // Fire the input's onchange event
          });
          // console.log( $(inp).get() );
        }
      }
    
      // Return the custom drop down to "" (Custom) when search from/to changed
      $(this).find( "div.group input." + type ).change(function(){
        $(this).parents( "div.field" ).find( "div.custom select" ).val("");
      });
    }
  );
}

function initUrlFields(){
  $("form.edit div.field.url").each(function(){
    if( $(this).find("input.url").val() != "" ) $(this).find( "input.url" ).hide();
    $(this).find("input.url").change(function(){
      a = $(this).parents("div.url_pair").find("a.url");
      url = $(this).val();
      if( !url.match( /^https?:\/\// ) ) url = "http://" + url;
      a.html(url)
      a.attr("href",url);
      $(this).attr("value",url);
    });
    if( $(this).find( "button.edit" ).length == 0 && !$(this).find( "input.url" ).is(":visible") ){
      $(this).find( "div.url_pair p a" ).after( "<button class=\"edit btn\" type=\"button\">Edit</button>" );
      $(this).find( "div.url_pair p button.edit" ).click(function(){
        $(this).parents("div.url_pair").find("input.url").toggle("blind",50);
        $(this).hide();
      });
    }
  });
}

function initTimeFields(){
  $("form div.field.tme").each(function(){
    if( $(this).find( "input.button" ).length == $(this).find( "input.tme" ).length ) return;
    for( var i=0; i<$(this).find( "input.tme" ).length; i++ ){
      inp = $(this).find( "input.tme" )[i];
      if( inp && !inp.disabled && inp.type == "text" ){
        $( inp ).after("<input type=\"button\" class=\"button\" value=\"Now\" title=\"Set to current time\" />");
        // inp = inp.id;
        btn = "btn" + inp.id.substr( 3 );
        $( this ).find( "input.button" )[i].id = btn;
        $( this ).find( "input.button" ).click( setTimeNow );
      }
    }
  });
}
function setSearchOptionLockStatus( lock ){
  $.get( globalSettings.site_root + "view/get_lock_status.php?lock="+lock, {}, function(status){
    el = $("ul.searchoptions li."+lock);
    if( status == "locked" ){ 
      el.addClass("locked")
        .attr("title", "This feature is locked as it is currently already in us" );
    }else{ 
      el.removeClass( "locked" )
        .attr("title", "" );
    }
  });
}

function changeViewLinks(){
  if( $(this).siblings("a.view").length > 0 ) $(this).siblings("a.view").get(0).href = $(this).siblings("a.view").get(0).href.replace( /\/edit\/(\d+)/, "/edit/" + this.options[this.selectedIndex].value );
}

function setTimeNow(){
  id = "tme" + this.id.substr( 3 );
  date = new Date();
  mins = date.getMinutes();
  if( mins < 10 ) mins = "0" + mins;
  $( "#" + id ).get(0).value = date.getHours() + ":" + mins;
}

function getAcademicYearString(){
  d = new Date();
  month = d.getMonth();
  if( month.toString().length > 2 ) month = month.toString().substring( 1, 2 );
  if( month > globalSettings.site_periodoffset ){
    str = d.getFullYear() + "/";
    d.setFullYear( Number(d.getFullYear()) + 1 );
    ystr = d.getYear().toString()
    str += ystr.substr(ystr.length-2);
  }else{
    ystr = d.getYear().toString()
    str = "/" + ystr.substr(ystr.length-2);
    d.setFullYear( d.getFullYear() - 1 );
    str = d.getFullYear() + str;
  }
  return str;
}
function getAcademicPeriod(){
  d = new Date();
  month = d.getMonth();
  if( month.toString().length > 2 ) month = month.toString().substring( 1, 2 );
  month = parseInt(month);
  month-=6;
  if( month < 1 ) month+=12;
  return month;
}

function getTarget(E){
  if( !E ) E = window.event;
  if(E.target){
    targ = E.target;
  }else if(E.srcElement){
    targ = E.srcElement;
  }if(targ.nodeType == 3){
    targ = targ.parentNode;
  }
  return targ;
}

function writeDefaultTime(E){
  targ = getTarget(E);
  tme = targ.parentNode.getElementsByTagName( "input" )[0];
  str = eval( camelToUnderscore( tme.id ) + "_default" );
  tme.value = str;
}

function writeDay(E){
  targ = getTarget(E);
  tmr = targ.parentNode.getElementsByTagName( "input" )[0];
  tmr.value = day_length;
  $(tmr).change();
}

function expandedListView(){

  $(this).addClass("busy");
  list = $(this).parent().children("select").get(0);
  listid = list.id;

  // Get title
  var title = list.title.replace( / \(.+\)/, '' );
  
  if( $("#dialog").length == 0 ){
    box = $("<div id=\"dialog\" title=\""+title+"\"></div>");
    $(document.body).append( box );
  }else{
    $("#dialog").html("");
    $("#dialog").title=title;
    $("div.ui-dialog div.ui-dialog-titlebar span").text(title);
  }
  
  $("#dialog").dialog({
    height: 400,
    width: 500,
    autoOpen: true,
    buttons: { "Ok": function() { $(this).dialog("close"); }}
  });
  $("#dialog").dialog("open");
  $("#dialog").html("<ul class=\"checklist\"></ul>");
  $("#dialog").addClass("busy");
  initIncrementalAjaxSearch("#dialog","div.ui-dialog div.ui-dialog-buttonpane","#dialog",false);
      
  buildExpandedList(listid);  
  $("#dialog").removeClass("busy");
  $(this).removeClass("busy");
}

function buildExpandedList(listid){
  // Build the list
  list = $("#" + listid).get(0);
  html = "";
  for( var i=0; i<list.options.length; i++ ){
    opt = list.options[i];
    if( opt.selected ){
      checked = " checked=\"checked\"";
      className = "checked";
    }else{
      checked = "";
      className = "";
    }
    li = "  <li class=\""+className+"\">\n"
      + '<input class="checkbox" name="'+listid+'['+opt.value+']" id="'+listid+'_'+opt.value+'" value="1" type="checkbox"'+checked+'>'
      + '<label for="'+listid+'_'+opt.value+'">'+opt.text+'</label>'
      + '</li>\n';
    html += li;
  }
  $("#dialog ul.checklist").get(0).innerHTML = html;
  $("#dialog input.checkbox").change(function(){
    if( this.checked ){
      $(this).parent().addClass("checked");
    }else{
      $(this).parent().removeClass("checked");
    }
    a = this.id.split(/_/);
    $("#"+a[0]+" option[value='"+a[1]+"']").get(0).selected = this.checked;
  });
  if( buildExpandedList.prototype.customMethod != null ) buildExpandedList.prototype.customMethod(listid,"div.ui-dialog-buttonpane");
}

// Nicer than the function below
function showConfirm( html, title, fnOk ){
  if( $("#dialog").length == 0 ){
    box = $("<div id=\"dialog\" title=\""+title+"\"></div>");
    $(document.body).append( box );
  }else{
    $("#dialog").html("");
    $("#dialog").title=title;
    $("div.ui-dialog div.ui-dialog-titlebar span").text(title);
  }
  
  $("#dialog").dialog({
    height: 300,
    width: "30%",
    resizable: true,
    autoOpen: true,
    buttons: { 
      "Cancel": function() { 
        $(this).dialog("close"); 
      }, 
      "OK": fnOk
    }
  });
  $("#dialog").dialog("open");
  $("#dialog").html(html);
}

function htmlAlert( html ){
  var title = "";
  if( arguments.length == 2 ) title = arguments[1];
  else title = "Alert";
  fnOk = arguments.length == 3 ? arguments[2] : function() { $(this).dialog("close"); };
  if( $("#dialog").length == 0 ){
    box = $("<div id=\"dialog\" title=\""+title+"\">"+html+"</div>");
    $(document.body).append( box );
  }else{
    $("#dialog").html(html);
    $("#dialog").title=title;
    $("div.ui-dialog div.ui-dialog-titlebar span").text(title);
  }
  
  $("#dialog").dialog({
    modal: true,
    height: 400,
    width: 500,
    autoOpen: true,
    buttons: { "Ok": fnOk }
  });
  $("#dialog").dialog("open");
}

// iframe transport, based on jQuery file upload demo: http://blueimp.github.com/jQuery-File-Upload/
function createAttachmentField(after){
  
  // Check hidden fields are there
  if( $(after).parents("form").find("input.attachments").length == 0 ){
    $(after).parents("form").append("<input type=\"hidden\" value=\"\" name=\"attachments\" class=\"attachments\" />");
  }
  
  // Get the new attachment form
  $.ajax({
    async: true,
    url: globalSettings.site_root + "attachment/new",
    success: function(data){
      form = $(data).find( "#frmAttachment" );
      iframe = $('<iframe src="javascript:false;" id="attachment-frame" name="attachment-frame" style="display: none;"></iframe>')
        .attr( "frameborder", "0" )
        .bind( "load", function(){
          // console.log( document.getElementById("attachment-frame").contentWindow.location.href );
          var response;
          // Wrap in a try/catch block to catch exceptions thrown
          // when trying to access cross-domain iframe contents:
          try {
              response = iframe.contents();
              // Google Chrome and Firefox do not throw an
              // exception when calling iframe.contents() on
              // cross-domain requests, so we unify the response:
              if (!response.length || !response[0].firstChild) {
                  throw new Error();
              }
          } catch (e) {
              response = undefined;
          }
          var inputs = $(response).find( "#frmAttachment input" );
          var data = new Object();
          data["tablename"] = "attachment";
          data["name"] = "Attachment";
          data["id"] = "attachment";
          if( $(response).find( "#flash.bad" ).length > 0 ){
            data["flash"] = {"positive":false,"notice":$(response).find("#flash").text()};
            $("#frmRptAttachments").append( createFlash( data ) );
          }else{
            data["flash"] = {"positive":true};
          }
          if( inputs.length > 0 ){
            var oFields = new Array();
            for( var i=0; i<inputs.length; i++ ){
              oInput = new Object();
              name = $(inputs[i]).attr("name");
              if( name == "btnSubmit" ) continue;
              if( name == "sessidhash" ) continue;
              if( name == "strModelName" ) continue;
              if( name == "intModelId" ) continue;
              if( name == "action" ) continue;
              if( name == "model" ) continue;
              
              
              if( name == "id" ){ 
                data["id"] = $(inputs[i]).val();
                oInput["column"] = "id";
              }else{
                oInput["column"] = camelToUnderscore(name);
              }
              oInput["name"] = name;
              oInput["displayname"] = $(inputs[i]).parents("div.field").find("label").text();
              oInput["value"] = $(inputs[i]).val();
              if( $(inputs[i]).siblings("span").length > 0 ){
                str = $(inputs[i]).siblings("span").html();
                if( !name.match(/^htm/) ){
                  str = str.replace(/<a[^>]+>[^<]+<\/a>/,'')
                }
              }else{
                str = $(inputs[i]).val();
              }
              oInput["string"] = str;
              oInput[$(inputs[i]).attr("name")] = $(inputs[i]).val();
              oFields.push(oInput);
            }
            data["fields"] = oFields;
            appendAttachmentToList( data, after );
            $(after).parents("form").find("div.fle").remove();
            $("#frmAttachment").remove();
            createAttachmentField(after);
          }
        });
      
      // The hidden attachment form
      form
        .attr( "target", "attachment-frame" )
        .append(iframe)
        .insertAfter($(after).parents("form"))
        .hide();
      
      // Move the file field to the main form
      $(after).after($("#frmAttachment div.field.fle"));
        
      // Set model name and id if not new
      var inf = getModelInfo();
      if( inf["id"] > 0 ){
        $("#frmAttachment div.model_name input").val(inf["model"]);
        $("#frmAttachment div.model_id input").val(inf["id"]);
      }
        
      $(after).parents("form").find("div.field.fle").change(function(){
        
        $("#flash").remove();
        
        // Move the field back to the attachment form
        $(this).append('<div class="loader">&nbsp;</div>');
        $("#frmAttachment").append($(this).find("input"));
        $("#frmAttachment").submit();
      });
    }
  });  
}
function appendAttachmentToList(data,after){
  if( after.match(/\.chd\./) ){
    ajaxRepeaterCallback(data);
    return;
  }
  oAttachment = new Object();
  for( var i=0; i<data["fields"].length; i++ ){
    f = data["fields"][i];
    oAttachment[f.name] = f.string;
  }
  frm = $(after).parents("form");
  if( frm.find("div.attachments").length == 0 ){
    $('<div class="attachments field"><ul class="files"></ul></div>').insertAfter(after);
  }
  frm.find("div.attachments.field ul")
    .append($('<li id="file_'+oAttachment["id"]+'">'+oAttachment["strName"]+' <var>('+oAttachment["sizSize"]+')</var><button type="button">Delete</button></li>'));
  $("#file_"+oAttachment["id"]+" button" ).click(function(){
    r = new RegExp( "[^0-9]?"+oAttachment["id"] );
    ids = $("input.attachments").val()
    ids = ids.replace(r,'');
    $("input.attachments").val(ids);
    $(this).parents("li").hide();
  });
  frm.find("input.attachments").val(frm.find("input.attachments").val()+","+oAttachment["id"]);
}


function startBusy(element){
  $(element).addClass( "busy" );  
}

function endBusy(element){
  $(element).removeClass( "busy" );
}

function getScrollXY() {
  var scrOfX = 0, scrOfY = 0;
  if( typeof( window.pageYOffset ) == 'number' ) {
    //Netscape compliant
    scrOfY = window.pageYOffset;
    scrOfX = window.pageXOffset;
  } else if( document.body && ( document.body.scrollLeft || document.body.scrollTop ) ) {
    //DOM compliant
    scrOfY = document.body.scrollTop;
    scrOfX = document.body.scrollLeft;
  } else if( document.documentElement && ( document.documentElement.scrollLeft || document.documentElement.scrollTop ) ) {
    //IE6 standards compliant mode
    scrOfY = document.documentElement.scrollTop;
    scrOfX = document.documentElement.scrollLeft;
  }
  return [ scrOfX, scrOfY ];
}

function formatFilesize(size) {
  var size = parseInt(size);
  var kb = 1024;         // Kilobyte
  var mb = 1024 * kb;   // Megabyte
  var gb = 1024 * mb;   // Gigabyte
  var tb = 1024 * gb;   // Terabyte

  /* If it's less than a kb we just return the size, otherwise we keep going until
  the size is in the appropriate measurement range. */
  if(size < kb) {
     return size+" B";
  }
  else if(size < mb) {
     return Math.round(size/kb,2)+" KB";
  }
  else if(size < gb) {
     return Math.round(size/mb,2)+" MB";
  }
  else if(size < tb) {
     return Math.round($size/gb,2)+" GB";
  }
  else {
     return Math.round(size/tb,2)+" TB";
  }
}


// Convert from camel case to underscore case
/*
function camelToHungarian( str ){
  return camelToUnderscore( str );
}
*/
function camelToUnderscore( str ){
  a = str.toString().replace( /([A-Z])/g, "_$1" ).toLowerCase().split( "_" );
  a.shift();
  return a.join( "_" );
}

// Format a number
function numberFormat(num)
{
  nStr = num.toFixed(2); // Convert to string of 2 decimal places
	nStr += '';
	x = nStr.split('.');
	x1 = x[0];
	x2 = x.length > 1 ? '.' + x[1] : '';
	var rgx = /(\d+)(\d{3})/;
	while (rgx.test(x1)) {
		x1 = x1.replace(rgx, '$1' + ',' + '$2');
	}
	return x1 + x2;
}

// Escape html entities
function htmlEntities(str) {
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

$(function(){
  
// Move the element at fromidx to toidx, move everything to make room
$.fn.reindex = function( fromidx, toidx )
{
	//Copy all matched elements of the jQuery object to an array
	var oldArr = jQuery.makeArray(jQuery(this));
  var newArr = new Array();
  
  var newidx = 0;
  var item;
  for( var i=0; i<oldArr.length; i++ ){
    if( i == fromidx ){
      item = oldArr[i];
    }
    newArr[newidx] = oldArr[i];
    newidx++;
  }
  // newArr[toidx] = item;
  /*
  // Copy bits that don't need sorting
  for( var i=0; i<Math.min(fromidx,toidx); i++ ){
    newArr[i] = oldArr[i];
  }
  for( var i=Math.max(fromidx,toidx); i<oldArr.length; i++ ){
    newArr[i] = oldArr[i];
  }
  
  var change = 0;
  var start = 0;
  var end = 0;
  if( fromidx > toidx ){ 
    change = 1;
    start = Math.min( fromidx, toidx );
    end = Math.max(fromidx,toidx) -1;
  }
  if( toidx > fromidx ){ 
    change = -1;
    start = Math.min( fromidx, toidx )+1;
    end = Math.max(fromidx,toidx);
  }
  // Move the element
  newArr[toidx+change] = oldArr[fromidx];
  
  // Fill the bits between positions
  for( var i=start; i<=end; i++ ){
    newArr[i+change] = oldArr[i];
  }
  */
	return $(newArr);
}

$.fn.swap = function(index1, index2)
{
	//Copy all matched elements of the jQuery object to an array
	var tempArr = jQuery.makeArray(jQuery(this));
	
	//Copy the value of index1 to a temporary variable
	var tempValue = tempArr[index1];
	
	//Assign the value of index1 to the value of index2
	tempArr[index1] = tempArr[index2];
	
	//Assign the value of index2 to the value of index1 via the tempValue variable
	tempArr[index2] = tempValue;
	
	return $(tempArr);
}

$.fn.getIndexOf = function(array)
{
	//Assume value isn't found
	var index = false;
	
	//Define scope
	var value = jQuery(this);
	
	//Initiate index counter
	var i=0;
	jQuery(array).each(function()
	{
		if(jQuery(this).equalTo(jQuery(value)))
		{
			index = i;
		}
		
		//Increment index counter
		i++;
	});
	
	return index;
};

$.fn.equalTo = function(object)
{
	isEqual = !jQuery(this).not( jQuery(object) ).length
	return isEqual;
};

$.fn.selectIndex = function(index)
{
	
};

$.fn.fakeFloat = function(options, callback)
{
	
	var defaults = {
	direction: "up",
	margin: 0,
	offset: 0,
	speed: 0
	},
	settings = jQuery.extend({}, defaults, options);  
		
	//Initialize counter
	var i=0;
	
	//Initialize element height
	var elemHeight = 0;
	
	jQuery(this).each(function()
	{
		elemWidth = jQuery(this).width();
		if(settings.direction == "up")
		{
			jQuery(this).animate({"up": ((settings.margin) + elemHeight)*i + (settings.offset) + 'px'}, settings.speed);
		}
		else
		{
				jQuery(this).animate({"down": ((settings.margin) + elemHeight)*i + (settings.offset) + 'px'}, settings.speed);
		}
		i++;
	});
	
	if(typeof callback == 'function')
	{
		setTimeout(function(){callback.call(this);}, settings.speed);
	}	
	return this;
};

});

function strPad (str, max) {
  var p = arguments.length > 2 ? arguments[2] : "0";   
  return str.length < max ? strPad(p + str, max) : str;
}

String.prototype.pad = function(){
  if( arguments.length > 1 ){
    str = strPad( this, arguments[0], arguments[1] );
  }else{
    str = strPad( this, arguments[0] );
  }
  return str;
}

$(document).ready(init);
