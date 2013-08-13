/*
jQuery.extend(jQuery.expr[':'], {
  "icontains": "jQuery.fn.text.apply([a]).toLowerCase().indexOf(m[3].toLowerCase())>=0",
});
*/

jQuery.expr[':'].iContains = function(a,i,m){
    return jQuery(a).text().toUpperCase().indexOf(m[3].toUpperCase())>=0;
};
