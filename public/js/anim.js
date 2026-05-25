$(window).load(function() {
$(".postit").delay(400).animate({
    'margin-left': "200px"
  }, 350 );
 
 $("#menu_items .selector").click(function(event){
 event.preventDefault();
 var redirect = $(this).parent().attr('href');
 if(redirect != '#')
 {
	  $(".postit").animate({
		'margin-left': "-1500px"
	  }, 350 );
	setTimeout(function() {window.location.href = redirect}, 400);
	}


});


});


