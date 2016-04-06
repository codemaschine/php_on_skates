
// http://stackoverflow.com/questions/1120335/how-to-make-css-visible-only-for-opera
// Sets browser infos as html.className
var params = [];
$.each($.browser, function(k, v) {
  var pat = /^[a-z].*/i;
  if(pat.test(k)) { params.push(k); }
});
params = params.join(' ');
$('html').addClass(params);
// ------------------------------------





$(document).ready(function() {
    
});  

