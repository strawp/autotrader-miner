function initDashboard(){
  if( $("div.report.dashboard").length == 0 ) return;

  if( $("#dialog").length == 0 ){
    $("body").append( "<div id=\"dialog\"></div>" );
    $("#dialog").dialog(
      {
        height: 400,
        width: 500,
        autoOpen: false,
        buttons: { "Cancel": function() { $(this).dialog("close"); }}
      }
    );
  }
  
  $("p.option a" ).each(function(){
    if( !this.href.match(/\/ajax\//) ) this.href += "/ajax/1";
    $(this).click(function(event){
      event.preventDefault();
      this.blur();
    });
  });
  
  // Links as dialogs
  $("p.option a.add").each(makeLinkIntoDialog);
  
  // Init containers
  $("div.widget_container").each(initWidgetContainer);
  
  // Init grid
  $("#tblWidgets > tbody > tr > td").each(initWidgetGridCell);
  
  // Minimise all button
  /*
  if( $( "p.option input.minimise" ).length == 0 ){
    inp = "<p class=\"option\"><button type=\"button\" class=\"minimise\">Toggle all</button></p>";
    $("#content > p.option").after(inp);
    $("#content > p.option button.minimise").click(function(){
      $(".widget_container button.minimise").click();
    });
  }
  */
}
function initWidgetGridCell(){
  $(this).droppable({
    activeClass: "drop-target",
    hoverClass: "drop-hover",
    accept: ".widget_container",
    drop: function( event, ui ){
      moveWidgetInto($(ui.draggable),$(this));
      $(ui.draggable).hide();
      $(ui.draggable).show("slow");
      // save order
      widgets = $("#tblWidgets .widget_container");
      ids = new Array();
      for( i=0; i<widgets.length; i++ ){
        ids[i] = $(widgets[i]).attr("id").match(/widget_(\d+)/)[1];
      }
      $.get( globalSettings.site_root + "report/dashboard/screen/_saveorder/ids/" +ids.join(), function(data){
        // console.log( "saved", data );
      });
    }
  });
}
function moveWidgetInto(widget,cell){
  var cellnum = cell.attr("class").match(/cell(\d+)/)[1];
  var draggedid = widget.attr("id");
  
  // Append widget to chosen cell
  $("#tblWidgets td.cell"+cellnum).append(widget);
  
  // Make sure it's positioned in the top left of that cell
  widget.css({top:0,left:0});
  
  // Find blank cell to displace contents of this cell into
  cells = $("#tblWidgets td.cell");
  var nextcellnum = parseInt(cellnum)+1;
  /*
  for( var i=0; i<cells.length; i++ ){
    if($(cells[i]).html().length < 2 && nextcellnum == null ){ 
      nextcellnum = i;
    }
  }
  */
  
  // If there is already a widget in this cell, move it to another cell
  if( cell.find(".widget_container").length > 1 ){
    // Which widget was already there?
    cell.find(".widget_container").each(function(){
      if( $(this).attr("id") != draggedid ){
        nextcell = $("#tblWidgets td.cell"+nextcellnum);
        // console.log( $(this).parents() );
        moveWidgetInto($(this),nextcell);
      }
    });
  }
  $("#tblWidgets td.cell"+cellnum).each(initWidgetGridCell);
  
  // Optional callback function for this function
  if( arguments.length == 3 ){ 
    fn = arguments[2];
    fn(widget,cell);
  }
}
function initWidgetHelp(){
  $(this).hide();
  if( $(this).siblings().filter("input.help").length > 0 ) return;
  $(this).before( "<input type=\"button\" class=\"help button\" value=\"Help\" title=\"Show help\"/>" );
  $(this).siblings().filter("input.help").click(function(){
      $(this).siblings().filter("div.help").toggle();
  });
  $(this).click(function(){ $(this).hide() });
}
function makeLinkIntoDialog(){
  $(this).click(function(event){
    $("#dialog").addClass("busy");
    $("#dialog").empty()
      .html("<div class=\"body\"></div>")
    var title = $(this).text();
    var height = parseInt( $(window).height() * 0.7 );
    var width = parseInt( $(window).width() * 0.7 );
    $("#dialog").dialog({
      height: height,
      width: width
    });
    $("#dialog").dialog("open");
    $("#dialog div.body").load( this.href, function(txt,status,xhr){
      $("div.ui-dialog div.ui-dialog-titlebar span").text(title);
      $("#dialog").dialog("option", "width", parseInt($("#content").width()*0.6));
      // $("#dialog").dialog("option", "height", "auto");
      $("#dialog").dialog("option", "position", "center");
      initDashboardDialog("#dialog",title);
      $("#dialog").removeClass("busy");
    });
  });
}

// Move the current widget left then move to next widget and do the same
function shiftCellContent(){
  
  // Widget to move
  widget = $(this).find(".widget_container");
  
  // The cell number it's currently in
  cellnum = parseInt($(this).attr("class").match(/cell(\d+)/)[1]);
  
  // The cell to move it to
  cell = $("#tblWidgets td.cell" +  (cellnum-1));
  
  // Move the content into the cell to the left if there is a cell to the left
  if( cell.length != 0 ) moveWidgetInto(widget,cell);
  
  // Do the same for the next cell
  $("#tblWidgets td.cell" +  (cellnum+1)).each(shiftCellContent);
}

function initDashboardDialog(elid,text){
  $(elid).find( "button[type=submit]" ).remove();
  buttons = { 
    "Cancel": function(){ $(elid).dialog("close") }
  }
  if( text == "Options" ){
    buttons.Save = function(){
      $(elid).addClass("busy");
      $(elid).find("form").submit();
    }
  }
  $(elid).find( "a" ).click(function(){
    $(elid).addClass("busy");
    $(elid).find("div.body").empty();  
  });

  $(elid).dialog( "option", "buttons", buttons );
}
function initWidgetContainer(){
  height = $(this).height();
  $(this).removeClass("busy");
  
  // Minimise
  if($(this).find("button.minimise").length == 0 ){
    $(this).find("p.option a.delete").parents("p.option").before("<p class=\"option\"><button class=\"minimise\">Minimise</button></p>");
  }
  
  $(this).find("button.minimise").get(0).onclick = function(){
    wdg = $(this).parents("div.widget_container")
    ctnt = wdg.find("div.content");
    switch($(this).text()){
      case "Minimise":
        ctnt.slideUp(300,function(){
          wdg = $(this).parents("div.widget_container");
          name = wdg.find("h3").length > 0 ? wdg.find("h3").text() : wdg.attr("title");
          wdg.prepend("<p class=\"name\">" + name + "</p>");
        });
        $(this).text("Maximise");
        $(this).addClass("maximise");
        break;
      case "Maximise":
        ctnt.slideDown(300);
        $(this).text("Minimise");
        $(this).removeClass("maximise");
        wdg.find("p.name").remove();
        break;
    }
  }
  
  $(this).find("p.option a" ).each(function(){
    if( !this.href.match(/\/ajax\//) ) this.href += "/ajax/1";
    $(this).click(function(event){
      event.preventDefault();
      this.blur();
    });
  });
  
  // Delete 
  $(this).find( "p.option a.delete" ).click(function(event){
    $(this).parents(".widget_container").addClass( "busy" );
    cellnum = parseInt($(this).parents("td.cell").attr("class").match(/cell(\d+)/)[1]);
    nextcell = $("#tblWidgets td.cell"+(cellnum+1));
    $.get( this.href, function(data){
      obj = eval( '(' + data + ')' );
      $("#widget_" + obj.id).fadeOut( "", function(){
        // If there's a widget in the next cell, move it into this one
        if( nextcell.length > 0 ) nextcell.each( shiftCellContent );
      });
    });
  });
  
  // Drag 
  $(this).draggable({ 
    revert: "invalid",
    start: function(e,ui){
      // Make sure the dragged element is above the ones on the page
      $(this).css({"z-index": 999});
      
      // Remember the cell it came from
      oldcell = $(this).parents( "td.cell" );
      cellnum = parseInt(oldcell.attr( "class" ).match( /cell(\d+)/ )[1]);
      
      // Take widget off the grid temporarily to shuffle things around
      $("#dragged_widget").show();
      $("#dragged_widget")
        .append($(this))
        .css({
          width: oldcell.width(), 
          height: oldcell.height(), 
          top: oldcell.position().top, 
          left: oldcell.position().left, 
          position: "absolute" 
        });
      
      // Shift everything up to fill the gap that was created
      nextcell = $("#tblWidgets td.cell"+(cellnum+1));
      nextcell.each(shiftCellContent);
    },
    stop: function(e,ui){
      $(this).css({"z-index": 0, position: "relative", width: "98%"});
      $("#dragged_widget").hide();
      
      // Move widget to cell0 if drag was invalid
      if( $(this).parents("#dragged_widget").length > 0 ){
        $(this).hide("puff",function(){
          moveWidgetInto($(this),$("#tblWidgets td.cell0"),function(wdgt,cell){
            wdgt.show("slow");
          });
        });        
      }
    }
  });
  
  // Move
  /*
  $(this).find( "p.option a.up,p.option a.down" ).click(function(event){
    $(this).parents("div.widget_container").addClass( "busy" );
    $.get( this.href, function(data){
      obj = eval( '(' + data + ')' );
      var currentidx = obj.currentidx-1;
      var newidx = obj.newidx-1;
      awidgets = $("div.widget_container").swap( currentidx, newidx );
      $("#widgets").empty();
      awidgets.each(function(i,el){
        $("#widgets").append(this);
        $(this).removeClass("busy");
        var id = this.id.split(/_/)[1];
        if( i == 0 ){
          $(this).find("p.option a.up").parent().remove();
        }
        if( i==awidgets.length-1 ){
          $(this).find("p.option a.down").parent().remove();
        }
        if( i>0 && $(this).find("p.option a.up").length == 0 ){
          $(this).append( "<p class=\"option\"><a href=\"" + site_root + "report/dashboard/screen/_up/user_widget/"+id+"\" class=\"up\">Move up</a></p>" );
        }
        if( i<awidgets.length-1 && $(this).find("p.option a.down").length == 0 ){
          $(this).append( "<p class=\"option\"><a href=\"" + site_root + "report/dashboard/screen/_down/user_widget/"+id+"\" class=\"down\">Move down</a></p>" );
        }
      });
      $("div.widget_container").each(initWidgetContainer);
    });
  });
  */
  $(this).find( "p.option a.configure").each(makeLinkIntoDialog);
  
  // Pop up help dialogs
  $(this).find("div.help").each(initWidgetHelp);
  
  // Graphs
  $(this).find( "img.graph" ).each(resizeGraph);
  /*
  $(window).resize(function(){
    $( ".widget_container img.graph" ).each(resizeGraph);
  });
  */
  // Resize
  /*
  $(this).resizable({
    maxHeight: height,
    minHeight: height,
    minWidth: 350,
    handles: 'e',
    resize: function(){
      $(this).height("auto");
    },
    stop: function(){
      var id = this.id.split(/_/)[1];
      $(this).addClass("busy");
      $("#widget_" + id + " div.content" ).load( site_root + "report/dashboard/screen/_resize/width/" + $(this).width() + "/user_widget/" + id, 
        function(){
          $(this).parent( "div.widget_container" ).each(initWidgetContainer);
        }
      );
    }
  });
  */  
}
function resizeGraph(){
  var gw = $(this).width();
  var gh = $(this).height();
  var cw = $(this).parents("#content").width();
  var ga = gw/gh;
  var colw = (cw / 2);
  colw *= 0.95;
  colw -= 10;
  $(this).width(colw);
  $(this).height(colw/ga);
}
$(initDashboard);
