var currentInterval;
function expand(id){
	var contenuObj = $("#contenu");
	var obj = $("#"+id+"");
	var text = $("#text_"+id+"");
	var objtop = obj.position().top;
	obj.css("z-index","500");
		obj.animate({
				'top': (objtop-50)
			  }, 350 );
		contenuObj.animate({
				'height': "1200px"
			  }, 450 );
		
		currentInterval = setInterval(function() {
			if( !contenuObj.is(":animated") ) {
				clearInterval(currentInterval);
				
				if(contenuObj.height() == 1200)
					text.fadeIn('slow');
			}
		}, 200);		
			  
}

function collapse(id,objtop){
var contenuObj = $("#contenu");
var obj = $("#"+id+"");
var text = $("#text_"+id+"");

clearInterval(currentInterval);
obj.css("z-index",'1');

	obj.animate({
			'top': objtop 
		  }, 350 );
	contenuObj.animate({
			'height': "250px"
		  }, 450 );
		  
	text.fadeOut('fast');

}

$(window).load(function() {

var imageToAnimate = new Array("elle", "lui");

	for (var i in imageToAnimate)
	{
		var curObject = $("#"+imageToAnimate[i]+"");
		var eltop = curObject.position().top;
		var containerHeight = $('#background_img').height();
		var elTopPercent = 100 * eltop / containerHeight;
		curObject.css("top",eltop+"px");//google chrome compatibility for jquery animation (percent don t work)
		curObject.mouseover(function(event){
			expand($(this).attr("id"));
		});

		curObject.mouseout(function(event){
			collapse($(this).attr("id"),eltop);
		});
	}
	
		/*$("#elle").mouseover(function(event){
			expand("elle");
		});

		$("#elle").mouseout(function(event){
			collapse("elle",$("#elle").position().top);
		});
	
		$("#luiselector").mouseover(function(event){
			expand("lui");
		});

		$("#lui").mouseout(function(event){
			collapse("lui",$("#lui").position().top);
		});*/
});