
function include(fileName){
	document.write("<script type='text/javascript' src='"+fileName+"'></script>" );
}

include("js/facebox.js");
var loadingElement = $('#loadingScreen');
var start = new Date().getTime();
if(loadingElement.length != 0)
	loadingElement.show();
	
$(window).ready(function() {
var end = new Date().getTime();
if(loadingElement.length != 0 )
{
	if((end-start>700))
		loadingElement.fadeOut();
	else
		loadingElement.hide();
}

$("#menu_items .selector").mouseover(function(){
  $(this).parent().children('h3').animate({
    height: "30px"
  }, 250 );
});
$("#menu_items .selector").mouseout(function(){
  $(this).parent().children('h3').animate({
    height: "0px"
  }, 250 );
}); 
});


