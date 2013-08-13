$(function(){
  if( $("#dialog").length == 0 ){
    box = $("<div id=\"dialog\" title=\"Welcome\"></div>");
    $(document.body).append( box );
  }

  // Welcome
  doWelcomeStep = function(){
    $("div.ui-dialog div.ui-dialog-titlebar span").text("Take a tour");
    $("#dialog").html( "<p>Welcome to the new dashboard!</p>"+
      "<p>This page will be the first thing you see when you log in and is designed to make is easier for you to get to the information you need.</p>" +
      "<p>Would you like to take a quick tour?</p>" );
    $("#dialog").dialog({
      height: 200,
      width: 400,
      autoOpen: true,
      position: "center",
      buttons: { 
        "Not now": function(){$(this).dialog("close");},
        "OK": doWidgetStep
      }
    });
    $("#dialog").dialog("open");
  }
  
  // Widgets
  doWidgetStep = function (){
    flasheach = function(i){
      if( i >= $(".widget_container").length ) return;
      el = $($(".widget_container")[i]);
      if( i == 0 ) prev = $("#container");
      else prev = $($(".widget_container")[i-1]);
      prev.effect("transfer", { to: el }, 700);
      el.effect("highlight",{},1500,function(){flasheach(i+1)});
    }
    $("div.ui-dialog div.ui-dialog-titlebar span").text("Widgets");
    $("#dialog").html( "<p>Your dashboard consists of <strong>Widgets</strong>.</p>" +
      "<p>Widgets are small blocks of the page which show simple imformation, like a list of projects you are on or a pie chart of expenses for a project.</p>"
    );
    $("#dialog").dialog({
      height: 200,
      width: 400,
      autoOpen: true,
      position: "center",
      buttons: { 
        "Close": function(){$(this).dialog("close");},
        "Previous": doWelcomeStep,
        "Next": doDragStep
      }
    });
    flasheach(0);
    $("#dialog").dialog("open");
  }
  
  // Dragging and dropping
  doDragStep = function(){
    $("div.ui-dialog div.ui-dialog-titlebar span").text("Drag and drop");
    $("#dialog").html( "<p>You can re-order your dashboard by <strong>dragging</strong> and <strong>dropping</strong> widgets.</p>" +
      "<p>Try dragging a widget now and dropping it onto one of the highlighted areas.</p>"
    );
    $("#dialog").dialog({
      height: 200,
      width: 400,
      autoOpen: true,
      position: "center",
      buttons: { 
        "Close": function(){$(this).dialog("close");},
        "Previous": doWidgetStep,
        "Next": doOptionsLinkStep
      }
    });
    $("#dialog").dialog("open");
  }
  
  // Move links
  /*
  doMoveLinksStep = function(){
    $("div.ui-dialog div.ui-dialog-titlebar span").text("Moving widgets");
    $("#dialog").html( "<p>You can move widgets around by clicking <strong>Move up</strong> or <strong>Move down</strong>.</p>"+ 
      "<p>Widgets sit at the top leftmost position available, like text on a page.</p>"
    );
    $("p.option a.down:first").focus();
    $("#dialog").dialog({
      height: 200,
      width: 400,
      autoOpen: true,
      position: "center",
      buttons: { 
        "Close": function(){$(this).dialog("close");},
        "Previous": doWidgetStep,
        "Next": doResizeStep
      }
    });
    $("#container").effect("transfer", { to: $("p.option a.down:first").eq(0) }, 700);
    $("#widgets div.widget_container a.down, #widgets div.widget_container a.up").effect( "pulsate", {times: 3}, 500);
    $("#widgets div.ui-resizable-handle").removeClass( "highlight" );
    $("#dialog").dialog("open");
  }
  
  // Resize bar
  doResizeStep = function(){
    $("div.ui-dialog div.ui-dialog-titlebar span").text("Resizing");
    $("#dialog").html( "<p>You can change the width of widgets by dragging the bar on the right.</p>"+
      "<p>Widgets will always be the minimum height to contain the content in them.</p>"
    );
    $("#dialog").dialog({
      height: 200,
      width: 400,
      autoOpen: true,
      position: "center",
      buttons: { 
        "Close": function(){$(this).dialog("close");},
        "Previous": doMoveLinksStep,
        "Next": doOptionsLinkStep
      }
    });
    $("#widgets div.ui-resizable-handle").addClass( "highlight" );
    $("#widgets div.ui-resizable-handle").effect( "pulsate", {times: 3}, 500);
    $("#dialog").dialog("open");
  }
  */
  
  // Options
  doOptionsLinkStep = function(){
    $("div.ui-dialog div.ui-dialog-titlebar span").text("Options");
    $("#dialog").html( "<p>Some widgets have <strong>options</strong>. You can click the options button, <img src=\"" + globalSettings.site_root + "img/icons/cog.png\"/> to change what the widget is displaying.</p>"
    );
    elem = "#tblWidgets a.configure";
    $(elem + ":first").focus();
    $("#dialog").dialog({
      height: 200,
      width: 400,
      autoOpen: true,
      position: "center",
      buttons: { 
        "Close": function(){$(this).dialog("close");},
        "Previous": doDragStep,
        "Next": doAddWidgetStep
      }
    });
    $("#container").effect("transfer", { to: $(elem+":first").eq(0) }, 700);
    $(elem).effect( "pulsate", {times: 3}, 500);
    $("#dialog").dialog("open");
  }
  
  // Add widget link
  doAddWidgetStep = function(){
    $("div.ui-dialog div.ui-dialog-titlebar span").text("Adding other widgets");
    $("#dialog").html( "<p>You can add in other widgets by clicking <strong>Add another widget</strong></p>"+
      "<p>It's possible to have several versions of the same widget, e.g. multiple project expenditure widgets, one for each of your projects</p>"
    );
    elem = "p.option a.add";
    $(elem).focus();
    $("#dialog").dialog({
      height: 200,
      width: 400,
      autoOpen: true,
      position: "center",
      buttons: { 
        "Close": function(){$(this).dialog("close");},
        "Previous": doOptionsLinkStep,
        "Next": doMinimiseLinkStep
      }
    });
    $("#container").effect("transfer", { to: $(elem+":first").eq(0) }, 700);
    $(elem).effect( "pulsate", {times: 3}, 500);
    $("#dialog").dialog("open");
  }
  
  doMinimiseLinkStep = function(){
    $("div.ui-dialog div.ui-dialog-titlebar span").text("Minimising widgets");
    $("#dialog").html( "<p>You can minimise widgets by clicking the minimise button, <img src=\""+globalSettings.site_root+"img/icons/minimise.png\"/></p>"+
      "<p>This makes it easier to work around large widgets.</p>"
    );
    elem = "p.option button.minimise";
    $(elem).focus();
    $("#dialog").dialog({
      height: 200,
      width: 400,
      autoOpen: true,
      position: "center",
      buttons: { 
        "Close": function(){$(this).dialog("close");},
        "Previous": doAddWidgetStep,
        "Next": doRemoveLinkStep
      }
    });
    $("#container").effect("transfer", { to: $(elem+":first").eq(0) }, 700);
    $(elem).effect( "pulsate", {times: 3}, 500);
    $("#dialog").dialog("open");
  }
  
  // Remove link
  doRemoveLinkStep = function(){
    $("div.ui-dialog div.ui-dialog-titlebar span").text("Removing widgets");
    $("#dialog").html( "<p>If a widget is no longer useful to you, you can remove it by clicking the <strong>close</strong> button, <img src=\""+globalSettings.site_root+"img/icons/cross.png\"/></p>" );
    elem = "p.option a.delete";
    $(elem + ":first").focus();
    $("#dialog").dialog({
      height: 200,
      width: 400,
      autoOpen: true,
      position: "center",
      buttons: { 
        "Close": function(){$(this).dialog("close");},
        "Previous": doMinimiseLinkStep,
        "Next": doFinalStep
      }
    });
    $("#container").effect("transfer", { to: $(elem+":first").eq(0) }, 700);
    $(elem).effect( "pulsate", {times: 3}, 500);
    $("#dialog").dialog("open");
  }
  
  // Goodbye / remove
  doFinalStep = function(){
    $("div.ui-dialog div.ui-dialog-titlebar span").text("Thank you!");
    $("#dialog").html( "<p>If you have finished with this tour, click the close button for this widget in order to remove it.</p>" );
    elem = "div.dashboard_intro p.option a.delete";
    $(elem+":first").focus();
    $("#dialog").dialog({
      height: 200,
      width: 400,
      autoOpen: true,
      position: "center",
      buttons: { 
        "Previous": doRemoveLinkStep,
        // "Close": function(){$(this).dialog("close");},
        "Finish": function(){$(elem).click()}
      }
    });
    $(elem).effect( "pulsate", {times: 3}, 500);
    $("#dialog").dialog("open");    
  }  
  $("div.dashboard_intro p.option a.delete").click(function(){
    $("#dialog").dialog("close");
  });
  
  // doOptionsLinkStep();
  doWelcomeStep();
});
