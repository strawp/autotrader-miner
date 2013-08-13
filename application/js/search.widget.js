$(function(){
  $(".widget_container .content #frmSearch").each(function(){
    bindAutoComplete.prototype.arr = new Array();
    bindAutoComplete.prototype.arr["model"] = $(this).find("input[name=model]").val();
    $(this).find("#strName").each(bindAutoComplete);
  });
});