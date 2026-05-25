	var flipped=false;
	function scroll_finished(){
		if(flipped==true)
			$('#parralax').css("background-image", "url(../img/wedding_plane.png)");
		else
			$('#parralax').css("background-image", "url(../img/wedding_plane_flipped.png)");  

		flipped = !flipped;
	}
	
	$(window).load(function() {
		var slider = new my_slider('slider1');
		slider.start();
		var slider2 = new my_slider('slider2');
		slider2.start();
	});
	
	$(document).ready(function(){
		$('div#parralax').parallax(1, -0.6);
		$('div#cloud_front').parallax(-2, -0.6);
		$('div#cloud_back').parallax(-2, -0.7);
	});